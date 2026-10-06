<?php

/**
 * Worker TANPA LOCK untuk uji kontrol negatif.
 */

$dbName = getenv('DB_DATABASE') ?: '';
if ($dbName !== 'dcms_test') {
    echo json_encode(['status' => 'error', 'pid' => getmypid(),
        'message' => "DITOLAK: DB_DATABASE bukan 'dcms_test', dapat: '{$dbName}'."]);
    exit(1);
}

$config = json_decode($argv[1] ?? '{}', true);
if (! $config || ! isset($config['company_id'], $config['unit_id'], $config['tahun'], $config['count'])) {
    echo json_encode(['status' => 'error', 'pid' => getmypid(),
        'message' => 'Config tidak valid: ' . ($argv[1] ?? '(kosong)')]);
    exit(1);
}

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Enums\JenisSurat;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

require_once __DIR__ . '/NomorSuratServiceNoLock.php';
use Tests\MySQL\NomorSuratServiceNoLock;

$actualDb = DB::connection()->getDatabaseName();
if ($actualDb !== 'dcms_test') {
    echo json_encode(['status' => 'error', 'pid' => getmypid(),
        'message' => "DITOLAK: Database aktif setelah bootstrap: '{$actualDb}'."]);
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

$nomorUrut          = [];
$errors             = [];
$totalDeadlockRetry = 0;

// Kontrol negatif: TANPA retry untuk deadlock dan unique violation
// Setiap kegagalan langsung dicatat — itulah yang ingin kita buktikan
for ($i = 0; $i < $count; $i++) {
    try {
        $surat = NomorSuratServiceNoLock::generate($unit, $jenis, $tgl, [
            'judul_surat'      => "NoLock pid={$pid} i={$i}",
            'dibuat_oleh_nama' => "NoLockWorker-{$pid}",
        ]);
        $nomorUrut[] = $surat->nomor_urut;

    } catch (\Illuminate\Database\QueryException $e) {
        $code = (string) $e->getCode();
        $type = match (true) {
            in_array($code, ['40001', '40P01'], true) => 'deadlock',
            $code === '23000'                         => 'unique_violation',
            default                                   => "query_error_{$code}",
        };
        $errors[] = [
            'type'      => $type,
            'code'      => $code,
            'message'   => substr($e->getMessage(), 0, 200),
            'iteration' => $i,
            'pid'       => $pid,
        ];
    } catch (\Throwable $e) {
        $errors[] = [
            'type'      => get_class($e),
            'message'   => $e->getMessage(),
            'iteration' => $i,
            'pid'       => $pid,
        ];
    }
}

echo json_encode([
    'status'               => empty($errors) ? 'ok' : 'partial',
    'pid'                  => $pid,
    'nomor_urut'           => $nomorUrut,
    'errors'               => $errors,
    'total_deadlock_retry'  => $totalDeadlockRetry,
]);
exit(0);
