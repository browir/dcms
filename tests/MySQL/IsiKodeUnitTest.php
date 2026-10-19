<?php

namespace Tests\MySQL;

use App\Console\Commands\IsiKodeUnitCommand;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test MySQL untuk command surat:isi-kode-unit.
 *
 * Menyiapkan data sendiri di dcms_test dan membersihkannya setelah selesai.
 * TIDAK menyentuh database dcms (production).
 * TIDAK menggunakan RefreshDatabase.
 *
 * Skenario yang diuji:
 *  1. dry-run tidak mengubah apa pun
 *  2. execute mengisi kode dengan benar
 *  3. execute dua kali = idempoten (tidak error, hasil sama)
 *  4. nama ganda (>1 unit cocok) tidak diisi
 *  5. nama tidak cocok tidak diisi
 *  6. kode yang sudah terisi dengan nilai berbeda tidak ditimpa (bentrok)
 *  7. baris tanpa kode_unit_dari_nomor diabaikan
 *  8. perusahaan lama 7 dilewati tanpa error
 */
class IsiKodeUnitTest extends MySQLTestCase
{
    private const PREFIX = 'TIKU_'; // Test Isi Kode Unit

    /** IDs company yang dibuat untuk test ini */
    private int $sggId;
    private int $rsusmId;
    private int $rsusmBrbId;

    /** ID departemen dummy */
    private int $deptSggId;
    private int $deptRsusmId;
    private int $deptRsusmBrbId;

    /** Semua unit IDs yang dibuat, untuk cleanup */
    private array $createdUnitIds = [];

    /** Semua company IDs yang dibuat, untuk cleanup */
    private array $createdCompanyIds = [];

    /** Semua department IDs yang dibuat, untuk cleanup */
    private array $createdDeptIds = [];

    // =========================================================================
    // Setup & Teardown
    // =========================================================================

    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanupStaleData();
        $this->createTestCompanies();
        $this->createTestDepartments();
        $this->createTestUnits();
    }

    protected function tearDown(): void
    {
        $this->cleanup();
        parent::tearDown();
    }

    private function cleanupStaleData(): void
    {
        // Bersihkan data test yang tertinggal dari run sebelumnya
        $staleCompanyIds = DB::table('companies')
            ->where('name', 'like', self::PREFIX . '%')
            ->pluck('id')
            ->toArray();

        if (! empty($staleCompanyIds)) {
            DB::table('units')->whereIn('company_id', $staleCompanyIds)->delete();
            DB::table('departments')->whereIn('company_id', $staleCompanyIds)->delete();
        }
        DB::table('companies')->where('name', 'like', self::PREFIX . '%')->delete();
    }

    private function createTestCompanies(): void
    {
        $uid = substr(uniqid(), -6);

        $this->sggId = DB::table('companies')->insertGetId([
            'name'       => self::PREFIX . 'SGG',
            'code'       => 'TIKU_S_' . $uid,
            'kode_surat' => null,
            'is_active'  => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->rsusmId = DB::table('companies')->insertGetId([
            'name'       => self::PREFIX . 'RSUSM',
            'code'       => 'TIKU_R_' . $uid,
            'kode_surat' => null,
            'is_active'  => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->rsusmBrbId = DB::table('companies')->insertGetId([
            'name'       => self::PREFIX . 'RSUSM-BRB',
            'code'       => 'TIKU_B_' . $uid,
            'kode_surat' => null,
            'is_active'  => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createdCompanyIds = [$this->sggId, $this->rsusmId, $this->rsusmBrbId];
    }

    private function createTestDepartments(): void
    {
        $uid = substr(uniqid(), -4);

        $this->deptSggId = DB::table('departments')->insertGetId([
            'name'       => self::PREFIX . 'DeptSGG',
            'code'       => 'TIKUDS_' . $uid,
            'company_id' => $this->sggId,
            'is_active'  => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->deptRsusmId = DB::table('departments')->insertGetId([
            'name'       => self::PREFIX . 'DeptRSUSM',
            'code'       => 'TIKUDR_' . $uid,
            'company_id' => $this->rsusmId,
            'is_active'  => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->deptRsusmBrbId = DB::table('departments')->insertGetId([
            'name'       => self::PREFIX . 'DeptBRB',
            'code'       => 'TIKUDB_' . $uid,
            'company_id' => $this->rsusmBrbId,
            'is_active'  => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createdDeptIds = [$this->deptSggId, $this->deptRsusmId, $this->deptRsusmBrbId];
    }

    private function createTestUnits(): void
    {
        $units = [
            // --- SGG units (name tanpa PREFIX agar bisa dicocokkan dari CSV) ---
            ['name' => self::PREFIX . 'Accounting',                            'kode' => null, 'company_id' => $this->sggId,      'dept_id' => $this->deptSggId],
            ['name' => self::PREFIX . 'President Director',                    'kode' => null, 'company_id' => $this->sggId,      'dept_id' => $this->deptSggId],
            ['name' => self::PREFIX . 'Industrial Relation & General Affair',  'kode' => null, 'company_id' => $this->sggId,      'dept_id' => $this->deptSggId],
            // Unit ganda (nama sama, company sama) — 2 baris untuk uji ganda
            ['name' => self::PREFIX . 'Ganda Unit',                            'kode' => null, 'company_id' => $this->sggId,      'dept_id' => $this->deptSggId],
            ['name' => self::PREFIX . 'Ganda Unit',                            'kode' => null, 'company_id' => $this->sggId,      'dept_id' => $this->deptSggId],
            // Unit dengan kode sudah terisi (untuk uji bentrok)
            ['name' => self::PREFIX . 'Bentrok Unit',                          'kode' => 'XX', 'company_id' => $this->sggId,      'dept_id' => $this->deptSggId],
            // --- RSUSM units ---
            ['name' => self::PREFIX . 'Administrasi dan Umum',                 'kode' => null, 'company_id' => $this->rsusmId,    'dept_id' => $this->deptRsusmId],
            // --- RSUSM-BRB units ---
            ['name' => self::PREFIX . 'Direktur',                              'kode' => null, 'company_id' => $this->rsusmBrbId, 'dept_id' => $this->deptRsusmBrbId],
        ];

        foreach ($units as $u) {
            $id = DB::table('units')->insertGetId([
                'name'          => $u['name'],
                'kode'          => $u['kode'],
                'company_id'    => $u['company_id'],
                'department_id' => $u['dept_id'],
                'is_active'     => 1,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
            $this->createdUnitIds[] = $id;
        }
    }

    private function cleanup(): void
    {
        DB::table('units')->whereIn('id', $this->createdUnitIds)->delete();
        DB::table('departments')->whereIn('id', $this->createdDeptIds)->delete();
        DB::table('companies')->whereIn('id', $this->createdCompanyIds)->delete();

        $this->createdUnitIds    = [];
        $this->createdDeptIds    = [];
        $this->createdCompanyIds = [];
    }

    // =========================================================================
    // Helper: buat CSV fixture sementara dan jalankan command
    // =========================================================================

    private function makeTempCsv(array $rows): string
    {
        $path = sys_get_temp_dir() . '/tiku_test_' . uniqid() . '.csv';
        $f = fopen($path, 'w');
        fputcsv($f, ['unit_id_lama', 'nama_unit', 'departemen', 'perusahaan_id_lama', 'kode_unit_dari_nomor', 'jumlah_surat', 'catatan']);
        foreach ($rows as $row) {
            fputcsv($f, $row);
        }
        fclose($f);
        return $path;
    }

    /**
     * Jalankan IsiKodeUnitTestableCommand menggunakan Symfony CommandTester.
     * Ini menyiapkan input/output context yang diperlukan command tanpa
     * membutuhkan Laravel container resolution untuk constructor args.
     *
     * @return int Exit code (Command::SUCCESS = 0)
     */
    private function runCommand(string $mode, array $csvRows): int
    {
        $csvPath = $this->makeTempCsv($csvRows);

        $command = new IsiKodeUnitTestableCommand(
            sggId: $this->sggId,
            rsusmId: $this->rsusmId,
            rsusmBrbId: $this->rsusmBrbId,
        );
        $command->setLaravel($this->app);

        $tester = new \Symfony\Component\Console\Tester\CommandTester($command);

        $params = ['--file' => $csvPath];
        if ($mode === 'execute') {
            $params['--execute'] = true;
        } else {
            $params['--dry-run'] = true;
        }

        return $tester->execute($params);
    }

    // =========================================================================
    // Helper: ambil kode unit dari DB
    // =========================================================================

    private function getUnitKode(string $name, int $companyId): ?string
    {
        return DB::table('units')
            ->where('name', $name)
            ->where('company_id', $companyId)
            ->value('kode');
    }

    private function resetKode(string $name, int $companyId): void
    {
        DB::table('units')
            ->where('name', $name)
            ->where('company_id', $companyId)
            ->update(['kode' => null]);
    }

    // =========================================================================
    // Tests
    // =========================================================================

    #[Test]
    public function dry_run_tidak_mengubah_database(): void
    {
        $csvRows = [
            // [unit_id_lama, nama_unit, departemen, perusahaan_id_lama, kode_unit_dari_nomor, jumlah, catatan]
            [34, self::PREFIX . 'Accounting',         'Dept', 1, '5', 25, ''],
            [35, self::PREFIX . 'President Director', 'Dept', 1, '1',  5, ''],
        ];

        $exitCode = $this->runCommand('dry-run', $csvRows);
        $this->assertEquals(0, $exitCode, 'Command harus sukses');

        // Verifikasi TIDAK ada perubahan di DB
        $this->assertNull(
            $this->getUnitKode(self::PREFIX . 'Accounting', $this->sggId),
            'dry-run seharusnya tidak mengubah kode Accounting'
        );
        $this->assertNull(
            $this->getUnitKode(self::PREFIX . 'President Director', $this->sggId),
            'dry-run seharusnya tidak mengubah kode President Director'
        );
    }

    #[Test]
    public function execute_mengisi_kode_dengan_benar(): void
    {
        $csvRows = [
            [34, self::PREFIX . 'Accounting',                            'Dept', 1, '5',  25, ''],
            [35, self::PREFIX . 'President Director',                    'Dept', 1, '1',   5, ''],
            // "&" harus cocok dengan "&" di nama unit DCMS
            [38, self::PREFIX . 'Industrial Relation & General Affair',  'Dept', 1, '3C', 232, ''],
            // RSUSM (perusahaan lama 2)
            [33, self::PREFIX . 'Administrasi dan Umum',                 'Dept', 2, '5',   1, ''],
            // RSUSM-BRB (perusahaan lama 3)
            [48, self::PREFIX . 'Direktur',                              'Dept', 3, '1', 166, ''],
        ];

        $exitCode = $this->runCommand('execute', $csvRows);
        $this->assertEquals(0, $exitCode);

        $this->assertEquals('5',  $this->getUnitKode(self::PREFIX . 'Accounting',                           $this->sggId),      'Accounting SGG harus kode 5');
        $this->assertEquals('1',  $this->getUnitKode(self::PREFIX . 'President Director',                   $this->sggId),      'President Director SGG harus kode 1');
        $this->assertEquals('3C', $this->getUnitKode(self::PREFIX . 'Industrial Relation & General Affair', $this->sggId),      'IR&GA SGG harus kode 3C');
        $this->assertEquals('5',  $this->getUnitKode(self::PREFIX . 'Administrasi dan Umum',                $this->rsusmId),    'Adm&Umum RSUSM harus kode 5');
        $this->assertEquals('1',  $this->getUnitKode(self::PREFIX . 'Direktur',                             $this->rsusmBrbId), 'Direktur BRB harus kode 1');

        // Cleanup untuk test lain
        $this->resetKode(self::PREFIX . 'Accounting', $this->sggId);
        $this->resetKode(self::PREFIX . 'President Director', $this->sggId);
        $this->resetKode(self::PREFIX . 'Industrial Relation & General Affair', $this->sggId);
        $this->resetKode(self::PREFIX . 'Administrasi dan Umum', $this->rsusmId);
        $this->resetKode(self::PREFIX . 'Direktur', $this->rsusmBrbId);
    }

    #[Test]
    public function execute_dua_kali_idempoten(): void
    {
        $csvRows = [
            [34, self::PREFIX . 'Accounting', 'Dept', 1, '5', 25, ''],
        ];

        // Run pertama
        $exitCode1 = $this->runCommand('execute', $csvRows);
        $this->assertEquals(0, $exitCode1, 'Execute pertama harus sukses');
        $this->assertEquals('5', $this->getUnitKode(self::PREFIX . 'Accounting', $this->sggId));

        // Run kedua — harus tetap sukses dan nilai tidak berubah
        $exitCode2 = $this->runCommand('execute', $csvRows);
        $this->assertEquals(0, $exitCode2, 'Execute kedua harus sukses (idempoten)');
        $this->assertEquals('5', $this->getUnitKode(self::PREFIX . 'Accounting', $this->sggId),
            'Kode tidak boleh berubah setelah execute kedua');

        // Cleanup
        $this->resetKode(self::PREFIX . 'Accounting', $this->sggId);
    }

    #[Test]
    public function nama_ganda_tidak_diisi(): void
    {
        // CSV berisi "TIKU_Ganda Unit" → ada 2 unit dengan nama tsb di sggId
        $csvRows = [
            [999, self::PREFIX . 'Ganda Unit', 'Dept', 1, 'GND', 0, ''],
        ];

        $exitCode = $this->runCommand('execute', $csvRows);
        $this->assertEquals(0, $exitCode);

        // Kedua unit harus tetap NULL
        $kodes = DB::table('units')
            ->where('name', self::PREFIX . 'Ganda Unit')
            ->where('company_id', $this->sggId)
            ->pluck('kode')
            ->toArray();

        $this->assertCount(2, $kodes, 'Harus ada 2 unit Ganda Unit');
        foreach ($kodes as $k) {
            $this->assertNull($k, 'Unit ganda tidak boleh diisi kodenya');
        }
    }

    #[Test]
    public function nama_tidak_cocok_tidak_mengisi_unit_baru(): void
    {
        $csvRows = [
            [998, self::PREFIX . 'Unit Yang Tidak Ada Di DCMS', 'Dept', 1, 'ZZZ', 0, ''],
        ];

        $exitCode = $this->runCommand('execute', $csvRows);
        $this->assertEquals(0, $exitCode);

        // Pastikan tidak ada unit baru yang dibuat
        $count = DB::table('units')
            ->where('name', self::PREFIX . 'Unit Yang Tidak Ada Di DCMS')
            ->count();
        $this->assertEquals(0, $count, 'Tidak boleh membuat unit baru');
    }

    #[Test]
    public function kode_sudah_ada_tidak_ditimpa(): void
    {
        // "TIKU_Bentrok Unit" sudah punya kode 'XX', CSV mencoba mengisi 'YY'
        $csvRows = [
            [997, self::PREFIX . 'Bentrok Unit', 'Dept', 1, 'YY', 0, ''],
        ];

        $exitCode = $this->runCommand('execute', $csvRows);
        $this->assertEquals(0, $exitCode);

        $kode = $this->getUnitKode(self::PREFIX . 'Bentrok Unit', $this->sggId);
        $this->assertEquals('XX', $kode, 'Kode yang sudah ada tidak boleh ditimpa');
    }

    #[Test]
    public function baris_tanpa_kode_diabaikan(): void
    {
        // CSV dengan kode_unit_dari_nomor kosong — harus diabaikan
        $csvRows = [
            [996, self::PREFIX . 'Accounting', 'Dept', 1, '', 0, 'belum pernah dipakai bersurat'],
        ];

        $exitCode = $this->runCommand('execute', $csvRows);
        $this->assertEquals(0, $exitCode);

        $this->assertNull(
            $this->getUnitKode(self::PREFIX . 'Accounting', $this->sggId),
            'Baris tanpa kode_unit_dari_nomor harus diabaikan'
        );
    }

    #[Test]
    public function perusahaan_lama_7_dilewati_tanpa_error(): void
    {
        // Perusahaan lama 7 harus dilewati tanpa error
        $csvRows = [
            [86, 'Akunting', 'Akunting', 7, '5', 1, ''],
        ];

        $exitCode = $this->runCommand('execute', $csvRows);
        $this->assertEquals(0, $exitCode, 'Command harus sukses meskipun ada perusahaan 7');
    }
}

// =============================================================================
// Command versi testable — override peta company agar menunjuk ke test IDs
// =============================================================================

/**
 * Subclass dari IsiKodeUnitCommand yang meng-override getCompanyMap()
 * agar menunjuk ke company ID yang dibuat oleh test (bukan ID hardcoded).
 */
class IsiKodeUnitTestableCommand extends IsiKodeUnitCommand
{
    public function __construct(
        private readonly int $sggId,
        private readonly int $rsusmId,
        private readonly int $rsusmBrbId,
    ) {
        parent::__construct();
    }

    protected function getCompanyMap(): array
    {
        return [
            1 => $this->sggId,      // Perusahaan lama 1 → company test SGG
            2 => $this->rsusmId,    // Perusahaan lama 2 → company test RSUSM
            3 => $this->rsusmBrbId, // Perusahaan lama 3 → company test RSUSM-BRB
            // 7 dilewati (tidak ada di map)
        ];
    }
}
