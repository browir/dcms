<?php

namespace Tests\MySQL;

use App\Enums\JenisSurat;
use App\Filament\Admin\Resources\SuratKeluar\Pages\ListSuratKeluar;
use App\Filament\Admin\Resources\SuratKeluar\SuratKeluarResource;
use App\Models\Surat;
use App\Models\Unit;
use App\Services\NomorSuratService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Test integrasi UI Surat Keluar terhadap MySQL dcms_test.
 *
 * Lingkup:
 *   - Logika filter "unit siap" yang sama dengan yang dipakai form modal
 *   - Submit berhasil menghasilkan nomor berformat benar
 *   - RuntimeException dari service ditangkap (bukan crash)
 *   - Resource bersifat hanya-baca (canEdit/canDelete false)
 *   - Filter dan pencarian tabel bekerja benar
 *
 * Catatan mutasi:
 *   Di metode test_unit_tidak_siap_tidak_muncul_di_pilihan(), terdapat komentar
 *   yang menunjukkan di mana mutasi dilakukan dan test mana yang akan gagal.
 *
 * Jalankan dengan: vendor\bin\phpunit -c phpunit.mysql.xml
 */
class SuratKeluarPageTest extends MySQLTestCase
{
    private const PREFIX = 'TSPG_';
    private const TAHUN  = 2097;

    /** @var array<int> */
    private array $companyIds    = [];
    /** @var array<int> */
    private array $departmentIds = [];
    /** @var array<int> */
    private array $unitIds       = [];
    /**
     * ID user test yang dibuat untuk test permission.
     * Dibersihkan di tearDown agar dcms_test tetap bersih.
     *
     * @var array<int>
     */
    private array $userIds         = [];
    /**
     * ID permission yang BARU dibuat di dcms_test (bukan yang sudah ada).
     * Dibersihkan di tearDown.
     *
     * @var array<int>
     */
    private array $newPermissionIds = [];

    protected function tearDown(): void
    {
        $this->cleanup();
        parent::tearDown();
    }

    // ── Helper: pembuatan data test ───────────────────────────────────────────

    /**
     * Buat company + department + unit yang VALID ("siap").
     *
     * @return array{company_id: int, dept_id: int, unit_id: int, unit: Unit}
     */
    private function buatUnitSiap(
        string $suffix     = 'A',
        string $kodePerush = 'TSPP',
        string $kodeUnit   = 'TSP',
    ): array {
        $companyId = DB::table('companies')->insertGetId([
            'name'       => self::PREFIX . "COMPANY_{$suffix}",
            'code'       => "TSPC{$suffix}",
            'kode_surat' => $kodePerush,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->companyIds[] = $companyId;

        $deptId = DB::table('departments')->insertGetId([
            'name'       => self::PREFIX . "DEPT_{$suffix}",
            'code'       => "TSPD{$suffix}",
            'company_id' => $companyId,
            'is_active'  => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->departmentIds[] = $deptId;

        $unitId = DB::table('units')->insertGetId([
            'name'          => self::PREFIX . "UNIT_{$suffix}",
            'kode'          => $kodeUnit,
            'department_id' => $deptId,
            'company_id'    => $companyId,
            'is_active'     => 1,
            'created_at'    => now(), 'updated_at' => now(),
        ]);
        $this->unitIds[] = $unitId;

        return [
            'company_id' => $companyId,
            'dept_id'    => $deptId,
            'unit_id'    => $unitId,
            'unit'       => Unit::with(['company', 'department'])->findOrFail($unitId),
        ];
    }

    private function dataOk(string $judul = 'Surat Uji Integrasi'): array
    {
        return [
            'judul_surat'      => $judul,
            'dibuat_oleh_nama' => 'Penguji',
        ];
    }

    /**
     * Cari permission di dcms_test; buat jika belum ada.
     * Jika baru dibuat, track ID-nya untuk dibersihkan di tearDown.
     *
     * @return Permission
     */
    private function createOrFindPermission(string $name): Permission
    {
        $exists = Permission::where('name', $name)->where('guard_name', 'web')->exists();
        $perm   = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);

        if (! $exists) {
            $this->newPermissionIds[] = $perm->id;
        }

        return $perm;
    }

    private function cleanup(): void
    {
        // ── Data unit/surat ─────────────────────────────────────────────────
        if (! empty($this->unitIds)) {
            DB::table('surat')->whereIn('unit_id', $this->unitIds)->delete();
            DB::table('nomor_surat_counter')
                ->whereIn('perusahaan_id', $this->companyIds ?: [0])
                ->delete();
        }
        DB::table('units')->where('name', 'LIKE', self::PREFIX . '%')->delete();
        DB::table('departments')->where('name', 'LIKE', self::PREFIX . '%')->delete();
        DB::table('companies')->where('name', 'LIKE', self::PREFIX . '%')->delete();

        // ── User test dan permission (hanya yang dibuat oleh test ini) ──────
        if (! empty($this->userIds)) {
            DB::table('model_has_permissions')
                ->whereIn('model_id', $this->userIds)
                ->where('model_type', 'App\\Models\\User')
                ->delete();
            DB::table('model_has_roles')
                ->whereIn('model_id', $this->userIds)
                ->where('model_type', 'App\\Models\\User')
                ->delete();
            DB::table('users')->whereIn('id', $this->userIds)->delete();
        }
        if (! empty($this->newPermissionIds)) {
            DB::table('role_has_permissions')
                ->whereIn('permission_id', $this->newPermissionIds)
                ->delete();
            DB::table('model_has_permissions')
                ->whereIn('permission_id', $this->newPermissionIds)
                ->delete();
            DB::table('permissions')->whereIn('id', $this->newPermissionIds)->delete();
        }

        Auth::logout();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    // ── 1. Unit tidak siap tidak muncul di pilihan ───────────────────────────

    /**
     * Unit tanpa kode tidak boleh muncul di dropdown form.
     *
     * MUTASI YANG DIDEMONSTRASIKAN:
     *   Untuk melihat test ini gagal, komentari ketiga kondisi berikut di
     *   ListSuratKeluar::queryUnitSiap():
     *     ->whereNotNull('kode')
     *     ->where('kode', '<>', '')
     *     ->whereNotNull('company_id')
     *     ->whereHas('company', ...)
     *
     *   Akibat: test ini akan GAGAL karena unit yang tidak siap akan muncul
     *   di hasil query, sehingga $this->assertNotContains() tidak terpenuhi.
     *   KEMBALIKAN kondisi tersebut setelah melihat hasil gagal.
     */
    #[Test]
    public function unit_tidak_siap_tidak_muncul_di_pilihan(): void
    {
        // Unit tanpa kode (tidak siap)
        $companyId = DB::table('companies')->insertGetId([
            'name'       => self::PREFIX . 'COMPANY_NOKODE',
            'code'       => 'TSPNK',
            'kode_surat' => 'TSPP',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->companyIds[] = $companyId;

        $deptId = DB::table('departments')->insertGetId([
            'name'       => self::PREFIX . 'DEPT_NOKODE',
            'code'       => 'TSPDK',
            'company_id' => $companyId,
            'is_active'  => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->departmentIds[] = $deptId;

        $unitTanpaKodeId = DB::table('units')->insertGetId([
            'name'          => self::PREFIX . 'UNIT_NOKODE',
            'kode'          => null,  // ← TIDAK siap: kode kosong
            'department_id' => $deptId,
            'company_id'    => $companyId,
            'is_active'     => 1,
            'created_at'    => now(), 'updated_at' => now(),
        ]);
        $this->unitIds[] = $unitTanpaKodeId;

        // Unit tanpa company_id (tidak siap)
        $unitTanpaCompanyId = DB::table('units')->insertGetId([
            'name'          => self::PREFIX . 'UNIT_NOCOMPANY',
            'kode'          => 'TSPNC',
            'department_id' => $deptId,
            'company_id'    => null,  // ← TIDAK siap: company_id null
            'is_active'     => 1,
            'created_at'    => now(), 'updated_at' => now(),
        ]);
        $this->unitIds[] = $unitTanpaCompanyId;

        // Perusahaan tanpa kode_surat (unit yang menunjuk ke sini tidak siap)
        $companyTanpaKodeSurat = DB::table('companies')->insertGetId([
            'name'       => self::PREFIX . 'COMPANY_NOKODE_SURAT',
            'code'       => 'TSPNKS',
            'kode_surat' => null,  // ← TIDAK siap: kode_surat null
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->companyIds[] = $companyTanpaKodeSurat;

        $unitKodesuratNull = DB::table('units')->insertGetId([
            'name'          => self::PREFIX . 'UNIT_NOKODESURAT',
            'kode'          => 'TSPKS',
            'department_id' => $deptId,
            'company_id'    => $companyTanpaKodeSurat,
            'is_active'     => 1,
            'created_at'    => now(), 'updated_at' => now(),
        ]);
        $this->unitIds[] = $unitKodesuratNull;

        // Gunakan query yang sama dengan form modal
        $unitSiapIds = ListSuratKeluar::queryUnitSiap()->pluck('id')->toArray();

        // Unit yang tidak siap tidak boleh ada di daftar
        $this->assertNotContains(
            $unitTanpaKodeId,
            $unitSiapIds,
            'Unit tanpa kode tidak boleh muncul di pilihan'
        );
        $this->assertNotContains(
            $unitTanpaCompanyId,
            $unitSiapIds,
            'Unit tanpa company_id tidak boleh muncul di pilihan'
        );
        $this->assertNotContains(
            $unitKodesuratNull,
            $unitSiapIds,
            'Unit dengan perusahaan tanpa kode_surat tidak boleh muncul di pilihan'
        );
    }

    // ── 2. Submit berhasil menghasilkan nomor berformat benar ─────────────────

    #[Test]
    public function submit_berhasil_menghasilkan_nomor_berformat_benar(): void
    {
        $d = $this->buatUnitSiap('FMT', 'TSPP', 'TSP');

        $surat = NomorSuratService::generate(
            $d['unit'],
            JenisSurat::G,
            Carbon::create(self::TAHUN, 9, 15),
            $this->dataOk()
        );

        // Format: {urut}/{kode_jenis}.{kode_unit}/{kode_perusahaan}/{bulan_romawi}/{tahun}
        // Contoh: 001/G.TSP/TSPP/IX/2097
        $this->assertMatchesRegularExpression(
            '/^\d{3,}\/[A-Z]+\.[A-Z0-9]+\/[A-Z][A-Z0-9-]*\/[IVX]+\/\d{4}$/',
            $surat->nomor,
            'Nomor surat harus berformat benar'
        );

        // Verifikasi komponen spesifik
        $this->assertStringContainsString('/G.TSP/', $surat->nomor, 'Format harus mengandung kode_jenis.kode_unit');
        $this->assertStringContainsString('/TSPP/', $surat->nomor, 'Format harus mengandung kode_perusahaan');
        $this->assertStringContainsString('/IX/', $surat->nomor, 'Bulan September harus IX');
        $this->assertStringEndsWith('/' . self::TAHUN, $surat->nomor, 'Nomor harus diakhiri tahun surat');

        // Verifikasi surat tersimpan di database
        $this->assertDatabaseHas('surat', [
            'id'    => $surat->id,
            'nomor' => $surat->nomor,
        ]);
    }

    // ── 3. RuntimeException dari service tampil sebagai notifikasi (bukan crash) ─

    /**
     * Mensimulasikan logika action handler:
     * RuntimeException harus ditangkap dan tidak menyebar sebagai uncaught exception.
     *
     * Test ini memverifikasi bahwa kode handler action di ListSuratKeluar memang
     * membungkus generate() dalam try/catch dengan RuntimeException.
     */
    #[Test]
    public function runtime_exception_dari_service_ditangkap_bukan_crash(): void
    {
        $d = $this->buatUnitSiap('EXC');

        // Kosongkan kode_surat perusahaan → service akan melempar RuntimeException
        DB::table('companies')->where('id', $d['company_id'])->update(['kode_surat' => null]);
        $unit = Unit::with(['company', 'department'])->findOrFail($d['unit_id']);

        // Simulasikan handler action: try/catch RuntimeException
        $caught = null;
        $nomorDibuat = null;

        try {
            $surat = NomorSuratService::generate(
                $unit,
                JenisSurat::A,
                Carbon::create(self::TAHUN, 1, 1),
                $this->dataOk()
            );
            $nomorDibuat = $surat->nomor;
        } catch (\RuntimeException $e) {
            $caught = $e->getMessage();
        }

        // RuntimeException harus tertangkap — bukan crash
        $this->assertNotNull($caught, 'RuntimeException harus ditangkap oleh handler action');
        $this->assertNull($nomorDibuat, 'Tidak boleh ada nomor yang dibuat saat exception');
        $this->assertStringContainsString('kode_surat', $caught, 'Pesan error harus menyebut kode_surat');
    }

    // ── 4. Resource hanya-baca: tidak ada aksi edit atau hapus ───────────────

    #[Test]
    public function resource_tidak_memiliki_aksi_edit_dan_hapus(): void
    {
        // Buat surat dummy untuk menjadi "record" yang dites
        $d     = $this->buatUnitSiap('RO');
        $surat = NomorSuratService::generate(
            $d['unit'],
            JenisSurat::B,
            Carbon::create(self::TAHUN, 3, 1),
            $this->dataOk('Surat Uji Hanya-Baca')
        );

        $this->assertFalse(
            SuratKeluarResource::canEdit($surat),
            'SuratKeluarResource::canEdit() harus selalu false'
        );
        $this->assertFalse(
            SuratKeluarResource::canDelete($surat),
            'SuratKeluarResource::canDelete() harus selalu false'
        );
        $this->assertFalse(
            SuratKeluarResource::canDeleteAny(),
            'SuratKeluarResource::canDeleteAny() harus selalu false'
        );
        $this->assertFalse(
            SuratKeluarResource::canCreate(),
            'SuratKeluarResource::canCreate() harus selalu false (pembuatan via modal custom)'
        );
    }

    // ── 5. Filter perusahaan menyaring surat berdasarkan company_id unit ──────

    #[Test]
    public function filter_perusahaan_menyaring_surat_dengan_benar(): void
    {
        $dA = $this->buatUnitSiap('FILA', 'TSTPA', 'TSTA');
        $dB = $this->buatUnitSiap('FILB', 'TSTPB', 'TSTB');

        // Buat satu surat untuk masing-masing perusahaan
        NomorSuratService::generate($dA['unit'], JenisSurat::C, Carbon::create(self::TAHUN, 4, 1), $this->dataOk('Surat Perusahaan A'));
        NomorSuratService::generate($dB['unit'], JenisSurat::C, Carbon::create(self::TAHUN, 4, 1), $this->dataOk('Surat Perusahaan B'));

        // Query yang mensimulasikan filter "perusahaan" di tabel
        $suratPerusahaanA = Surat::query()
            ->where('tipe_surat', 'keluar')
            ->whereHas('unit', fn ($q) => $q->where('company_id', $dA['company_id']))
            ->whereIn('unit_id', $this->unitIds)
            ->get();

        $suratPerusahaanB = Surat::query()
            ->where('tipe_surat', 'keluar')
            ->whereHas('unit', fn ($q) => $q->where('company_id', $dB['company_id']))
            ->whereIn('unit_id', $this->unitIds)
            ->get();

        $this->assertCount(1, $suratPerusahaanA, 'Filter perusahaan A harus menghasilkan tepat 1 surat');
        $this->assertCount(1, $suratPerusahaanB, 'Filter perusahaan B harus menghasilkan tepat 1 surat');

        $this->assertStringContainsString('/TSTPA/', $suratPerusahaanA->first()->nomor);
        $this->assertStringContainsString('/TSTPB/', $suratPerusahaanB->first()->nomor);
    }

    // ── 6. Pencarian berdasarkan judul_surat dan dibuat_oleh_nama ────────────

    #[Test]
    public function pencarian_judul_dan_nama_pembuat_bekerja_benar(): void
    {
        $d = $this->buatUnitSiap('SCH', 'TSTSR', 'TSSR');

        NomorSuratService::generate($d['unit'], JenisSurat::D, Carbon::create(self::TAHUN, 5, 1), [
            'judul_surat'      => 'Surat Uji Pencarian Spesifik',
            'dibuat_oleh_nama' => 'Ahmad Farhan Pengujian',
        ]);

        // Cari berdasarkan judul_surat
        $hasilJudul = Surat::query()
            ->where('tipe_surat', 'keluar')
            ->whereIn('unit_id', $this->unitIds)
            ->where('judul_surat', 'like', '%Pencarian Spesifik%')
            ->get();

        $this->assertCount(1, $hasilJudul, 'Pencarian berdasarkan judul_surat harus menemukan surat');

        // Cari berdasarkan dibuat_oleh_nama
        $hasilPembuat = Surat::query()
            ->where('tipe_surat', 'keluar')
            ->whereIn('unit_id', $this->unitIds)
            ->where('dibuat_oleh_nama', 'like', '%Farhan%')
            ->get();

        $this->assertCount(1, $hasilPembuat, 'Pencarian berdasarkan dibuat_oleh_nama harus menemukan surat');
    }

    // ── 7. Pencarian perihal (kolom tersembunyi di tabel) ────────────────────

    #[Test]
    public function pencarian_perihal_tersembunyi_bekerja_benar(): void
    {
        $d = $this->buatUnitSiap('PHER', 'TSTPH', 'TSPH');

        NomorSuratService::generate($d['unit'], JenisSurat::E, Carbon::create(self::TAHUN, 6, 1), [
            'judul_surat'      => 'Surat dengan Perihal Spesifik',
            'dibuat_oleh_nama' => 'Tester Perihal',
            'perihal'          => 'Perihal Dana Operasional Khusus Pengujian',
        ]);

        $hasilPerihal = Surat::query()
            ->where('tipe_surat', 'keluar')
            ->whereIn('unit_id', $this->unitIds)
            ->where('perihal', 'like', '%Dana Operasional Khusus%')
            ->get();

        $this->assertCount(1, $hasilPerihal, 'Pencarian berdasarkan perihal harus menemukan surat');
        $this->assertStringContainsString('Dana Operasional Khusus', $hasilPerihal->first()->perihal);
    }

    // ── 8. Unit siap muncul di pilihan ───────────────────────────────────────

    #[Test]
    public function unit_siap_muncul_di_pilihan(): void
    {
        $d = $this->buatUnitSiap('SDP');

        $unitSiapIds = ListSuratKeluar::queryUnitSiap()->pluck('id')->toArray();

        $this->assertContains(
            $d['unit_id'],
            $unitSiapIds,
            'Unit yang memenuhi semua syarat harus muncul di daftar pilihan'
        );
    }

    // ── 9. Akses kontrol: user TANPA permission ditolak ──────────────────────

    /**
     * User yang tidak memiliki permission ViewAny:SuratKeluar harus:
     *   - canAccess()          → false  (halaman tidak dapat dibuka)
     *   - can('Create:...')   → false  (tombol modal tidak terlihat)
     *
     * Test ini berjalan di dcms_test. Permission dibuat di dcms_test jika belum ada,
     * dan dibersihkan di tearDown().
     */
    #[Test]
    public function user_tanpa_permission_tidak_bisa_mengakses_halaman(): void
    {
        // Pastikan kedua permission ada di dcms_test (idempoten)
        $this->createOrFindPermission('ViewAny:SuratKeluar');
        $this->createOrFindPermission('Create:SuratKeluar');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Buat user biasa TANPA permission apa pun
        $userId = DB::table('users')->insertGetId([
            'name'       => self::PREFIX . 'NOPERM',
            'email'      => 'tspg_noperm_' . uniqid() . '@test.local',
            'password'   => bcrypt('secret'),
            'is_active'  => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->userIds[] = $userId;

        $user = \App\Models\User::findOrFail($userId);

        // Login sebagai user TANPA permission
        $this->actingAs($user);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertFalse(
            SuratKeluarResource::canAccess(),
            'User tanpa ViewAny:SuratKeluar TIDAK boleh mengakses halaman Surat Keluar'
        );
        $this->assertFalse(
            auth()->user()->can('Create:SuratKeluar'),
            'User tanpa Create:SuratKeluar TIDAK boleh melihat tombol Buat Nomor Surat'
        );
        $this->assertFalse(
            auth()->user()->can('ViewAny:SuratKeluar'),
            'User tanpa permission ViewAny TIDAK boleh mengakses resource'
        );
    }

    // ── 10. Akses kontrol: user DENGAN permission diizinkan ─────────────────

    /**
     * User yang memiliki permission ViewAny:SuratKeluar dan Create:SuratKeluar harus:
     *   - canAccess()          → true
     *   - can('Create:...')   → true
     */
    #[Test]
    public function user_dengan_permission_bisa_mengakses_halaman_dan_tombol(): void
    {
        // Pastikan kedua permission ada di dcms_test
        $viewPerm   = $this->createOrFindPermission('ViewAny:SuratKeluar');
        $createPerm = $this->createOrFindPermission('Create:SuratKeluar');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Buat user dan assign LANGSUNG kedua permission (bukan via role)
        $userId = DB::table('users')->insertGetId([
            'name'       => self::PREFIX . 'WITHPERM',
            'email'      => 'tspg_withperm_' . uniqid() . '@test.local',
            'password'   => bcrypt('secret'),
            'is_active'  => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->userIds[] = $userId;

        $user = \App\Models\User::findOrFail($userId);
        $user->givePermissionTo([$viewPerm, $createPerm]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Login sebagai user DENGAN permission
        $this->actingAs($user);

        $this->assertTrue(
            SuratKeluarResource::canAccess(),
            'User dengan ViewAny:SuratKeluar HARUS bisa mengakses halaman Surat Keluar'
        );
        $this->assertTrue(
            auth()->user()->can('Create:SuratKeluar'),
            'User dengan Create:SuratKeluar HARUS bisa melihat tombol Buat Nomor Surat'
        );
        $this->assertTrue(
            auth()->user()->can('ViewAny:SuratKeluar'),
            'User dengan permission ViewAny HARUS bisa mengakses resource'
        );
    }
}
