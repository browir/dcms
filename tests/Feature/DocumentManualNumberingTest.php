<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Documents\Pages\CreateDocument;
use App\Filament\Admin\Resources\LetterNumbers\Schemas\LetterNumberForm;
use App\Models\Company;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\LetterNumber;
use App\Models\Unit;
use App\Models\User;
use App\Services\LetterNumberManualService;
use App\Services\LetterNumberService;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DocumentManualNumberingTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Department $department;
    private Unit $unit;
    private DocumentCategory $category;
    private User $adminUser;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-19 10:00');
        Notification::fake();
        Storage::fake('documents');

        Role::firstOrCreate(['name' => 'kabid']);
        Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'Sekretaris']);
        $stafRole = Role::firstOrCreate(['name' => 'staf']);
        $stafRole->givePermissionTo(collect(['ViewAny:Document', 'Create:Document'])
            ->map(fn ($name) => Permission::firstOrCreate(['name' => $name])));

        $this->company = Company::create(['name' => 'PT Syifa Global Group', 'code' => 'SYIFA', 'kode_surat' => 'SGG']);
        $this->department = Department::create(['name' => 'Umum', 'code' => 'UMM', 'company_id' => $this->company->id]);
        $this->unit = Unit::create(['name' => 'Kepegawaian', 'prefix' => '2', 'department_id' => $this->department->id]);
        $this->category = DocumentCategory::create([
            'name' => 'SOP Unit',
            'prefix' => 'SOP',
            'code' => 'SOP',
            'kelompok' => 'dokumen',
            'company_id' => $this->company->id,
        ]);

        $this->adminUser = User::create([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'admin@example.test',
            'password' => 'password',
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);
        $this->adminUser->assignRole('super_admin');

        $this->regularUser = User::create([
            'name' => 'Staf Biasa',
            'username' => 'stafbiasa',
            'email' => 'staf@example.test',
            'password' => 'password',
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);
        $this->regularUser->assignRole($stafRole);

        $this->actingAs($this->adminUser);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_normalisasi_dan_validasi_pola_standar_format(): void
    {
        $this->assertSame('001/SOP.2/SGG/VII/2026', LetterNumberManualService::normalize('   001/SOP.2/SGG/VII/2026   '));
        $this->assertSame('001 / SOP / SGG', LetterNumberManualService::normalize("001   /   SOP   /   SGG"));

        // Pola standar vs kustom
        $this->assertTrue(LetterNumberManualService::matchesStandardPattern('001/SOP.2/SGG/VII/2026'));
        $this->assertNull(LetterNumberManualService::getPatternWarning('001/SOP.2/SGG/VII/2026'));

        $this->assertFalse(LetterNumberManualService::matchesStandardPattern('DOC-CUSTOM-001'));
        $this->assertNotNull(LetterNumberManualService::getPatternWarning('DOC-CUSTOM-001'));
    }

    public function test_nomor_manual_valid_tersimpan_dan_berstatus_manual(): void
    {
        $this->actingAs($this->adminUser);

        $letter = LetterNumberForm::issue([
            'numbering_type' => LetterNumber::NUMBERING_MANUAL,
            'nomor_manual'   => '   009/SOP.2/SGG/VII/2026   ',
            'unit_id'        => $this->unit->id,
            'category_id'    => $this->category->id,
            'tanggal_surat'  => '2026-07-15',
            'judul_surat'    => 'SOP Manual Testing',
        ]);

        $this->assertSame('009/SOP.2/SGG/VII/2026', $letter->nomor);
        $this->assertSame(LetterNumber::NUMBERING_MANUAL, $letter->numbering_type);
        $this->assertTrue($letter->isManual());
        $this->assertFalse($letter->isAuto());
        $this->assertNull($letter->nomor_urut);
    }

    public function test_nomor_manual_kosong_ditolak(): void
    {
        $this->actingAs($this->adminUser);

        $this->expectException(ValidationException::class);

        LetterNumberForm::issue([
            'numbering_type' => LetterNumber::NUMBERING_MANUAL,
            'nomor_manual'   => '    ',
            'unit_id'        => $this->unit->id,
            'category_id'    => $this->category->id,
            'tanggal_surat'  => '2026-07-15',
            'judul_surat'    => 'SOP Kosong',
        ]);
    }

    public function test_nomor_manual_duplikat_ditolak_secara_case_insensitive_dan_spasi(): void
    {
        $this->actingAs($this->adminUser);

        LetterNumberForm::issue([
            'numbering_type' => LetterNumber::NUMBERING_MANUAL,
            'nomor_manual'   => '001/SOP.2/SGG/VII/2026',
            'unit_id'        => $this->unit->id,
            'category_id'    => $this->category->id,
            'tanggal_surat'  => '2026-07-15',
            'judul_surat'    => 'SOP Pertama',
        ]);

        $this->expectException(ValidationException::class);

        // Duplikat huruf kecil dan spasi ekstra
        LetterNumberForm::issue([
            'numbering_type' => LetterNumber::NUMBERING_MANUAL,
            'nomor_manual'   => '   001/sop.2/sgg/vii/2026   ',
            'unit_id'        => $this->unit->id,
            'category_id'    => $this->category->id,
            'tanggal_surat'  => '2026-07-15',
            'judul_surat'    => 'SOP Kedua',
        ]);
    }

    public function test_nomor_manual_duplikat_dengan_nomor_otomatis_ditolak(): void
    {
        $this->actingAs($this->adminUser);

        // Buat nomor otomatis
        $auto = LetterNumberForm::issue([
            'numbering_type' => LetterNumber::NUMBERING_AUTO,
            'unit_id'        => $this->unit->id,
            'category_id'    => $this->category->id,
            'tanggal_surat'  => '2026-07-15',
            'judul_surat'    => 'SOP Auto',
        ]);

        $this->assertSame('001/SOP.2/SGG/VII/2026', $auto->nomor);

        $this->expectException(ValidationException::class);

        // Coba masukkan manual dengan nomor yang sama persis
        LetterNumberForm::issue([
            'numbering_type' => LetterNumber::NUMBERING_MANUAL,
            'nomor_manual'   => strtolower($auto->nomor),
            'unit_id'        => $this->unit->id,
            'category_id'    => $this->category->id,
            'tanggal_surat'  => '2026-07-15',
            'judul_surat'    => 'SOP Manual Rebut',
        ]);
    }

    public function test_nomor_manual_tidak_memengaruhi_counter_otomatis(): void
    {
        $this->actingAs($this->adminUser);

        // Terbitkan 2 nomor manual
        LetterNumberForm::issue([
            'numbering_type' => LetterNumber::NUMBERING_MANUAL,
            'nomor_manual'   => 'MANUAL-A',
            'unit_id'        => $this->unit->id,
            'category_id'    => $this->category->id,
            'tanggal_surat'  => '2026-07-15',
            'judul_surat'    => 'Manual A',
        ]);

        LetterNumberForm::issue([
            'numbering_type' => LetterNumber::NUMBERING_MANUAL,
            'nomor_manual'   => 'MANUAL-B',
            'unit_id'        => $this->unit->id,
            'category_id'    => $this->category->id,
            'tanggal_surat'  => '2026-07-15',
            'judul_surat'    => 'Manual B',
        ]);

        // Terbitkan nomor otomatis pertama kali
        $auto = LetterNumberForm::issue([
            'numbering_type' => LetterNumber::NUMBERING_AUTO,
            'unit_id'        => $this->unit->id,
            'category_id'    => $this->category->id,
            'tanggal_surat'  => '2026-07-15',
            'judul_surat'    => 'Auto Pertama',
        ]);

        // Counter harus tetap dimulai dari 1!
        $this->assertSame(1, $auto->nomor_urut);
        $this->assertSame('001/SOP.2/SGG/VII/2026', $auto->nomor);
    }

    public function test_generator_otomatis_melompati_nomor_jika_sudah_dipakai_oleh_nomor_manual(): void
    {
        $this->actingAs($this->adminUser);

        // Pengguna sengaja memasukkan nomor manual yang persis nomor auto ke-1: 001/SOP.2/SGG/VII/2026
        LetterNumberForm::issue([
            'numbering_type' => LetterNumber::NUMBERING_MANUAL,
            'nomor_manual'   => '001/SOP.2/SGG/VII/2026',
            'unit_id'        => $this->unit->id,
            'category_id'    => $this->category->id,
            'tanggal_surat'  => '2026-07-15',
            'judul_surat'    => 'Manual Mendahului Auto 1',
        ]);

        // Sekarang terbitkan nomor otomatis
        $auto = LetterNumberForm::issue([
            'numbering_type' => LetterNumber::NUMBERING_AUTO,
            'unit_id'        => $this->unit->id,
            'category_id'    => $this->category->id,
            'tanggal_surat'  => '2026-07-15',
            'judul_surat'    => 'Auto Harus Skip Ke 2',
        ]);

        // Generator otomatis harus lompat ke 2 tanpa crash
        $this->assertSame(2, $auto->nomor_urut);
        $this->assertSame('002/SOP.2/SGG/VII/2026', $auto->nomor);
    }

    public function test_pengguna_tanpa_hak_akses_ditolak_membuat_nomor_manual(): void
    {
        $this->actingAs($this->regularUser);

        $this->expectException(AuthorizationException::class);

        LetterNumberForm::issue([
            'numbering_type' => LetterNumber::NUMBERING_MANUAL,
            'nomor_manual'   => 'MANUAL-UNAUTHORIZED',
            'unit_id'        => $this->unit->id,
            'category_id'    => $this->category->id,
            'tanggal_surat'  => '2026-07-15',
            'judul_surat'    => 'Coba Ilegal',
        ]);
    }

    public function test_alur_tambah_dokumen_menggunakan_nomor_manual(): void
    {
        $this->actingAs($this->regularUser);

        // 1. Ambil nomor manual (sebagai admin)
        $letter = LetterNumberManualService::issue(
            unit: $this->unit,
            jenis: $this->category,
            tanggal: Carbon::parse('2026-07-15'),
            nomorManual: '099/SK/DIR/VII/2026',
            judul: 'SK Direktur Khusus',
            by: $this->adminUser,
        );

        $this->assertSame('099/SK/DIR/VII/2026', $letter->nomor);
        $this->assertSame(LetterNumber::NUMBERING_MANUAL, $letter->numbering_type);

        // 2. Isi dokumen menggunakan nomor manual tersebut
        Livewire::withQueryParams(['nomor_surat' => $letter->id])
            ->test(CreateDocument::class)
            ->assertSchemaStateSet([
                'letter_number_id' => $letter->id,
                'category_id'      => $this->category->id,
                'tanggal_surat'    => '2026-07-15',
                'title'            => 'SK Direktur Khusus',
            ])
            ->fillForm([
                'document_type' => 'file',
                'file_path'     => UploadedFile::fake()->create('sk.pdf', 20, 'application/pdf'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $doc = Document::query()->sole();
        $this->assertSame($letter->id, $doc->letter_number_id);
        $this->assertSame('099/SK/DIR/VII/2026', $doc->code_number);

        // Nomor yang sudah terpakai dokumen tidak lagi tersedia di scopeAvailable
        $this->assertSame(0, LetterNumber::query()->available()->count());
    }
}
