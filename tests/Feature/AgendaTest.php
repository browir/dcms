<?php

namespace Tests\Feature;

use App\Enum\JenisSurat;
use App\Filament\Admin\Resources\Agendas\Pages\CreateAgenda;
use App\Filament\Admin\Resources\Agendas\Pages\ListAgendas;
use App\Filament\Admin\Resources\Agendas\Pages\ViewAgenda;
use App\Models\Agenda;
use App\Models\Company;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\LetterNumber;
use App\Models\Meeting;
use App\Models\MeetingLocation;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\AgendaAnnouncedNotification;
use App\Services\AgendaAnnouncer;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Kegiatan unit: ruangan bersama rapat, surat pemberitahuan, dan pengumuman. */
class AgendaTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Unit $unit;

    private Unit $otherUnit;

    private MeetingLocation $room;

    private User $creator;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-21 08:00');
        Role::findOrCreate('kabid', 'web');
        Role::findOrCreate('super_admin', 'web');
        $staff = Role::findOrCreate('staf', 'web');
        $staff->givePermissionTo(Permission::findOrCreate('Create:Meeting', 'web'));

        $this->company = Company::create(['name' => 'PT Syifa Global Group', 'code' => 'SYIFA', 'kode_surat' => 'SGG']);
        $department = Department::create(['name' => 'Umum', 'code' => 'UMM', 'company_id' => $this->company->id]);
        $this->unit = Unit::create(['name' => 'Kepegawaian', 'prefix' => '2', 'department_id' => $department->id, 'whatsapp_group' => 'grup-kepegawaian@g.us']);
        $this->otherUnit = Unit::create(['name' => 'Keuangan', 'prefix' => '3', 'department_id' => $department->id]);
        $this->room = MeetingLocation::create(['name' => 'Aula Serbaguna', 'capacity' => 80, 'company_id' => $this->company->id]);

        $this->creator = $this->user('penyelenggara', $this->unit);
        $this->creator->assignRole($staff);
    }

    private function user(string $username, Unit $unit, ?Company $company = null): User
    {
        return User::create([
            'name' => ucfirst($username),
            'username' => $username,
            'email' => "{$username}@syifa.test",
            'password' => 'password',
            'company_id' => ($company ?? $this->company)->id,
            'department_id' => $unit->department_id,
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);
    }

    private function agenda(array $attributes = []): Agenda
    {
        return Agenda::create([
            'title' => 'Pelatihan Pengelolaan Arsip',
            'kategori' => 'pelatihan',
            'company_id' => $this->company->id,
            'department_id' => $this->unit->department_id,
            'unit_id' => $this->unit->id,
            'date_time' => '2026-10-26 09:00',
            'end_time' => '2026-10-26 12:00',
            'meeting_location_id' => $this->room->id,
            'audience' => Agenda::AUDIENCE_UNIT,
            'created_by' => $this->creator->id,
            ...$attributes,
        ]);
    }

    public function test_ruangan_dibagi_antara_rapat_dan_kegiatan(): void
    {
        $meeting = Meeting::create([
            'title' => 'Rapat Direksi', 'date_time' => '2026-10-26 08:00', 'end_time' => '2026-10-26 10:00',
            'meeting_location_id' => $this->room->id, 'location' => $this->room->name, 'status' => 'scheduled',
            'company_id' => $this->company->id, 'created_by' => $this->creator->id,
        ]);

        // Kegiatan 09.00 di ruangan yang sama bentrok dengan rapat.
        $conflict = Meeting::locationConflict($this->room->id, $this->room->name, Carbon::parse('2026-10-26 09:00'), Carbon::parse('2026-10-26 12:00'));
        $this->assertTrue($conflict->is($meeting));

        // Sebaliknya: rapat baru bentrok dengan kegiatan yang sudah ada.
        $meeting->update(['status' => 'cancelled']);
        $agenda = $this->agenda();
        $conflict = Meeting::locationConflict($this->room->id, $this->room->name, Carbon::parse('2026-10-26 11:00'), Carbon::parse('2026-10-26 13:00'));
        $this->assertTrue($conflict->is($agenda));
        $this->assertSame('kegiatan "Pelatihan Pengelolaan Arsip"', $conflict->bookingLabel());

        // Kegiatan tidak bentrok dengan dirinya sendiri saat diedit.
        $this->assertNull(Meeting::locationConflict($this->room->id, $this->room->name, Carbon::parse('2026-10-26 09:00'), Carbon::parse('2026-10-26 12:00'), excludeAgendaId: $agenda->id));
    }

    public function test_halaman_daftar_kegiatan_menampilkan_tab_mendatang(): void
    {
        $upcoming = $this->agenda();
        $past = $this->agenda(['title' => 'Kegiatan lalu', 'date_time' => '2026-10-01 09:00', 'end_time' => '2026-10-01 10:00', 'meeting_location_id' => null, 'location' => 'Aula']);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->creator);

        Livewire::test(ListAgendas::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$upcoming])
            ->assertCanNotSeeTableRecords([$past])
            ->set('activeTab', 'lewat')
            ->assertCanSeeTableRecords([$past]);
    }

    public function test_form_menolak_ruangan_yang_sedang_dipakai(): void
    {
        $this->agenda();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->creator);

        Livewire::test(CreateAgenda::class)
            ->fillForm([
                'title' => 'Sosialisasi K3',
                'unit_id' => $this->unit->id,
                'date_time' => '2026-10-26 10:00',
                'end_time' => '2026-10-26 11:00',
                'meeting_location_id' => $this->room->id,
                'audience' => Agenda::AUDIENCE_UNIT,
            ])
            ->call('create')
            ->assertHasFormErrors(['meeting_location_id']);
    }

    public function test_catat_kegiatan_dengan_surat_dan_langsung_diumumkan(): void
    {
        Notification::fake();
        $colleague = $this->user('rekan', $this->unit);
        $otherUnitUser = $this->user('lain', $this->otherUnit);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->creator);

        Livewire::test(CreateAgenda::class)
            ->fillForm([
                'title' => 'Apel Pagi Bersama',
                'kategori' => 'apel',
                'unit_id' => $this->unit->id,
                'date_time' => '2026-10-27 07:30',
                'end_time' => '2026-10-27 08:30',
                'location' => 'Lapangan Parkir',
                'audience' => Agenda::AUDIENCE_UNIT,
                'buat_surat' => true,
                'jenis_surat' => JenisSurat::EDARAN_PENGUMUMAN->value,
                'umumkan_aplikasi' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $agenda = Agenda::query()->sole();
        $this->assertSame($this->company->id, $agenda->company_id);
        $this->assertSame('001/C.2/SGG/X/2026', $agenda->letterNumber->nomor);
        $this->assertStringContainsString('Apel Pagi Bersama', $agenda->notice_body);
        $this->assertNotNull($agenda->announced_at);

        // Notifikasi hanya ke karyawan unit penyelenggara.
        Notification::assertSentTo([$this->creator, $colleague], AgendaAnnouncedNotification::class);
        Notification::assertNotSentTo($otherUnitUser, AgendaAnnouncedNotification::class);

        // Nomor surat kegiatan dihitung terpakai dan tidak bisa dipakai dokumen.
        $this->assertSame(0, LetterNumber::query()->available()->count());
        $this->expectException(ValidationException::class);
        Document::create([
            'title' => 'Dokumen lain', 'letter_number_id' => $agenda->letter_number_id, 'document_type' => 'file',
            'company_id' => $this->company->id, 'department_id' => $this->unit->department_id, 'unit_id' => $this->unit->id,
            'category_id' => DocumentCategory::create(['name' => 'Memo', 'prefix' => 'MI', 'company_id' => $this->company->id])->id,
            'user_id' => $this->creator->id,
        ]);
    }

    public function test_jangkauan_pengumuman_dan_akses(): void
    {
        $sameUnit = $this->user('rekan', $this->unit);
        $otherUnit = $this->user('lain', $this->otherUnit);
        $otherCompanyUser = $this->user('luar', $this->otherUnit, Company::create(['name' => 'RSU Syifa', 'code' => 'RSUSM']));

        $announcer = app(AgendaAnnouncer::class);
        $unitAgenda = $this->agenda();
        $companyAgenda = $this->agenda(['title' => 'Senam Pagi', 'audience' => Agenda::AUDIENCE_COMPANY, 'meeting_location_id' => null, 'location' => 'Lapangan']);

        $this->assertEqualsCanonicalizing([$this->creator->id, $sameUnit->id], $announcer->recipients($unitAgenda)->pluck('id')->all());
        $this->assertCount(3, $announcer->recipients($companyAgenda)); // tanpa karyawan perusahaan lain
        $this->assertCount(4, $announcer->recipients($this->agenda(['audience' => Agenda::AUDIENCE_ALL, 'meeting_location_id' => null, 'location' => 'Online'])));

        $this->actingAs($otherUnit);
        $this->assertEqualsCanonicalizing([$companyAgenda->id], Agenda::query()->access()->where('audience', '!=', 'all')->pluck('id')->all());

        $this->actingAs($otherCompanyUser);
        $this->assertSame(0, Agenda::query()->access()->where('audience', '!=', 'all')->count());
    }

    public function test_aksi_umumkan_di_halaman_detail(): void
    {
        Notification::fake();
        $agenda = $this->agenda();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->creator);

        Livewire::test(ViewAgenda::class, ['record' => $agenda->getRouteKey()])
            ->assertOk()
            ->callAction('umumkan', ['in_app' => true, 'whatsapp' => false])
            ->assertHasNoActionErrors();

        $this->assertNotNull($agenda->refresh()->announced_at);
        Notification::assertSentTo($this->creator, AgendaAnnouncedNotification::class);
    }

    public function test_kirim_ke_grup_whatsapp_lewat_n8n(): void
    {
        config(['services.n8n.webhook_url' => 'https://n8n.test/webhook/dcms', 'services.n8n.secret' => 'rahasia']);
        Http::fake(['n8n.test/*' => Http::response(['ok' => true])]);

        $agenda = $this->agenda();
        $announcer = app(AgendaAnnouncer::class);
        $announcer->issueNotice($agenda, by: $this->creator);

        $result = $announcer->sendToWhatsAppGroup($agenda->refresh());

        $this->assertTrue($result['sent']);
        $this->assertNotNull($agenda->refresh()->whatsapp_sent_at);
        Http::assertSent(fn (Request $request) => $request['to'] === 'grup-kepegawaian@g.us'
            && $request['channel'] === 'whatsapp_group'
            && $request['letter_number'] === '001/C.2/SGG/X/2026'
            && str_contains($request['message'], 'Pelatihan Pengelolaan Arsip')
            && str_contains($request['notice_pdf_url'], 'signature=')
            && $request->hasHeader('X-DCMS-Signature', 'rahasia'));
    }

    public function test_whatsapp_tidak_dikirim_tanpa_grup(): void
    {
        config(['services.n8n.webhook_url' => 'https://n8n.test/webhook/dcms', 'services.n8n.default_group' => null]);
        Http::fake();

        $result = app(AgendaAnnouncer::class)->sendToWhatsAppGroup($this->agenda(['unit_id' => $this->otherUnit->id]));

        $this->assertFalse($result['sent']);
        $this->assertStringContainsString('grup WhatsApp belum diatur', $result['reason']);
        Http::assertNothingSent();
    }

    public function test_api_integrasi_hanya_menampilkan_kegiatan_yang_diumumkan(): void
    {
        Notification::fake();
        $announced = $this->agenda();
        app(AgendaAnnouncer::class)->issueNotice($announced, by: $this->creator);
        app(AgendaAnnouncer::class)->announceInApp($announced);
        $draft = $this->agenda(['title' => 'Belum diumumkan', 'meeting_location_id' => null, 'location' => 'Lapangan']);

        $this->getJson('/api/v1/kegiatan')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Pelatihan Pengelolaan Arsip')
            ->assertJsonPath('data.0.notice.number', '001/C.2/SGG/X/2026')
            ->assertJsonPath('data.0.organizer.company_code', 'SGG');

        $this->getJson("/api/v1/kegiatan/{$draft->id}")->assertNotFound();
    }

    public function test_surat_pemberitahuan_pdf(): void
    {
        $agenda = $this->agenda();
        app(AgendaAnnouncer::class)->issueNotice($agenda, by: $this->creator);

        $this->actingAs($this->creator)->get(route('kegiatan.surat', $agenda))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        auth()->logout();
        $this->get(URL::temporarySignedRoute('kegiatan.surat.signed', now()->addDay(), ['agenda' => $agenda->id]))->assertOk();
        $this->get(route('kegiatan.surat.signed', ['agenda' => $agenda->id]))->assertForbidden();
    }
}
