<?php

namespace App\Services;

use App\Enums\JenisSurat;
use App\Models\Surat;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * NomorSuratService — satu-satunya tempat logika penomoran surat keluar.
 *
 * JANGAN menyalin logika generate() ke tempat lain.
 * Seluruh operasi counter harus memanggil class ini.
 *
 * Format nomor:
 *   {urut}/{kode_jenis}.{kode_unit}/{kode_perusahaan}/{bulan_romawi}/{tahun}
 *   Contoh: 004/G.3C/SGG/IX/2025
 *
 * Scope counter: (perusahaan_id, tahun tanggal surat) — bukan per unit atau jenis.
 * Reset hanya saat tahun berganti. Nomor yang terpakai tidak pernah dikembalikan.
 *
 * Untuk keperluan test, dua method protected dapat di-override via subclass:
 *   - static::acquireLock()  — mengambil row counter dengan lock eksklusif
 *   - static::persistSurat() — menyimpan surat ke database
 * Subclass HANYA boleh dibuat di direktori tests/, bukan di kode produksi.
 */
class NomorSuratService
{
    /** @var array<int, string> Peta angka → angka Romawi */
    private const ROMAWI = [
        1  => 'I',
        2  => 'II',
        3  => 'III',
        4  => 'IV',
        5  => 'V',
        6  => 'VI',
        7  => 'VII',
        8  => 'VIII',
        9  => 'IX',
        10 => 'X',
        11 => 'XI',
        12 => 'XII',
    ];

    // ── Fungsi format (murni, tidak ada DB call) ─────────────────────────────

    /**
     * Format nomor urut menjadi string dengan zero-padding.
     *
     * Aturan:
     * - 1–999   → 3 digit (001, 002, ..., 999)
     * - ≥ 1000  → tanpa padding, tulis apa adanya (1000, 1001, ...)
     *
     * @param  positive-int $urut
     */
    public static function formatUrut(int $urut): string
    {
        if ($urut < 1) {
            throw new \InvalidArgumentException("Urutan nomor harus >= 1, diberikan: {$urut}");
        }

        return $urut < 1000 ? str_pad((string) $urut, 3, '0', STR_PAD_LEFT) : (string) $urut;
    }

    /**
     * Konversi nomor bulan (1–12) ke angka Romawi.
     *
     * @param  int $bulan 1–12
     * @throws \InvalidArgumentException jika bulan di luar 1–12
     */
    public static function bulanRomawi(int $bulan): string
    {
        if (! isset(self::ROMAWI[$bulan])) {
            throw new \InvalidArgumentException("Bulan harus antara 1 dan 12, diberikan: {$bulan}");
        }

        return self::ROMAWI[$bulan];
    }

    /**
     * Format nomor surat dari bagian-bagiannya.
     *
     * Format: {urut}/{kode_jenis}.{kode_unit}/{kode_perusahaan}/{bulan_romawi}/{tahun}
     *
     * Fungsi ini murni — hanya string manipulation, tidak ada DB call.
     *
     * @param  int    $urut           Nomor urut (positif)
     * @param  string $kodeJenis      Kode jenis surat (misal: G, SP, MCU)
     * @param  string $kodeUnit       Kode unit (misal: 3C, HRD)
     * @param  string $kodePerusahaan Kode surat perusahaan (misal: SGG, RSUSM)
     * @param  int    $bulan          Nomor bulan 1–12
     * @param  int    $tahun          Tahun 4 digit
     */
    public static function formatNomor(
        int $urut,
        string $kodeJenis,
        string $kodeUnit,
        string $kodePerusahaan,
        int $bulan,
        int $tahun,
    ): string {
        return sprintf(
            '%s/%s.%s/%s/%s/%d',
            self::formatUrut($urut),
            $kodeJenis,
            $kodeUnit,
            $kodePerusahaan,
            self::bulanRomawi($bulan),
            $tahun,
        );
    }

    // ── Method yang dapat di-override oleh subclass di tests/ ────────────────

    /**
     * Mengambil baris counter dengan SELECT ... FOR UPDATE (lock eksklusif).
     *
     * Dapat di-override oleh subclass test untuk menghilangkan lock (kontrol negatif).
     * JANGAN override di kode produksi.
     *
     * @return object|null Baris counter, atau null jika tidak ditemukan
     */
    protected static function acquireLock(int $perusahaanId, int $tahun): ?object
    {
        return DB::table('nomor_surat_counter')
            ->where('perusahaan_id', $perusahaanId)
            ->where('tahun', $tahun)
            ->lockForUpdate()
            ->first();
    }

    /**
     * Menyimpan surat ke database.
     *
     * Dapat di-override oleh subclass test untuk mensimulasikan kegagalan
     * setelah counter di-increment (test rollback).
     * JANGAN override di kode produksi.
     *
     * @param  array $data Data surat yang sudah lengkap (termasuk nomor dan nomor_urut)
     */
    protected static function persistSurat(array $data): Surat
    {
        return Surat::create($data);
    }

    // ── Validasi input ────────────────────────────────────────────────────────

    /**
     * Validasi semua prasyarat sebelum counter disentuh.
     * Melempar RuntimeException jika ada yang tidak memenuhi syarat.
     *
     * @param  Unit $unit Unit yang membuat surat
     * @throws RuntimeException
     */
    private static function validasiUnit(Unit $unit): void
    {
        if ($unit->company_id === null) {
            throw new RuntimeException(
                "Unit [{$unit->id}] \"{$unit->name}\" belum memiliki company_id. "
                . 'Isi units.company_id terlebih dahulu sebelum membuat surat keluar.'
            );
        }

        if (empty(trim((string) $unit->kode))) {
            throw new RuntimeException(
                "Unit [{$unit->id}] \"{$unit->name}\" belum memiliki kode unit. "
                . 'Isi units.kode terlebih dahulu sebelum membuat surat keluar.'
            );
        }

        $company = $unit->company;

        if ($company === null) {
            throw new RuntimeException(
                "Perusahaan untuk unit [{$unit->id}] \"{$unit->name}\" tidak ditemukan "
                . "(company_id={$unit->company_id}). Periksa integritas data."
            );
        }

        if (empty(trim((string) $company->kode_surat))) {
            throw new RuntimeException(
                "Perusahaan [{$company->id}] \"{$company->name}\" belum memiliki kode_surat. "
                . 'Jalankan perintah surat:seed-perusahaan atau isi companies.kode_surat secara manual.'
            );
        }
    }

    // ── Fungsi utama generate ────────────────────────────────────────────────

    /**
     * Generate nomor surat keluar dan simpan surat baru dalam satu transaksi atomik.
     *
     * Algoritma:
     *   1. Validasi (unit.kode, unit.company_id, company.kode_surat) SEBELUM transaksi.
     *   2. Buka transaksi (retry otomatis hingga 5x saat deadlock MySQL 1213).
     *   3. INSERT IGNORE baris counter jika belum ada (nomor_terakhir = 0).
     *   4. static::acquireLock() — SELECT FOR UPDATE (dapat di-override).
     *   5. urut = nomor_terakhir + 1, UPDATE counter.
     *   6. Format nomor surat.
     *   7. static::persistSurat() — INSERT surat (dapat di-override).
     *   8. COMMIT. Rollback otomatis jika ada kegagalan.
     *
     * Catatan transaksi:
     *   - `attempts: 5` membuat Laravel retry otomatis saat deadlock (error 1213).
     *   - Validasi di luar transaksi memastikan counter tidak terbuang saat validasi gagal.
     *
     * @param  Unit       $unit    Unit pembuat surat
     * @param  JenisSurat $jenis   Jenis surat dari enum
     * @param  Carbon     $tanggal Tanggal surat
     * @param  array      $data    judul_surat, dibuat_oleh_nama, perihal?, deskripsi?,
     *                             dibuat_oleh_id?, status?, legacy_id?
     * @return Surat               Model surat yang baru tersimpan
     *
     * @throws RuntimeException Jika validasi gagal atau semua retry deadlock habis
     */
    public static function generate(
        Unit $unit,
        JenisSurat $jenis,
        Carbon $tanggal,
        array $data,
    ): Surat {
        // Langkah 1: Validasi SEBELUM transaksi
        $unit->loadMissing('company');
        self::validasiUnit($unit);

        $company = $unit->company;
        $tahun   = (int) $tanggal->format('Y');
        $bulan   = (int) $tanggal->format('n');

        $dataSurat = [
            'tipe_surat'       => 'keluar',
            'status'           => $data['status']        ?? 'Tersimpan',
            'jenis'            => $jenis->value,
            'unit_id'          => $unit->id,
            'tanggal'          => $tanggal->format('Y-m-d'),
            'judul_surat'      => $data['judul_surat'],
            'perihal'          => $data['perihal']        ?? null,
            'deskripsi'        => $data['deskripsi']      ?? null,
            'dibuat_oleh_id'   => $data['dibuat_oleh_id'] ?? null,
            'dibuat_oleh_nama' => $data['dibuat_oleh_nama'],
            'legacy_id'        => $data['legacy_id']      ?? null,
        ];

        // Langkah 2–8: Satu transaksi atomik, retry 5x saat deadlock
        return DB::transaction(function () use ($company, $tahun, $bulan, $jenis, $unit, $dataSurat) {

            // Langkah 3: Pastikan baris counter ada
            DB::statement(
                'INSERT IGNORE INTO nomor_surat_counter (perusahaan_id, tahun, nomor_terakhir) VALUES (?, ?, 0)',
                [$company->id, $tahun]
            );

            // Langkah 4: Ambil lock (static:: agar dapat di-override oleh subclass)
            $counter = static::acquireLock($company->id, $tahun);

            if ($counter === null) {
                throw new RuntimeException(
                    "Baris counter tidak ditemukan untuk perusahaan [{$company->id}] tahun {$tahun} "
                    . 'setelah INSERT IGNORE.'
                );
            }

            // Langkah 5: Increment counter
            $urut = $counter->nomor_terakhir + 1;

            DB::table('nomor_surat_counter')
                ->where('perusahaan_id', $company->id)
                ->where('tahun', $tahun)
                ->update(['nomor_terakhir' => $urut]);

            // Langkah 6: Format nomor
            $nomor = self::formatNomor(
                $urut,
                $jenis->value,
                $unit->kode,
                $company->kode_surat,
                $bulan,
                $tahun,
            );

            // Langkah 7: Simpan surat (static:: agar dapat di-override)
            return static::persistSurat([
                ...$dataSurat,
                'nomor'      => $nomor,
                'nomor_urut' => $urut,
            ]);

        }, 5); // 5 attempts: retry otomatis saat deadlock MySQL 1213
    }
}
