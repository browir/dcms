<?php

namespace Tests\MySQL;

use App\Services\NomorSuratService;
use Illuminate\Support\Facades\DB;

/**
 * Subclass untuk test kontrol negatif.
 *
 * Mengganti incrementCounter() dengan pola READ-MODIFY-WRITE yang TIDAK atomik:
 *   1. SELECT nomor_terakhir  ← race window: dua proses bisa membaca nilai yang sama
 *   2. UPDATE nomor_terakhir = current + 1
 *
 * Tidak ada INSERT IGNORE (menghindari InnoDB gap lock yang menyebabkan deadlock).
 * Baris counter harus sudah ada sebelum dipakai — dibuat di test setUp/resetCounter.
 *
 * Skenario race yang terbukti:
 *   - Worker A baca nomor_terakhir=5, Worker B baca nomor_terakhir=5
 *   - Keduanya hitung next=6
 *   - A UPDATE ke 6, COMMIT, INSERT surat(urut=6) → sukses
 *   - B UPDATE ke 6 (nilai tidak berubah), INSERT surat(urut=6) → UNIQUE VIOLATION
 *
 * HANYA dipakai di tests/ — jangan pernah dipakai di kode produksi.
 */
class NomorSuratServiceNoLock extends NomorSuratService
{
    protected static function incrementCounter(int $perusahaanId, int $tahun): int
    {
        // Plain snapshot SELECT — tidak ada lock, race window di sini.
        // Dua proses concurrent dapat membaca nilai yang sama.
        $current = (int) DB::table('nomor_surat_counter')
            ->where('perusahaan_id', $perusahaanId)
            ->where('tahun', $tahun)
            ->value('nomor_terakhir');

        $next = $current + 1;

        // UPDATE tanpa memeriksa nilai sebelumnya.
        // Jika proses lain sudah UPDATE lebih dulu dengan nilai yang sama,
        // UPDATE ini tetap berhasil (mengubah ke nilai yang sudah ada).
        // Akibatnya: dua proses memiliki $next yang sama → unique violation pada surat.nomor.
        DB::table('nomor_surat_counter')
            ->where('perusahaan_id', $perusahaanId)
            ->where('tahun', $tahun)
            ->update(['nomor_terakhir' => $next]);

        return $next;
    }
}
