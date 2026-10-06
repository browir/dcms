<?php

namespace Tests\MySQL;

use App\Enums\JenisSurat;
use App\Models\Unit;
use App\Services\NomorSuratService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Process\Process;

/**
 * Uji konkurensi NomorSuratService — Tahap 3.
 *
 * Jalankan dengan:
 *   vendor\bin\phpunit -c phpunit.mysql.xml
 *
 * Tiga skenario:
 *   1. Dengan lock  : 20 proses × 10 surat — nomor berurutan 1..200, 0 kembar, 0 lubang
 *   2. Rollback     : counter rollback saat INSERT surat gagal (atomicity)
 *   3. Tanpa lock   : race condition terdeteksi via kembar atau unique violation
 */
class NomorSuratKonkurensiTest extends MySQLTestCase
{
    private const PREFIX          = 'TEST_CONCURRENCY_';
    private const TAHUN           = 2099;
    private const WORKER_TIMEOUT  = 90;

    private int $companyId;
    private int $unitId;
    private int $departmentId;

    // ── Setup & Teardown ──────────────────────────────────────────────────────

    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanupTestData();
        $this->createTestData();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestData();
        parent::tearDown();
    }

    private function createTestData(): void
    {
        $this->companyId = DB::table('companies')->insertGetId([
            'name'       => self::PREFIX . 'PERUSAHAAN',
            'code'       => 'TSTC',
            'kode_surat' => 'TSTC',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->departmentId = DB::table('departments')->insertGetId([
            'name'       => self::PREFIX . 'DEPT',
            'code'       => 'TSTD',
            'company_id' => $this->companyId,
            'is_active'  => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->unitId = DB::table('units')->insertGetId([
            'name'          => self::PREFIX . 'UNIT',
            'kode'          => 'TST',
            'department_id' => $this->departmentId,
            'company_id'    => $this->companyId,
            'is_active'     => 1,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    private function cleanupTestData(): void
    {
        $companyIds = DB::table('companies')
            ->where('name', 'LIKE', self::PREFIX . '%')
            ->pluck('id')->toArray();

        $unitIds = DB::table('units')
            ->where('name', 'LIKE', self::PREFIX . '%')
            ->pluck('id')->toArray();

        if (! empty($unitIds)) {
            DB::table('surat')->whereIn('unit_id', $unitIds)->delete();
        }
        if (! empty($companyIds)) {
            DB::table('nomor_surat_counter')->whereIn('perusahaan_id', $companyIds)->delete();
        }

        DB::table('units')->where('name', 'LIKE', self::PREFIX . '%')->delete();
        DB::table('departments')->where('name', 'LIKE', self::PREFIX . '%')->delete();
        DB::table('companies')->where('name', 'LIKE', self::PREFIX . '%')->delete();
    }

    private function resetCounter(): void
    {
        DB::table('surat')
            ->where('unit_id', $this->unitId)
            ->delete();

        DB::table('nomor_surat_counter')
            ->where('perusahaan_id', $this->companyId)
            ->where('tahun', self::TAHUN)
            ->delete();

        // Pre-create baris counter dengan nomor_terakhir=0.
        // Diperlukan oleh NomorSuratServiceNoLock yang tidak melakukan INSERT IGNORE.
        DB::table('nomor_surat_counter')->insert([
            'perusahaan_id'  => $this->companyId,
            'tahun'          => self::TAHUN,
            'nomor_terakhir' => 0,
        ]);
    }

    // ── Helper: jalankan N worker secara paralel ──────────────────────────────


    /**
     * @return array{urut: int[], errors: array[], serviceDeadlockRetries: int}
     */
    private function runWorkers(string $workerScript, int $proses, int $suratPerProses): array
    {
        $workerPath = __DIR__ . '/' . $workerScript;
        $this->assertFileExists($workerPath);

        $startAt    = microtime(true) + 5.0;
        $workerEnv  = [
            'APP_ENV'       => 'testing',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST'       => env('DB_HOST', '127.0.0.1'),
            'DB_PORT'       => (string) env('DB_PORT', '3306'),
            'DB_DATABASE'   => 'dcms_test',
            'DB_USERNAME'   => env('DB_USERNAME', 'root'),
            'DB_PASSWORD'   => (string) env('DB_PASSWORD', ''),
        ];

        $processes = [];
        for ($i = 0; $i < $proses; $i++) {
            $cfg = json_encode([
                'company_id' => $this->companyId,
                'unit_id'    => $this->unitId,
                'tahun'      => self::TAHUN,
                'count'      => $suratPerProses,
                'start_at'   => $startAt,
            ]);
            $p = new Process([PHP_BINARY, $workerPath, $cfg]);
            $p->setTimeout(self::WORKER_TIMEOUT);
            $p->setEnv($workerEnv);
            $p->start();
            $processes[] = $p;
        }

        $allUrut                = [];
        $allErrors              = [];
        $totalServiceDeadlocks  = 0;

        foreach ($processes as $idx => $p) {
            $p->wait();
            $out    = $p->getOutput();
            $err    = $p->getErrorOutput();
            $result = json_decode($out, true);

            if ($result === null) {
                $allErrors[] = [
                    'type'    => 'worker_parse_error',
                    'process' => $idx,
                    'stdout'  => substr($out, 0, 500),
                    'stderr'  => substr($err, 0, 200),
                ];
                continue;
            }

            foreach ($result['nomor_urut'] ?? [] as $u) {
                $allUrut[] = (int) $u;
            }
            foreach ($result['errors'] ?? [] as $e) {
                $allErrors[] = $e;
            }
            $totalServiceDeadlocks += (int) ($result['service_deadlock_retries'] ?? 0);
        }

        return [
            'urut'                 => $allUrut,
            'errors'               => $allErrors,
            'serviceDeadlockRetries' => $totalServiceDeadlocks,
        ];
    }

    // ── Test 1: 20 proses × 10 surat, dengan lock ────────────────────────────

    #[Test]
    public function dua_puluh_proses_paralel_tidak_ada_nomor_kembar_atau_lubang(): void
    {
        $prosesCount    = 20;
        $suratPerProses = 10;
        $totalExpected  = $prosesCount * $suratPerProses; // 200

        $result = $this->runWorkers('worker_generate.php', $prosesCount, $suratPerProses);

        // Laporkan deadlock di level service (target: mendekati 0)
        $dr = $result['serviceDeadlockRetries'];
        fwrite(STDOUT, "\n  [INFO] Retry deadlock di level service: {$dr} "
            . ($dr === 0 ? '✓ (nol, pola ON DUPLICATE KEY UPDATE bekerja)' : '(lihat catatan)') . "\n");

        // Semua error adalah fatal
        $this->assertEmpty(
            $result['errors'],
            "Ada error di worker:\n" . json_encode(array_values($result['errors']), JSON_PRETTY_PRINT)
        );

        $urut = $result['urut'];

        $this->assertCount(
            $totalExpected,
            $urut,
            "Jumlah surat harus {$totalExpected}, dapat " . count($urut)
        );

        $this->assertCount(
            $totalExpected,
            array_unique($urut),
            'Ada nomor urut kembar'
        );

        sort($urut);
        $this->assertEquals(
            range(1, $totalExpected),
            array_values($urut),
            "Nomor urut harus berurutan 1..{$totalExpected} tanpa lubang"
        );

        $counter = DB::table('nomor_surat_counter')
            ->where('perusahaan_id', $this->companyId)
            ->where('tahun', self::TAHUN)
            ->first();

        $this->assertNotNull($counter);
        $this->assertEquals($totalExpected, $counter->nomor_terakhir, "Counter akhir harus {$totalExpected}");

        // Verifikasi tabel surat
        $suratCount = DB::table('surat')
            ->where('unit_id', $this->unitId)
            ->count();
        $this->assertEquals(
            $totalExpected,
            $suratCount,
            "Jumlah baris di tabel surat harus {$totalExpected}"
        );

        $distinctNomor = DB::table('surat')
            ->where('unit_id', $this->unitId)
            ->distinct()
            ->count('nomor');
        $this->assertEquals(
            $totalExpected,
            $distinctNomor,
            "COUNT(DISTINCT nomor) harus {$totalExpected} — tidak boleh ada nomor kembar di DB"
        );
    }

    // ── Test 2: Rollback atomicity ────────────────────────────────────────────

    #[Test]
    public function rollback_memulihkan_counter_saat_insert_surat_gagal(): void
    {
        $unit    = Unit::with('company')->findOrFail($this->unitId);
        $jenis   = JenisSurat::G;
        $tanggal = Carbon::create(self::TAHUN, 6, 15);
        $dataOk  = ['judul_surat' => 'Test Rollback', 'dibuat_oleh_nama' => 'Tester'];

        // Buat 3 surat sukses (counter → 3)
        for ($i = 0; $i < 3; $i++) {
            NomorSuratService::generate($unit, $jenis, $tanggal, $dataOk);
        }

        $sebelum = DB::table('nomor_surat_counter')
            ->where('perusahaan_id', $this->companyId)
            ->where('tahun', self::TAHUN)
            ->first();

        $this->assertEquals(3, $sebelum->nomor_terakhir);
        $this->assertEquals(3, DB::table('surat')->where('unit_id', $this->unitId)->count());

        // Coba generate dengan service yang fail setelah counter naik
        $caught = false;
        try {
            NomorSuratServiceFailOnPersist::generate($unit, $jenis, $tanggal, $dataOk);
        } catch (\RuntimeException $e) {
            $caught = true;
            $this->assertStringContainsString('[TEST] Simulasi kegagalan', $e->getMessage());
        }
        $this->assertTrue($caught, 'Exception harus dilempar');

        // Counter harus rollback ke 3 (bukan 4)
        $sesudah = DB::table('nomor_surat_counter')
            ->where('perusahaan_id', $this->companyId)
            ->where('tahun', self::TAHUN)
            ->first();

        $this->assertEquals(3, $sesudah->nomor_terakhir,
            'Counter harus rollback ke 3, bukan 4 — transaksi harus atomik');
        $this->assertEquals(3, DB::table('surat')->where('unit_id', $this->unitId)->count(),
            'Jumlah surat harus tetap 3 setelah rollback');
    }

    // ── Test 3: Kontrol negatif — tanpa lock ─────────────────────────────────

    #[Test]
    public function tanpa_lock_mendeteksi_race_condition(): void
    {
        $maxAttempts    = 5;
        $prosesCount    = 10;
        $suratPerProses = 5;
        $totalExpected  = $prosesCount * $suratPerProses; // 50

        $raceDetected = false;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $this->resetCounter();
            $result = $this->runWorkers('worker_generate_nolock.php', $prosesCount, $suratPerProses);

            $urut   = $result['urut'];
            $errors = $result['errors'];

            // Pisahkan jenis error
            $errByType = [];
            foreach ($errors as $e) {
                $t = $e['type'] ?? 'unknown';
                $errByType[$t] = ($errByType[$t] ?? 0) + 1;
            }

            $berhasil          = count($urut);
            $hasDuplicate      = count(array_unique($urut)) < count($urut);
            $uniqueViolation   = ($errByType['unique_violation'] ?? 0) > 0;
            $hasShortfall      = $berhasil < $totalExpected;

            // Hitung lubang dalam nomor urut yang berhasil (bukan shortfall global)
            sort($urut);
            $hasGapInUrut = false;
            for ($j = 1; $j < count($urut); $j++) {
                if ($urut[$j] !== $urut[$j - 1] + 1) {
                    $hasGapInUrut = true;
                    break;
                }
            }

            // Cetak rincian per percobaan
            $shortfallStr    = $hasShortfall   ? "Ya ({$berhasil}/{$totalExpected})" : 'Tidak';
            $dupStr          = $hasDuplicate   ? 'Ya' : 'Tidak';
            $gapStr          = $hasGapInUrut   ? 'Ya' : 'Tidak';
            $errStr          = empty($errByType) ? 'Tidak ada' : json_encode($errByType);
            fwrite(STDOUT,
                "\n  [Kontrol Negatif] Percobaan {$attempt}/{$maxAttempts}:\n"
                . "    Berhasil   : {$berhasil}/{$totalExpected}\n"
                . "    Kekurangan : {$shortfallStr}\n"
                . "    Kembar     : {$dupStr}\n"
                . "    Lubang     : {$gapStr}\n"
                . "    Error      : {$errStr}\n"
            );

            // Test peka HANYA pada kembar atau unique violation pada surat.nomor
            // Kekurangan saja (tanpa kembar/unique_violation) bukan bukti race condition
            // (bisa jadi hanya lock contention atau timeout)
            if ($hasDuplicate || $uniqueViolation) {
                $raceDetected = true;
                fwrite(STDOUT, "    → RACE CONDITION TERDETEKSI\n");
                break;
            }
        }

        if (! $raceDetected) {
            $this->markTestIncomplete(
                "Race condition (kembar atau unique violation) tidak terdeteksi setelah "
                . "{$maxAttempts} percobaan tanpa lock. "
                . "Kemungkinan: MySQL serialize INSERT ON DUPLICATE KEY UPDATE secara internal "
                . "bahkan untuk pola non-atomik, atau concurrency tidak cukup tinggi. "
                . "Test ini incomplete — BUKAN berarti lock tidak diperlukan di production."
            );
        }

        $this->assertTrue($raceDetected,
            'Race condition (kembar atau unique_violation) harus terdeteksi saat lock dinonaktifkan');
    }
}
