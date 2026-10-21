<?php

namespace Tests\Feature;

use App\Enum\JenisSurat;
use App\Filament\Admin\Resources\Agendas\Pages\ListAgendas;
use App\Filament\Admin\Resources\Departments\Pages\ManageDepartments;
use App\Filament\Admin\Resources\Documents\Pages\ListDocuments;
use App\Filament\Admin\Resources\LetterNumbers\Pages\ListLetterNumbers;
use App\Filament\Admin\Resources\Meetings\Pages\ListMeetings;
use App\Filament\Admin\Resources\Units\Pages\ManageUnits;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Models\Company;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Meeting;
use App\Models\Unit;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Semua halaman daftar data terbuka tanpa error, di setiap tab dan dengan filter utama. */
class ListPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Document $pending;

    private Document $approved;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Role::findOrCreate('kabid', 'web');
        $role = Role::findOrCreate('super_admin', 'web');
        $role->givePermissionTo(collect(['Document', 'Meeting', 'User', 'Unit', 'Department'])
            ->flatMap(fn ($model) => ["ViewAny:{$model}", "View:{$model}", "Create:{$model}", "Update:{$model}", "Delete:{$model}"])
            ->map(fn ($name) => Permission::findOrCreate($name, 'web')));

        $company = Company::create(['name' => 'PT Syifa Global Group', 'code' => 'SYIFA', 'kode_surat' => 'SGG']);
        $department = Department::create(['name' => 'Umum', 'code' => 'UMM', 'company_id' => $company->id]);
        $unit = Unit::create(['name' => 'Kepegawaian', 'prefix' => '2', 'department_id' => $department->id]);
        $category = DocumentCategory::create(['name' => 'SOP', 'prefix' => 'SOP', 'company_id' => $company->id]);

        $this->admin = User::create([
            'name' => 'Admin', 'username' => 'admin', 'email' => 'admin@syifa.test', 'password' => 'password',
            'company_id' => $company->id, 'department_id' => $department->id, 'unit_id' => $unit->id, 'is_active' => true,
        ]);
        $this->admin->assignRole($role);

        $make = fn (string $title, string $status) => Document::create([
            'title' => $title, 'status' => $status, 'jenis_surat' => JenisSurat::SOP, 'tanggal_surat' => today(),
            'company_id' => $company->id, 'department_id' => $department->id, 'unit_id' => $unit->id,
            'category_id' => $category->id, 'user_id' => $this->admin->id, 'document_type' => 'file',
        ]);
        $this->pending = $make('SOP menunggu review', Document::STATUS_PENDING_KABID);
        $this->approved = $make('SOP disetujui', Document::STATUS_APPROVED);

        Meeting::create([
            'title' => 'Rapat lalu tanpa notulen', 'date_time' => now()->subDay(), 'end_time' => now()->subDay()->addHour(),
            'status' => 'completed', 'company_id' => $company->id, 'created_by' => $this->admin->id,
        ]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->admin);
    }

    public function test_daftar_dokumen_tab_dan_filter(): void
    {
        $page = Livewire::test(ListDocuments::class)->assertOk();

        // Super admin punya antrean review → tab default "Perlu Tindakan Saya".
        $page->assertSet('activeTab', 'perlu_tindakan')
            ->assertCanSeeTableRecords([$this->pending])
            ->assertCanNotSeeTableRecords([$this->approved]);

        foreach (['milik_saya', 'direview', 'disetujui', 'wajib_baca', 'semua'] as $tab) {
            $page->set('activeTab', $tab)->assertOk();
        }

        $page->set('activeTab', 'semua')
            ->filterTable('status', [Document::STATUS_APPROVED])
            ->assertCanSeeTableRecords([$this->approved])
            ->assertCanNotSeeTableRecords([$this->pending])
            ->resetTableFilters()
            ->filterTable('masa_berlaku', 'tanpa_batas')
            ->assertCanSeeTableRecords([$this->pending, $this->approved]);
    }

    public function test_daftar_rapat_semua_tab(): void
    {
        $page = Livewire::test(ListMeetings::class)->assertOk();

        foreach (['hari_ini', 'terjadwal', 'undangan', 'perlu_notulen', 'berakhir', 'batal', 'semua'] as $tab) {
            $page->set('activeTab', $tab)->assertOk();
        }

        $page->set('activeTab', 'perlu_notulen')->assertCountTableRecords(1);
    }

    public function test_daftar_lain_terbuka(): void
    {
        foreach ([ListAgendas::class, ListLetterNumbers::class, ListUsers::class, ManageUnits::class, ManageDepartments::class] as $page) {
            Livewire::test($page)->assertOk();
        }

        Livewire::test(ListUsers::class)->filterTable('sso', false)->assertCanSeeTableRecords([$this->admin]);
        Livewire::test(ManageUnits::class)->filterTable('tanpa_kode_hris', true)->assertCountTableRecords(1);
    }
}
