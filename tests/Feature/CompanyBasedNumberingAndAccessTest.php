<?php

namespace Tests\Feature;

use App\Enums\JenisSurat;
use App\Filament\Admin\Resources\LetterNumbers\LetterNumberResource;
use App\Filament\Admin\Resources\LetterNumbers\Schemas\LetterNumberForm;
use App\Filament\Admin\Resources\SuratKeluar\Pages\ListSuratKeluar;
use App\Models\Company;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\LetterNumber;
use App\Models\Surat;
use App\Models\Unit;
use App\Models\User;
use App\Services\LetterNumberService;
use App\Services\NomorSuratService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CompanyBasedNumberingAndAccessTest extends TestCase
{
    use RefreshDatabase;

    private Company $sgg;
    private Company $rsusmBjb;
    private Company $rsusmBrb;

    private Unit $unitSgg;
    private Unit $unitBjb;
    private Unit $unitBrb;

    private User $userBjb;
    private User $userSgg;
    private User $userBrb;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup 3 Perusahaan
        $this->sgg = Company::create(['id' => 1, 'name' => 'PT Syifa Global Group', 'code' => 'SGG', 'kode_surat' => 'SGG']);
        $this->rsusmBjb = Company::create(['id' => 5, 'name' => 'RSU Syifa Medika Banjarbaru', 'code' => 'RSUSMBJB', 'kode_surat' => 'RSUSM']);
        $this->rsusmBrb = Company::create(['id' => 6, 'name' => 'RSU Syifa Medika Barabai', 'code' => 'RSUSMBRB', 'kode_surat' => 'RSUSM-BRB']);

        // 2. Setup Departemen & Unit per Perusahaan
        $deptSgg = Department::create(['company_id' => $this->sgg->id, 'name' => 'Umum SGG', 'code' => 'UMM']);
        $this->unitSgg = Unit::create(['company_id' => $this->sgg->id, 'department_id' => $deptSgg->id, 'name' => 'Direksi SGG', 'kode' => 'DIR', 'prefix' => 'DIR', 'is_active' => true]);

        $deptBjb = Department::create(['company_id' => $this->rsusmBjb->id, 'name' => 'Pelayanan Medik BJB', 'code' => 'YANMED']);
        $this->unitBjb = Unit::create(['company_id' => $this->rsusmBjb->id, 'department_id' => $deptBjb->id, 'name' => 'Laboratorium BJB', 'kode' => '16', 'prefix' => '16', 'is_active' => true]);

        $deptBrb = Department::create(['company_id' => $this->rsusmBrb->id, 'name' => 'Umum Barabai', 'code' => 'UMM-BRB']);
        $this->unitBrb = Unit::create(['company_id' => $this->rsusmBrb->id, 'department_id' => $deptBrb->id, 'name' => 'Kepegawaian Barabai', 'kode' => '7', 'prefix' => '7', 'is_active' => true]);

        // 3. Permissions
        Permission::findOrCreate('ViewAny:LetterNumber', 'web');
        Permission::findOrCreate('Create:LetterNumber', 'web');
        Permission::findOrCreate('ViewAny:Document', 'web');
        Permission::findOrCreate('Create:Document', 'web');

        // 4. Setup User per Perusahaan
        $this->userBjb = User::create([
            'name' => 'Petugas Banjarbaru',
            'username' => 'petugas_bjb',
            'email' => 'bjb@syifa.test',
            'password' => 'password',
            'company_id' => $this->rsusmBjb->id,
            'department_id' => $deptBjb->id,
            'unit_id' => $this->unitBjb->id,
            'is_active' => true,
        ]);

        $this->userSgg = User::create([
            'name' => 'Petugas SGG',
            'username' => 'petugas_sgg',
            'email' => 'sgg@syifa.test',
            'password' => 'password',
            'company_id' => $this->sgg->id,
            'department_id' => $deptSgg->id,
            'unit_id' => $this->unitSgg->id,
            'is_active' => true,
        ]);

        $this->userBrb = User::create([
            'name' => 'Petugas Barabai',
            'username' => 'petugas_brb',
            'email' => 'brb@syifa.test',
            'password' => 'password',
            'company_id' => $this->rsusmBrb->id,
            'department_id' => $deptBrb->id,
            'unit_id' => $this->unitBrb->id,
            'is_active' => true,
        ]);
    }

    public function test_penomoran_otomatis_mengikuti_perusahaan_banjarbaru(): void
    {
        $this->actingAs($this->userBjb);
        $this->userBjb->givePermissionTo(['ViewAny:LetterNumber', 'Create:LetterNumber']);

        // Default unit otomatis milik Banjarbaru
        $this->assertSame($this->unitBjb->id, LetterNumberForm::defaultUnitId());

        // Pilihan unit hanya menampilkan unit RSUSM Banjarbaru
        $unitOptions = LetterNumberForm::unitOptions();
        $this->assertArrayHasKey($this->unitBjb->id, $unitOptions);
        $this->assertArrayNotHasKey($this->unitSgg->id, $unitOptions);
        $this->assertArrayNotHasKey($this->unitBrb->id, $unitOptions);

        // Buat kategori untuk Banjarbaru
        $catBjb = DocumentCategory::create([
            'company_id' => $this->rsusmBjb->id,
            'name' => 'Surat Edaran',
            'prefix' => 'D',
            'code' => 'D',
            'is_active' => true,
        ]);

        $letter = LetterNumberForm::issue([
            'unit_id' => $this->unitBjb->id,
            'category_id' => $catBjb->id,
            'tanggal_surat' => '2026-11-18',
            'judul_surat' => 'Surat Edaran Direktur RSUSM',
        ]);

        // Nomor otomatis berlabel RSUSM
        $this->assertStringContainsString('RSUSM', $letter->nomor);
        $this->assertSame($this->rsusmBjb->id, $letter->company_id);
    }

    public function test_penomoran_otomatis_mengikuti_perusahaan_sgg(): void
    {
        $this->actingAs($this->userSgg);
        $this->userSgg->givePermissionTo(['ViewAny:LetterNumber', 'Create:LetterNumber']);

        $this->assertSame($this->unitSgg->id, LetterNumberForm::defaultUnitId());

        $unitOptions = LetterNumberForm::unitOptions();
        $this->assertArrayHasKey($this->unitSgg->id, $unitOptions);
        $this->assertArrayNotHasKey($this->unitBjb->id, $unitOptions);
        $this->assertArrayNotHasKey($this->unitBrb->id, $unitOptions);

        $catSgg = DocumentCategory::create([
            'company_id' => $this->sgg->id,
            'name' => 'Kebijakan Direktur SGG',
            'prefix' => 'A',
            'code' => 'A',
            'is_active' => true,
        ]);

        $letter = LetterNumberForm::issue([
            'unit_id' => $this->unitSgg->id,
            'category_id' => $catSgg->id,
            'tanggal_surat' => '2026-11-18',
            'judul_surat' => 'Kebijakan Perusahaan SGG',
        ]);

        $this->assertStringContainsString('SGG', $letter->nomor);
        $this->assertSame($this->sgg->id, $letter->company_id);
    }

    public function test_penomoran_otomatis_mengikuti_perusahaan_barabai(): void
    {
        $this->actingAs($this->userBrb);
        $this->userBrb->givePermissionTo(['ViewAny:LetterNumber', 'Create:LetterNumber']);

        $this->assertSame($this->unitBrb->id, LetterNumberForm::defaultUnitId());

        $unitOptions = LetterNumberForm::unitOptions();
        $this->assertArrayHasKey($this->unitBrb->id, $unitOptions);
        $this->assertArrayNotHasKey($this->unitSgg->id, $unitOptions);
        $this->assertArrayNotHasKey($this->unitBjb->id, $unitOptions);

        $catBrb = DocumentCategory::create([
            'company_id' => $this->rsusmBrb->id,
            'name' => 'Surat Tugas Barabai',
            'prefix' => 'G',
            'code' => 'G',
            'is_active' => true,
        ]);

        $letter = LetterNumberForm::issue([
            'unit_id' => $this->unitBrb->id,
            'category_id' => $catBrb->id,
            'tanggal_surat' => '2026-11-18',
            'judul_surat' => 'Penugasan Barabai',
        ]);

        $this->assertStringContainsString('RSUSM-BRB', $letter->nomor);
        $this->assertSame($this->rsusmBrb->id, $letter->company_id);
    }

    public function test_user_tanpa_hak_akses_penomoran_dokumen_ditolak(): void
    {
        // User Banjarbaru tanpa permission Create:LetterNumber
        $this->actingAs($this->userBjb);

        $catBjb = DocumentCategory::create([
            'company_id' => $this->rsusmBjb->id,
            'name' => 'Surat Edaran',
            'prefix' => 'D',
            'code' => 'D',
            'is_active' => true,
        ]);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Anda tidak memiliki hak akses untuk menerbitkan nomor dokumen.');

        LetterNumberForm::issue([
            'unit_id' => $this->unitBjb->id,
            'category_id' => $catBjb->id,
            'tanggal_surat' => '2026-11-18',
            'judul_surat' => 'Coba Terbitkan Nomor',
        ]);
    }

    public function test_resource_nomor_dokumen_hanya_bisa_diakses_oleh_user_dengan_permission(): void
    {
        // Tanpa permission ViewAny:LetterNumber
        $this->actingAs($this->userBjb);
        $this->assertFalse(LetterNumberResource::canViewAny());

        // Berikan permission ViewAny:LetterNumber
        $this->userBjb->givePermissionTo('ViewAny:LetterNumber');
        $this->assertTrue(LetterNumberResource::canViewAny());
    }

    public function test_surat_keluar_query_unit_otomatis_mengikuti_perusahaan_user(): void
    {
        $this->actingAs($this->userBjb);
        $unitsBjb = ListSuratKeluar::queryUnitSiap($this->userBjb)->pluck('id')->toArray();

        $this->assertContains($this->unitBjb->id, $unitsBjb);
        $this->assertNotContains($this->unitSgg->id, $unitsBjb);
        $this->assertNotContains($this->unitBrb->id, $unitsBjb);

        $this->actingAs($this->userBrb);
        $unitsBrb = ListSuratKeluar::queryUnitSiap($this->userBrb)->pluck('id')->toArray();

        $this->assertContains($this->unitBrb->id, $unitsBrb);
        $this->assertNotContains($this->unitBjb->id, $unitsBrb);
        $this->assertNotContains($this->unitSgg->id, $unitsBrb);
    }

    public function test_user_biasa_tidak_bisa_mengubah_unit_dan_terkunci_ke_unit_akun(): void
    {
        $this->actingAs($this->userBjb);
        $this->userBjb->givePermissionTo(['ViewAny:LetterNumber', 'Create:LetterNumber']);

        // Komponen form: cek apakah unit_id dinonaktifkan (disabled)
        $components = LetterNumberForm::components();
        $unitSelect = collect($components)->first(fn ($c) => method_exists($c, 'getName') && $c->getName() === 'unit_id');

        $this->assertNotNull($unitSelect);
        $this->assertTrue($unitSelect->isDisabled());

        // Jika user mencoba memanipulasi pengiriman unit_id ke unit SGG, backend memaksanya tetap unit Banjarbaru
        $catBjb = DocumentCategory::create([
            'company_id' => $this->rsusmBjb->id,
            'name' => 'SPO Banjarbaru',
            'prefix' => 'C',
            'code' => 'C',
            'is_active' => true,
        ]);

        $letter = LetterNumberForm::issue([
            'unit_id' => $this->unitSgg->id, // Mencoba manipulasi kirim unit lain
            'category_id' => $catBjb->id,
            'tanggal_surat' => '2026-11-18',
            'judul_surat' => 'Tes Kunci Unit',
        ]);

        // Tetap unit Banjarbaru (16), bukan SGG
        $this->assertSame($this->unitBjb->id, $letter->unit_id);
        $this->assertStringContainsString('RSUSM', $letter->nomor);
    }
}

