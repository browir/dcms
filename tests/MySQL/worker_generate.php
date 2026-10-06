<?php

/**
 * Worker proses anak untuk uji konkurensi NomorSuratService.
 *
 * Input : argv[1] = JSON config
 * Output: JSON ke stdout
 */

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

$actualDb = DB::connection()->getDatabaseName();
if ($actualDb !== 'dcms_test') {
    echo json_encode([
        'status'  => 'error',
        'pid'     => getmypid(),
        'message' => "DITOLAK: Database aktif setelah bootstrap: '{$actualDb}'.",
    ]);
    exit(1);
}

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

// Deteksi deadlock: SQLSTATE 40001 (MySQL) atau 40P01 (Postgres), atau pesan "Deadlock"
$isDeadlock = function (\Throwable $e): bool {
    if ($e instanceof \Illuminate\Database\QueryException) {
        $code = (string) $e->getCode();
        if (in_array($code, ['40001', '40P01'], true)) {
            return true;
        }
    }
    return str_contains($e->getMessage(), 'Deadlock') || str_contains($e->getMessage(), 'deadlock');
};

$nomorUrut          = [];
$errors             = [];
$totalDeadlockRetry = 0;

// Setiap iterasi (satu surat) punya retry mandiri di level worker
// Service sudah retry 5x internal; worker tambahkan 30x lagi dengan backoff
$MAX_WORKER_RETRY = 30;

for ($i = 0; $i < $count; $i++) {
    $success      = false;
    $workerRetry  = 0;

    while (! $success && $workerRetry <= $MAX_WORKER_RETRY) {
        try {
            $surat = NomorSuratService::generate($unit, $jenis, $tgl, [
                'judul_surat'      => "Test Konkurensi pid={$pid} i={$i}",
                'dibuat_oleh_nama' => "Worker-{$pid}",
            ]);
            $nomorUrut[] = $surat->nomor_urut;
            $success     = true;

        } catch (\Throwable $e) {
            if ($isDeadlock($e) && $workerRetry < $MAX_WORKER_RETRY) {
                // Deadlock: tunggu sedikit lalu coba lagi
                $totalDeadlockRetry++;
                $workerRetry++;
                usleep(random_int(5_000, 50_000)); // 5–50ms backoff acak
            } else {
                // Error lain, atau deadlock habis retry
                $type = $isDeadlock($e) ? 'deadlock_exhausted' : get_class($e);
                if ($e instanceof \Illuminate\Database\QueryException) {
                    $code = (string) $e->getCode();
                    $type = match (true) {
                        in_array($code, ['40001', '40P01'], true) => 'deadlock_exhausted',
                        $code === '23000'                         => 'unique_violation',
                        default                                   => "query_error_{$code}",
                    };
                }
                $errors[] = [
                    'type'          => $type,
                    'code'          => $e instanceof \Illuminate\Database\QueryException
                                        ? (string) $e->getCode() : null,
                    'message'       => substr($e->getMessage(), 0, 300),
                    'iteration'     => $i,
                    'pid'           => $pid,
                    'worker_retry'  => $workerRetry,
                ];
                break; // jangan coba lagi untuk error ini
            }
        }
    }

    if (! $success && $workerRetry > $MAX_WORKER_RETRY) {
        $errors[] = [
            'type'      => 'deadlock_exhausted',
            'message'   => "Deadlock tidak sembuh setelah {$MAX_WORKER_RETRY} retry di worker",
            'iteration' => $i,
            'pid'       => $pid,
        ];
    }
}

echo json_encode([
    'status'              => empty($errors) ? 'ok' : 'partial',
    'pid'                 => $pid,
    'nomor_urut'          => $nomorUrut,
    'errors'              => $errors,
    'total_deadlock_retry' => $totalDeadlockRetry,
]);
exit(0);
