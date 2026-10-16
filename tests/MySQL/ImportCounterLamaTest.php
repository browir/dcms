<?php

namespace Tests\MySQL;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test MySQL untuk command surat:import-counter-lama.
 *
 * Menggunakan file fixture: tests/fixtures/legacy/audit-nomor-fixture.sql
 * Data fixture menghasilkan nilai yang diharapkan:
 *   SGG 2025       = MAX(counter=305, max_urut=307) = 307
 *   SGG 2026       = MAX(counter=350, max_urut=351) = 351
 *   RSUSM 2025     = MAX(counter=1,   max_urut=1)   = 1
 *   RSUSM-BRB 2026 = MAX(counter=1580,max_urut=1583)= 1583
 *   legacy_id=4 (Koperasi), legacy_id=7 (SMP) -> dilewati
 *
 * ATURAN:
 *   - Tidak boleh menyentuh database dcms (production).
 *   - Setiap test membersihkan data yang dibuatnya sendiri.
 *   - Tidak menggunakan RefreshDatabase.
 */
class ImportCounterLamaTest extends MySQLTestCase
{
    private const PREFIX  = 'TSICL_'; // Test Surat Import Counter Lama
    private const FIXTURE = __DIR__ . '/../fixtures/legacy/audit-nomor-fixture.sql';

    // legacy_id -> company_id di dcms_test (diisi oleh createTestCompanies)
    private int $sggId;
    private int $rsusmId;
    private int $rsusmBrbId;

    /** IDs semua company test, untuk cleanup */
    private array $companyIds = [];

    // =========================================================================
    // Setup & Teardown
    // =========================================================================

    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanupByLegacyIds(); // bersihkan sisa run sebelumnya (jika ada)
        $this->createTestCompanies();
    }

    protected function tearDown(): void
    {
        $this->cleanupCounters();
        $this->cleanupCompanies();
        parent::tearDown();
    }

    /**
     * Bersihkan perusahaan test berdasarkan legacy_id (1,2,3) agar tidak bentrok
     * jika test sebelumnya gagal di tengah jalan.
     */
    private function cleanupByLegacyIds(): void
    {
        $existingIds = DB::table('companies')
            ->whereIn('legacy_id', [1, 2, 3])
            ->pluck('id')
            ->toArray();

        if (! empty($existingIds)) {
            DB::table('nomor_surat_counter')->whereIn('perusahaan_id', $existingIds)->delete();
            DB::table('companies')->whereIn('id', $existingIds)->delete();
        }
    }

    private function createTestCompanies(): void
    {
        // code: UNIQUE NOT NULL (varchar(255)) — pakai prefix pendek + uniqid
        // kode_surat: UNIQUE NULL (varchar(20)) — pakai NULL agar tidak overflow
        $uid = substr(uniqid(), -6);

        $this->sggId = DB::table('companies')->insertGetId([
            'name'       => self::PREFIX . 'SGG',
            'code'       => 'ICL_S_' . $uid,
            'kode_surat' => null,
            'legacy_id'  => 1,
            'is_active'  => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->rsusmId = DB::table('companies')->insertGetId([
            'name'       => self::PREFIX . 'RSUSM',
            'code'       => 'ICL_R_' . $uid,
            'kode_surat' => null,
            'legacy_id'  => 2,
            'is_active'  => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->rsusmBrbId = DB::table('companies')->insertGetId([
            'name'       => self::PREFIX . 'RSUSM_BRB',
            'code'       => 'ICL_B_' . $uid,
            'kode_surat' => null,
            'legacy_id'  => 3,
            'is_active'  => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->companyIds = [$this->sggId, $this->rsusmId, $this->rsusmBrbId];
    }

    private function cleanupCounters(): void
    {
        if (! empty($this->companyIds)) {
            DB::table('nomor_surat_counter')
                ->whereIn('perusahaan_id', $this->companyIds)
                ->delete();
        }
    }

    private function cleanupCompanies(): void
    {
        if (! empty($this->companyIds)) {
            DB::table('companies')
                ->whereIn('id', $this->companyIds)
                ->delete();
        }
    }

    // =========================================================================
    // Test 1: dry-run tidak mengubah database
    // =========================================================================

    #[Test]
    public function dry_run_tidak_mengubah_database(): void
    {
        $this->assertFileExists(self::FIXTURE, 'File fixture tidak ditemukan');

        $this->artisan('surat:import-counter-lama', [
            '--file' => self::FIXTURE,
            // tidak menyetel --execute -> dry-run mode
        ])->assertSuccessful();

        $count = DB::table('nomor_surat_counter')
            ->whereIn('perusahaan_id', $this->companyIds)
            ->count();

        $this->assertSame(
            0,
            $count,
            'dry-run tidak boleh menulis satu pun baris ke nomor_surat_counter'
        );
    }

    // =========================================================================
    // Test 2: execute mengisi counter dengan benar
    // =========================================================================

    #[Test]
    public function execute_mengisi_counter_dengan_benar(): void
    {
        $this->artisan('surat:import-counter-lama', [
            '--file'    => self::FIXTURE,
            '--execute' => true,
        ])->assertSuccessful();

        // SGG 2025: MAX(counter=305, max_urut=307) = 307
        $this->assertCounter($this->sggId, 2025, 307, 'SGG 2025');

        // SGG 2026: MAX(counter=350, max_urut=351) = 351
        $this->assertCounter($this->sggId, 2026, 351, 'SGG 2026');

        // RSUSM 2025: MAX(counter=1, max_urut=1) = 1
        $this->assertCounter($this->rsusmId, 2025, 1, 'RSUSM 2025');

        // RSUSM-BRB 2026: MAX(counter=1580, max_urut=1583) = 1583
        $this->assertCounter($this->rsusmBrbId, 2026, 1583, 'RSUSM-BRB 2026');

        // legacy_id=4 (Koperasi) dan legacy_id=7 (SMP) tidak dibuatkan entri
        $total = DB::table('nomor_surat_counter')
            ->whereIn('perusahaan_id', $this->companyIds)
            ->count();
        $this->assertSame(4, $total, 'Hanya 4 baris yang harus ada (SGG 2x + RSUSM + RSUSM-BRB)');
    }

    // =========================================================================
    // Test 3: idempoten — dijalankan dua kali hasilnya sama
    // =========================================================================

    #[Test]
    public function execute_dua_kali_hasilnya_sama(): void
    {
        $args = ['--file' => self::FIXTURE, '--execute' => true];

        $this->artisan('surat:import-counter-lama', $args)->assertSuccessful();

        $after1 = $this->getCounterSnapshot();

        $this->artisan('surat:import-counter-lama', $args)->assertSuccessful();

        $after2 = $this->getCounterSnapshot();

        $this->assertEquals(
            $after1,
            $after2,
            'Menjalankan dua kali harus menghasilkan data yang identik (idempoten)'
        );
    }

    // =========================================================================
    // Test 4: counter yang sudah lebih besar tidak diturunkan
    // =========================================================================

    #[Test]
    public function execute_tidak_menurunkan_counter_yang_sudah_lebih_besar(): void
    {
        // Pre-insert counter SGG 2025 dengan nilai lebih besar dari fixture (307)
        DB::table('nomor_surat_counter')->insert([
            'perusahaan_id'  => $this->sggId,
            'tahun'          => 2025,
            'nomor_terakhir' => 999,
        ]);

        $this->artisan('surat:import-counter-lama', [
            '--file'    => self::FIXTURE,
            '--execute' => true,
        ])->assertSuccessful();

        // 999 harus dipertahankan (GREATEST menolak turun ke 307)
        $this->assertCounter($this->sggId, 2025, 999, 'SGG 2025 harus tetap 999');

        // Nilai lain yang belum ada tetap diimpor dengan benar
        $this->assertCounter($this->sggId, 2026, 351, 'SGG 2026 harus 351');
        $this->assertCounter($this->rsusmId, 2025, 1, 'RSUSM 2025 harus 1');
        $this->assertCounter($this->rsusmBrbId, 2026, 1583, 'RSUSM-BRB 2026 harus 1583');
    }

    // =========================================================================
    // Test 5: file tidak ada mengembalikan kode gagal
    // =========================================================================

    #[Test]
    public function gagal_jika_file_tidak_ada(): void
    {
        $this->artisan('surat:import-counter-lama', [
            '--file' => '/path/yang/tidak/ada/sama-sekali.sql',
        ])->assertFailed();

        // Pastikan tidak ada yang tertulis ke DB
        $count = DB::table('nomor_surat_counter')
            ->whereIn('perusahaan_id', $this->companyIds)
            ->count();
        $this->assertSame(0, $count);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function assertCounter(int $companyId, int $tahun, int $expected, string $label): void
    {
        $actual = $this->getCounter($companyId, $tahun);
        $this->assertSame(
            $expected,
            $actual,
            "Counter {$label}: diharapkan {$expected}, didapat " . ($actual ?? 'NULL')
        );
    }

    private function getCounter(int $companyId, int $tahun): ?int
    {
        $val = DB::table('nomor_surat_counter')
            ->where('perusahaan_id', $companyId)
            ->where('tahun', $tahun)
            ->value('nomor_terakhir');

        return $val !== null ? (int) $val : null;
    }

    /**
     * Snapshot semua counter milik test companies, untuk uji idempoten.
     */
    private function getCounterSnapshot(): array
    {
        return DB::table('nomor_surat_counter')
            ->whereIn('perusahaan_id', $this->companyIds)
            ->orderBy('perusahaan_id')
            ->orderBy('tahun')
            ->get(['perusahaan_id', 'tahun', 'nomor_terakhir'])
            ->map(fn ($r) => [
                'perusahaan_id'  => (int) $r->perusahaan_id,
                'tahun'          => (int) $r->tahun,
                'nomor_terakhir' => (int) $r->nomor_terakhir,
            ])
            ->toArray();
    }
}
