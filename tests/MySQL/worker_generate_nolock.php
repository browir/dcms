<?php

/**
 * Worker TANPA LOCK untuk uji kontrol negatif.
 * Memakai NomorSuratServiceNoLock (non-atomik: SELECT lalu UPDATE terpisah).
 * Tidak ada retry — setiap kegagalan langsung dicatat untuk analisis race condition.
 *
 * Output keys:
 *   status     string
 *   pid        int
 *   nomor_urut int[]   nomor urut yang berhasil di-INSERT ke tabel surat
 *   errors     array[] setiap kegagalan dengan type, code, message, nomor_urut_dicoba
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

$nomorUrut = [];
$errors    = [];

for ($i = 0; $i < $count; $i++) {
    // Kita perlu tahu nomor urut yang DICOBA agar bisa deteksi kembar
    // Kita tangkap di level transaksi dengan menjalankan incrementCounter
    // secara manual terlebih dahulu di luar persistSurat.
    // Cara termudah: tangkap exception dan parse nomor_urut dari pesan error.
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
            'message'   => substr($e->getMessage(), 0, 250),
            'iteration' => $i,
            'pid'       => $pid,
        ];
    } catch (\Throwable $e) {
        $errors[] = [
            'type'      => get_class($e),
            'message'   => substr($e->getMessage(), 0, 250),
            'iteration' => $i,
            'pid'       => $pid,
        ];
    }
}

echo json_encode([
    'status'     => empty($errors) ? 'ok' : 'partial',
    'pid'        => $pid,
    'nomor_urut' => $nomorUrut,
    'errors'     => $errors,
]);
exit(0);
