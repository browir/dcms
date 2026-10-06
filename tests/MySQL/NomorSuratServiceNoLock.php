<?php

namespace Tests\MySQL;

use App\Services\NomorSuratService;
use Illuminate\Support\Facades\DB;

/**
 * Subclass untuk test kontrol negatif.
 * Menghilangkan SELECT FOR UPDATE agar race condition dapat terjadi.
 *
 * HANYA dipakai di tests/ — jangan pernah dipakai di kode produksi.
 */
class NomorSuratServiceNoLock extends NomorSuratService
{
    protected static function acquireLock(int $perusahaanId, int $tahun): ?object
    {
        // Tanpa lockForUpdate — race condition dapat terjadi
        return DB::table('nomor_surat_counter')
            ->where('perusahaan_id', $perusahaanId)
            ->where('tahun', $tahun)
            ->first();
    }
}
