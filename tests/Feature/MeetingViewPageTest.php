<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Meetings\Pages\ViewMeeting;
use App\Models\Company;
use App\Models\Meeting;
use App\Models\User;
use App\Support\RoleAccess;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Halaman Detail Rapat: action plan, peserta, berkas, dan lampiran tampil dengan benar. */
class MeetingViewPageTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        RoleAccess::sync();
        $this->company = Company::create(['name' => 'RSU Syifa Medika', 'code' => 'RSU', 'kode_surat' => 'RSU']);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function user(string $name, string $username): User
    {
        $user = User::create([
            'name' => $name,
            'username' => $username,
            'email' => $username.'@example.test',
            'password' => 'password',
            'company_id' => $this->company->id,
        ]);
        $user->assignRole('super_admin');

        return $user;
    }

    public function test_detail_rapat_menampilkan_action_plan_pic_dan_peserta(): void
    {
        $notulis = $this->user('Wira Margono', 'wira');
        $pic = $this->user('Lutfi Salisa', 'lutfi');

        $meeting = Meeting::create([
            'title' => 'TIM MRMIK',
            'agenda' => "1. Evaluasi EP\n2. Pembagian jobdesk",
            'date_time' => now()->subDay(),
            'status' => 'completed',
            'company_id' => $this->company->id,
            'created_by' => $notulis->id,
            'notulis_id' => $notulis->id,
            'action_items' => [
                ['pembahasan' => 'Evaluasi EP tahun 2023', 'action_plan' => 'Merevisi dokumen akreditasi', 'pic_ids' => [$pic->id]],
            ],
            'attachments' => ['meetings/attachments/foto.png'],
        ]);
        $meeting->participants()->attach([$notulis->id, $pic->id]);

        $this->actingAs($pic);

        Livewire::test(ViewMeeting::class, ['record' => $meeting->getRouteKey()])
            ->assertOk()
            ->assertSee('Action Plan &amp; PIC (1)', false)
            ->assertSee('Merevisi dokumen akreditasi')
            ->assertSee('Tugas Anda')
            ->assertSee('Peserta (2)')
            ->assertSee('Notulis')
            ->assertSee('Lampiran Dokumentasi (1)')
            ->assertSee('Belum ada berkas notulensi')
            ->assertSee('1. Evaluasi EP<br />', false);
    }

    public function test_detail_rapat_tanpa_action_plan_menampilkan_empty_state(): void
    {
        $user = $this->user('Wira Margono', 'wira');

        $meeting = Meeting::create([
            'title' => 'Rapat Koordinasi',
            'date_time' => now()->addDay(),
            'status' => 'scheduled',
            'company_id' => $this->company->id,
            'created_by' => $user->id,
            'file_path' => 'meetings/notulen_1.pdf',
        ]);

        $this->actingAs($user);

        Livewire::test(ViewMeeting::class, ['record' => $meeting->getRouteKey()])
            ->assertOk()
            ->assertSee('Belum ada action plan')
            ->assertSee('Buka Notulensi')
            ->assertDontSee('Lampiran Dokumentasi')
            ->assertDontSee('Catatan Tambahan');
    }
}
