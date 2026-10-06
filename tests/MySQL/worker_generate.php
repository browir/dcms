<?php

/**
 * Worker proses anak untuk uji konkurensi NomorSuratService.
 *
 * Tidak ada retry di level worker — setiap kegagalan dilaporkan apa adanya.
 * Retry (maks 5x) hanya ada di level service (DB::transaction attempts).
 *
 * Input : argv[1] = JSON config
 * Output: JSON ke stdout
 *
 * Output keys:
 *   status                string  'ok' atau 'partial'
 *   pid                   int
 *   nomor_urut            int[]   nomor urut yang berhasil dibuat
 *   errors                array[] kegagalan permanen (setelah 5 retry service habis)
 *   service_deadlock_retries int  total retry deadlock di level service (semua iterasi)
 */

// ── Pengaman 1: cek DB_DATABASE SEBELUM require apapun ──────────────────────
$dbName = getenv('DB_DATABASE') ?: '';
if ($dbName !== 'dcms_test') {
    echo json_encode([
        'status'  => 'error',
        'pid'     => getmypid(),
        'message' => "DITOLAK: DB_DATABASE bukan 'dcms_test', dapat: '{$dbName}'.",
    ]);
    exit(1);
}

$config = json_decode($argv[1] ?? '{}', true);
if (! $config || ! isset($config['company_id'], $config['unit_id'], $config['tahun'], $config['count'])) {
    echo json_encode([
        'status'  => 'error',
        'pid'     => getmypid(),
        'message' => 'Config tidak valid: ' . ($argv[1] ?? '(kosong)'),
    ]);
    exit(1);
}

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Enums\JenisSurat;
use App\Models\Unit;
use App\Services\NomorSuratService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// ── Pengaman 2: cek setelah bootstrap ────────────────────────────────────────
$actualDb = DB::connection()->getDatabaseName();
if ($actualDb !== 'dcms_test') {
    echo json_encode([
        'status'  => 'error',
        'pid'     => getmypid(),
        'message' => "DITOLAK: Database aktif setelah bootstrap: '{$actualDb}'.",
    ]);
    exit(1);
}

// ── Barrier: tunggu sampai start_at ──────────────────────────────────────────
$startAt = (float) ($config['start_at'] ?? 0);
if ($startAt > 0) {
    while (microtime(true) < $startAt) {
        usleep(5000);
    }
}

$unit  = Unit::with('company')->findOrFail($config['unit_id']);
$jenis = JenisSurat::G;
$tgl   = Carbon::create($config['tahun'], 6, 15);
$count = (int) $config['count'];
$pid   = getmypid();

$nomorUrut             = [];
$errors                = [];
$serviceDeadlockRetries = 0; // total retry deadlock di level service (semua iterasi)

for ($i = 0; $i < $count; $i++) {
    NomorSuratService::$lastDeadlockRetries = 0; // reset sebelum tiap generate()

    try {
        $surat = NomorSuratService::generate($unit, $jenis, $tgl, [
            'judul_surat'      => "Test Konkurensi pid={$pid} i={$i}",
            'dibuat_oleh_nama' => "Worker-{$pid}",
        ]);

        $nomorUrut[]            = $surat->nomor_urut;
        $serviceDeadlockRetries += NomorSuratService::$lastDeadlockRetries;

    } catch (\Throwable $e) {
        // Termasuk deadlock yang exhausted setelah 5 retry service
        $code = $e instanceof \Illuminate\Database\QueryException ? (string) $e->getCode() : null;
        $type = match (true) {
            $code !== null && in_array($code, ['40001', '40P01'], true) => 'deadlock_exhausted',
            $code === '23000'                                            => 'unique_violation',
            $code !== null                                               => "query_error_{$code}",
            default                                                      => get_class($e),
        };
        $errors[] = [
            'type'      => $type,
            'code'      => $code,
            'message'   => substr($e->getMessage(), 0, 300),
            'iteration' => $i,
            'pid'       => $pid,
        ];
        // Tetap akumulasi retry yang terjadi sebelum exception
        $serviceDeadlockRetries += NomorSuratService::$lastDeadlockRetries;
    }
}

echo json_encode([
    'status'                  => empty($errors) ? 'ok' : 'partial',
    'pid'                     => $pid,
    'nomor_urut'              => $nomorUrut,
    'errors'                  => $errors,
    'service_deadlock_retries' => $serviceDeadlockRetries,
]);
exit(0);
