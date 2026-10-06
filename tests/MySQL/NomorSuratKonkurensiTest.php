<?php

namespace Tests\MySQL;

use App\Enums\JenisSurat;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Process\Process;

/**
 * Uji konkurensi NomorSuratService — Tahap 3.
 *
 * Jalankan dengan:
 *   php artisan test --configuration=phpunit.mysql.xml
 *
 * Prasyarat:
 *   - Database dcms_test sudah ada dan sudah di-migrate
 *   - Tidak ada data berprefiks TEST_CONCURRENCY_ yang mengganggu
 *
 * Tiga skenario:
 *   1. Dengan lock  : 20 proses × 10 surat — nomor urut 1..200, tanpa kembar, tanpa lubang
 *   2. Rollback     : counter rollback saat INSERT surat gagal
 *   3. Tanpa lock   : race condition terdeteksi (kembar, lubang, atau unique violation)
 */
class NomorSuratKonkurensiTest extends MySQLTestCase
{
    private const PREFIX     = 'TEST_CONCURRENCY_';
    private const TAHUN      = 2099; // Tahun jauh di masa depan, hindari konflik data nyata
    private const WORKER_TIMEOUT = 90; // detik

    private int $companyId;
    private int $unitId;
    private int $departmentId;

    // ── Setup & Teardown ──────────────────────────────────────────────────────

    protected function setUp(): void
    {
        parent::setUp(); // MySQLTestCase mengecek DB = dcms_test

        $this->cleanupTestData(); // bersihkan sisa run yang gagal
        $this->createTestData();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestData();
        parent::tearDown();
    }

    private function createTestData(): void
    {
        // Company
        $this->companyId = DB::table('companies')->insertGetId([
            'name'       => self::PREFIX . 'PERUSAHAAN',
            'code'       => 'TSTC',
            'kode_surat' => 'TSTC',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Department (butuh: name, code, company_id, is_active)
        $this->departmentId = DB::table('departments')->insertGetId([
            'name'       => self::PREFIX . 'DEPT',
            'code'       => 'TSTD',
            'company_id' => $this->companyId,
            'is_active'  => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Unit (butuh: name, department_id, is_active)
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
        // Ambil id dulu (FK order)
        $companyIds = DB::table('companies')
            ->where('name', 'LIKE', self::PREFIX . '%')
            ->pluck('id')
            ->toArray();

        $unitIds = DB::table('units')
            ->where('name', 'LIKE', self::PREFIX . '%')
            ->pluck('id')
            ->toArray();

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
        DB::table('nomor_surat_counter')
            ->where('perusahaan_id', $this->companyId)
            ->where('tahun', self::TAHUN)
            ->delete();

        DB::table('surat')
            ->where('unit_id', $this->unitId)
            ->delete();
    }

    // ── Helper: jalankan N worker secara paralel ──────────────────────────────

    /**
     * @param  string $workerScript  Nama file worker di tests/MySQL/
     * @param  int    $proses        Jumlah proses paralel
     * @param  int    $suratPerProses Surat per proses
     * @return array{urut: int[], errors: array[], deadlockExceeded: int}
     */
    private function runWorkers(string $workerScript, int $proses, int $suratPerProses): array
    {
        $workerPath = __DIR__ . '/' . $workerScript;
        $this->assertFileExists($workerPath, "Worker script tidak ditemukan: {$workerPath}");

        // Barrier: beri 5 detik untuk semua worker bootstrap
        $startAt = microtime(true) + 5.0;

        $workerEnv = [
            'APP_ENV'       => 'testing',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST'       => env('DB_HOST', '127.0.0.1'),
            'DB_PORT'       => (string) env('DB_PORT', '3306'),
            'DB_DATABASE'   => 'dcms_test',
            'DB_USERNAME'   => env('DB_USERNAME', 'root'),
            'DB_PASSWORD'   => (string) env('DB_PASSWORD', ''),
        ];

        // Mulai semua proses sekaligus
        $processes = [];
        for ($i = 0; $i < $proses; $i++) {
            $configJson = json_encode([
                'company_id' => $this->companyId,
                'unit_id'    => $this->unitId,
                'tahun'      => self::TAHUN,
                'count'      => $suratPerProses,
                'start_at'   => $startAt,
            ]);

            $process = new Process([PHP_BINARY, $workerPath, $configJson]);
            $process->setTimeout(self::WORKER_TIMEOUT);
            $process->setEnv($workerEnv);
            $process->start();
            $processes[] = $process;
        }

        // Kumpulkan hasil
        $allUrut            = [];
        $allErrors          = [];
        $totalDeadlockRetry = 0;

        foreach ($processes as $idx => $process) {
            $process->wait();

            $stdout = $process->getOutput();
            $stderr = $process->getErrorOutput();
            $result = json_decode($stdout, true);

            if ($result === null) {
                $allErrors[] = [
                    'type'    => 'worker_parse_error',
                    'process' => $idx,
                    'stdout'  => substr($stdout, 0, 500),
                    'stderr'  => substr($stderr, 0, 500),
                ];
                continue;
            }

            foreach ($result['nomor_urut'] ?? [] as $urut) {
                $allUrut[] = (int) $urut;
            }

            foreach ($result['errors'] ?? [] as $err) {
                $allErrors[] = $err;
            }

            // total_deadlock_retry = deadlock yang BERHASIL di-recovery via worker retry
            $totalDeadlockRetry += (int) ($result['total_deadlock_retry'] ?? 0);
        }

        return [
            'urut'               => $allUrut,
            'errors'             => $allErrors,
            'totalDeadlockRetry' => $totalDeadlockRetry,
        ];
    }

    // ── Test 1: Konkurensi dengan lock ───────────────────────────────────────

    #[Test]
    public function dua_puluh_proses_paralel_tidak_ada_nomor_kembar_atau_lubang(): void
    {
        $prosesCount      = 20;
        $suratPerProses   = 10;
        $totalExpected    = $prosesCount * $suratPerProses; // 200

        $result = $this->runWorkers('worker_generate.php', $prosesCount, $suratPerProses);

        // Laporkan deadlock yang berhasil di-recovery (informatif, bukan failure)
        if ($result['totalDeadlockRetry'] > 0) {
            fwrite(STDOUT, "\n  [INFO] Total retry deadlock di worker: "
                . $result['totalDeadlockRetry'] . " (berhasil di-recovery, bukan kegagalan)\n");
        }

        // Semua error di worker adalah fatal — deadlock yang terpulihkan
        // tidak masuk ke errors (hanya dicatat di total_deadlock_retry)
        $this->assertEmpty(
            $result['errors'],
            'Ada error di worker: ' . json_encode(array_values($result['errors']), JSON_PRETTY_PRINT)
        );

        $urut = $result['urut'];

        // Jumlah total harus tepat
        $this->assertCount(
            $totalExpected,
            $urut,
            "Jumlah surat harus {$totalExpected}, dapat " . count($urut)
        );

        // Tidak ada nomor kembar
        $this->assertCount(
            $totalExpected,
            array_unique($urut),
            'Ada nomor urut kembar: ' . implode(', ', array_diff_key($urut, array_unique($urut)))
        );

        // Berurutan 1..200 tanpa lubang
        sort($urut);
        $this->assertEquals(
            range(1, $totalExpected),
            array_values($urut),
            'Nomor urut harus berurutan 1..' . $totalExpected . ' tanpa lubang'
        );

        // Counter akhir harus = 200
        $counter = DB::table('nomor_surat_counter')
            ->where('perusahaan_id', $this->companyId)
            ->where('tahun', self::TAHUN)
            ->first();

        $this->assertNotNull($counter, 'Baris counter harus ada');
        $this->assertEquals(
            $totalExpected,
            $counter->nomor_terakhir,
            "Counter akhir harus {$totalExpected}"
        );
    }

    // ── Test 2: Rollback setelah counter naik ────────────────────────────────

    #[Test]
    public function rollback_memulihkan_counter_saat_insert_surat_gagal(): void
    {
        $unit    = Unit::with('company')->findOrFail($this->unitId);
        $jenis   = JenisSurat::G;
        $tanggal = Carbon::create(self::TAHUN, 6, 15);
        $dataOk  = [
            'judul_surat'      => 'Surat Test Rollback',
            'dibuat_oleh_nama' => 'Tester',
        ];

        // Buat 3 surat dengan service normal dulu (counter → 3)
        for ($i = 0; $i < 3; $i++) {
            \App\Services\NomorSuratService::generate($unit, $jenis, $tanggal, $dataOk);
        }


        $counterBefore = DB::table('nomor_surat_counter')
            ->where('perusahaan_id', $this->companyId)
            ->where('tahun', self::TAHUN)
            ->first();

        $this->assertEquals(3, $counterBefore->nomor_terakhir, 'Counter harus = 3 sebelum rollback test');
        $this->assertEquals(3, DB::table('surat')->where('unit_id', $this->unitId)->count());

        // Sekarang coba dengan service yang fail setelah counter naik
        $exceptionCaught = false;
        try {
            NomorSuratServiceFailOnPersist::generate($unit, $jenis, $tanggal, $dataOk);
        } catch (\RuntimeException $e) {
            $exceptionCaught = true;
            $this->assertStringContainsString(
                '[TEST] Simulasi kegagalan',
                $e->getMessage(),
                'Exception harus dari persistSurat yang di-override'
            );
        }

        $this->assertTrue($exceptionCaught, 'Exception harus dilempar oleh NomorSuratServiceFailOnPersist');

        // Counter HARUS kembali ke 3 (bukan 4) karena rollback
        $counterAfter = DB::table('nomor_surat_counter')
            ->where('perusahaan_id', $this->companyId)
            ->where('tahun', self::TAHUN)
            ->first();

        $this->assertEquals(
            3,
            $counterAfter->nomor_terakhir,
            "Counter harus rollback ke 3, bukan 4. Transaksi atomik gagal."
        );

        // Surat tetap 3
        $this->assertEquals(
            3,
            DB::table('surat')->where('unit_id', $this->unitId)->count(),
            'Jumlah surat harus tetap 3 setelah rollback'
        );
    }

    // ── Test 3: Kontrol negatif — tanpa lock ─────────────────────────────────

    #[Test]
    public function tanpa_lock_mendeteksi_race_condition(): void
    {
        $maxAttempts  = 5;
        $prosesCount  = 10;
        $suratPerProses = 5;
        $raceDetected = false;
        $attemptInfo  = [];

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $this->resetCounter();

            $result = $this->runWorkers('worker_generate_nolock.php', $prosesCount, $suratPerProses);

            $urut           = $result['urut'];
            $errors         = $result['errors'];
            $totalInserted  = count($urut);
            $totalExpected  = $prosesCount * $suratPerProses; // 50

            $hasDuplicate = count(array_unique($urut)) < count($urut);
            $hasGap       = ! empty($urut) && (array_values(array_unique($urut)) !== range(1, count(array_unique($urut))));
            $hasUniqueViolation = count(array_filter($errors, fn ($e) => ($e['type'] ?? '') === 'unique_violation')) > 0;
            $hasShortfall   = $totalInserted < $totalExpected;

            $raceDetected = $hasDuplicate || $hasGap || $hasUniqueViolation || $hasShortfall;

            $attemptInfo[] = [
                'attempt'          => $attempt,
                'inserted'         => $totalInserted,
                'expected'         => $totalExpected,
                'has_duplicate'    => $hasDuplicate,
                'has_gap'          => $hasGap,
                'unique_violation' => $hasUniqueViolation,
                'shortfall'        => $hasShortfall,
                'error_count'      => count($errors),
            ];

            if ($raceDetected) {
                break;
            }
        }

        // Cetak ringkasan percobaan
        fwrite(STDOUT, "\n  [Kontrol Negatif] Ringkasan percobaan:\n");
        foreach ($attemptInfo as $info) {
            $status = ($info['has_duplicate'] || $info['has_gap'] || $info['unique_violation'] || $info['shortfall'])
                ? 'RACE TERDETEKSI' : 'tidak terdeteksi';
            fwrite(STDOUT,
                "    Percobaan {$info['attempt']}: {$info['inserted']}/{$info['expected']} surat, "
                . "duplikat={$info['has_duplicate']}, lubang={$info['has_gap']}, "
                . "unik_viol={$info['unique_violation']}, shortfall={$info['shortfall']} → {$status}\n"
            );
        }

        if (! $raceDetected) {
            $this->markTestIncomplete(
                "Race condition tidak terdeteksi setelah {$maxAttempts} percobaan tanpa lock. "
                . "Kemungkinan: MySQL melakukan serialisasi internal, hardware terlalu cepat, "
                . "atau jumlah proses tidak cukup. "
                . "Test ini tidak lulus (incomplete) — lock MUNGKIN tidak diperlukan di environment ini, "
                . "TAPI ini bukan berarti lock aman untuk dihapus di production."
            );
        }

        $this->assertTrue(
            $raceDetected,
            "Race condition harus terdeteksi saat lock dinonaktifkan. "
            . "Detail: " . json_encode($attemptInfo)
        );
    }
}
