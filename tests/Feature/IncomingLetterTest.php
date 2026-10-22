<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\IncomingLetters\IncomingLetterResource;
use App\Filament\Admin\Resources\IncomingLetters\Pages\CreateIncomingLetter;
use App\Models\Company;
use App\Models\Department;
use App\Models\IncomingLetter;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\IncomingLetterReceivedNotification;
use App\Support\RoleAccess;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Surat Masuk: nomor agenda, notifikasi unit tujuan, dan akses per unit. */
class IncomingLetterTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Unit $unit;

    private Unit $otherUnit;

    private User $secretary;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Storage::fake('documents');
        RoleAccess::sync();

        // Sekretariat: boleh mencatat surat masuk.
        Role::findOrCreate('sekretariat', 'web')->givePermissionTo(
            collect(['ViewAny', 'View', 'Create', 'Update'])->map(fn ($a) => Permission::findOrCreate("{$a}:IncomingLetter", 'web'))
        );

        $this->company = Company::create(['name' => 'PT Syifa Global Group', 'code' => 'SYIFA', 'kode_surat' => 'SGG']);
        $department = Department::create(['name' => 'Umum', 'code' => 'UMM', 'company_id' => $this->company->id]);
        $this->unit = Unit::create(['name' => 'Kepegawaian', 'prefix' => '2', 'department_id' => $department->id, 'is_active' => true]);
        $this->otherUnit = Unit::create(['name' => 'Keuangan', 'prefix' => '3', 'department_id' => $department->id, 'is_active' => true]);

        $this->secretary = $this->user('sekretaris', 'sekretariat', $this->otherUnit);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function user(string $username, string $role, Unit $unit): User
    {
        $user = User::create([
            'name' => ucfirst($username),
            'username' => $username,
            'email' => "{$username}@example.test",
            'password' => 'password',
            'company_id' => $this->company->id,
            'department_id' => $unit->department_id,
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function record(): IncomingLetter
    {
        $this->actingAs($this->secretary);
        Livewire::test(CreateIncomingLetter::class)
            ->fillForm([
                'pengirim' => 'Dinas Kesehatan Kota',
                'nomor_surat' => '445/123/DINKES/2026',
                'tanggal_surat' => '2026-10-20',
                'perihal' => 'Undangan rapat koordinasi akreditasi',
                'sifat' => 'segera',
                'tanggal_terima' => '2026-10-22',
                'unit_id' => $this->unit->id,
                'file_path' => UploadedFile::fake()->create('surat.pdf', 30, 'application/pdf'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        return IncomingLetter::query()->latest('id')->firstOrFail();
    }

    public function test_catat_surat_masuk_dapat_nomor_agenda_dan_unit_tujuan_diberi_tahu(): void
    {
        $staff = $this->user('staf', 'pegawai', $this->unit);
        $outsider = $this->user('lain', 'pegawai', $this->otherUnit);

        $letter = $this->record();

        $this->assertSame('001/SM/SGG/X/2026', $letter->nomor_agenda);
        $this->assertSame(IncomingLetter::STATUS_BARU, $letter->status);
        $this->assertSame($this->secretary->id, $letter->created_by);
        Notification::assertSentTo($staff, IncomingLetterReceivedNotification::class);
        Notification::assertNotSentTo($outsider, IncomingLetterReceivedNotification::class);

        // Nomor agenda berurutan per perusahaan per tahun.
        $this->assertSame('002/SM/SGG/X/2026', $this->record()->nomor_agenda);
    }

    /** Surat dengan scan, dicatat sekretariat untuk unit Kepegawaian. */
    private function stored(): IncomingLetter
    {
        // Lewat model (bukan Livewire::test) supaya state Livewire tidak terbawa ke request HTTP berikutnya.
        Storage::disk('documents')->put('surat-masuk/surat.pdf', '%PDF-1.4');
        $this->actingAs($this->secretary);

        return IncomingLetter::create([
            'pengirim' => 'Dinas Kesehatan Kota',
            'perihal' => 'Undangan rapat koordinasi',
            'tanggal_terima' => '2026-10-22',
            'unit_id' => $this->unit->id,
            'file_path' => 'surat-masuk/surat.pdf',
        ]);
    }

    public function test_unit_tujuan_bisa_membuka_dan_status_jadi_dibaca(): void
    {
        $letter = $this->stored();
        $staff = $this->user('staf', 'pegawai', $this->unit);

        $this->actingAs($staff)->get(IncomingLetterResource::getUrl('view', ['record' => $letter]))->assertOk();
        $this->get(route('surat-masuk.file', $letter))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertSame(IncomingLetter::STATUS_DIBACA, $letter->fresh()->status);
        $this->assertSame($staff->id, $letter->fresh()->read_by);
    }

    public function test_unit_lain_tidak_bisa_membuka_surat_maupun_scannya(): void
    {
        $letter = $this->stored();
        $outsider = $this->user('lain', 'pegawai', $this->otherUnit);

        $this->assertFalse($outsider->can('view', $letter));
        $this->assertTrue($this->secretary->can('view', $letter));
        $this->actingAs($outsider)->get(route('surat-masuk.file', $letter))->assertForbidden();
        $this->assertSame(0, IncomingLetter::query()->access($outsider)->count());
    }
}
