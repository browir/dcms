<?php

namespace App\Services;

use App\Enums\JenisSurat;
use App\Models\NomorSuratCounter;
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
     * Pisahkan dari generate() agar mudah diuji tanpa database.
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

    // ── Validasi input (melempar exception sebelum counter disentuh) ─────────

    /**
     * Validasi semua prasyarat sebelum counter disentuh.
     * Melempar RuntimeException jika ada yang tidak memenuhi syarat.
     *
     * @param  Unit $unit Unit yang membuat surat
     * @throws RuntimeException
     */
    private static function validasiUnit(Unit $unit): void
    {
        // 1. Pastikan unit punya company_id
        if ($unit->company_id === null) {
            throw new RuntimeException(
                "Unit [{$unit->id}] \"{$unit->name}\" belum memiliki company_id. "
                . 'Isi units.company_id terlebih dahulu sebelum membuat surat keluar.'
            );
        }

        // 2. Pastikan unit punya kode
        if (empty(trim((string) $unit->kode))) {
            throw new RuntimeException(
                "Unit [{$unit->id}] \"{$unit->name}\" belum memiliki kode unit. "
                . 'Isi units.kode terlebih dahulu sebelum membuat surat keluar.'
            );
        }

        // 3. Load relasi company jika belum di-eager-load
        $company = $unit->company;

        if ($company === null) {
            throw new RuntimeException(
                "Perusahaan untuk unit [{$unit->id}] \"{$unit->name}\" tidak ditemukan "
                . "(company_id={$unit->company_id}). Periksa integritas data."
            );
        }

        // 4. Pastikan perusahaan punya kode_surat
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
     * Algoritma (seluruhnya dalam satu transaksi):
     *   1. Validasi prasyarat (unit.kode, unit.company_id, company.kode_surat).
     *      Dilakukan SEBELUM transaksi dibuka agar tidak ada lock yang terbuang.
     *   2. Buka transaksi.
     *   3. INSERT IGNORE baris counter jika belum ada (nomor_terakhir = 0).
     *   4. SELECT ... FOR UPDATE pada baris counter (lock eksklusif).
     *   5. urut = nomor_terakhir + 1, UPDATE nomor_terakhir = urut.
     *   6. Format nomor surat.
     *   7. INSERT surat dengan nomor terformat.
     *   8. COMMIT. Rollback otomatis jika ada kegagalan di langkah 2–7.
     *
     * @param  Unit       $unit      Unit pembuat surat (harus sudah ada company via $unit->company)
     * @param  JenisSurat $jenis     Jenis surat dari enum
     * @param  Carbon     $tanggal   Tanggal surat (bulan, tahun, dan counter diambil dari sini)
     * @param  array      $data      Data surat: judul_surat, perihal?, deskripsi?,
     *                               dibuat_oleh_id?, dibuat_oleh_nama
     * @return Surat                 Model surat yang baru dibuat (sudah tersimpan di DB)
     *
     * @throws RuntimeException      Jika validasi gagal atau transaksi gagal
     */
    public static function generate(
        Unit $unit,
        JenisSurat $jenis,
        Carbon $tanggal,
        array $data,
    ): Surat {
        // ── Langkah 1: Validasi SEBELUM transaksi (tidak ada counter yang terbuang) ──
        // Muat relasi company sekali saja agar validasi dan format pakai data yang sama
        $unit->loadMissing('company');
        self::validasiUnit($unit);

        $company = $unit->company; // sudah divalidasi tidak null
        $tahun   = (int) $tanggal->format('Y');
        $bulan   = (int) $tanggal->format('n');

        // Siapkan data surat (field wajib dan opsional)
        $dataSurat = [
            'tipe_surat'       => 'keluar',
            'status'           => $data['status']      ?? 'Tersimpan',
            'jenis'            => $jenis->value,
            'unit_id'          => $unit->id,
            'tanggal'          => $tanggal->format('Y-m-d'),
            'judul_surat'      => $data['judul_surat'],
            'perihal'          => $data['perihal']       ?? null,
            'deskripsi'        => $data['deskripsi']     ?? null,
            'dibuat_oleh_id'   => $data['dibuat_oleh_id'] ?? null,
            'dibuat_oleh_nama' => $data['dibuat_oleh_nama'],
            'legacy_id'        => $data['legacy_id']    ?? null,
        ];

        // ── Langkah 2–8: Satu transaksi atomik ──────────────────────────────
        return DB::transaction(function () use ($unit, $jenis, $tanggal, $company, $tahun, $bulan, $dataSurat) {

            // Langkah 3: Pastikan baris counter ada (INSERT IGNORE — idempotent)
            DB::statement(
                'INSERT IGNORE INTO nomor_surat_counter (perusahaan_id, tahun, nomor_terakhir) VALUES (?, ?, 0)',
                [$company->id, $tahun]
            );

            // Langkah 4: SELECT FOR UPDATE — lock eksklusif baris counter
            $counter = DB::table('nomor_surat_counter')
                ->where('perusahaan_id', $company->id)
                ->where('tahun', $tahun)
                ->lockForUpdate()
                ->first();

            if ($counter === null) {
                // Seharusnya tidak terjadi setelah INSERT IGNORE di atas
                throw new RuntimeException(
                    "Baris counter tidak ditemukan untuk perusahaan [{$company->id}] tahun {$tahun} "
                    . 'setelah INSERT IGNORE. Periksa hak akses tabel nomor_surat_counter.'
                );
            }

            // Langkah 5: Hitung urut baru dan update counter
            $urut = $counter->nomor_terakhir + 1;

            DB::table('nomor_surat_counter')
                ->where('perusahaan_id', $company->id)
                ->where('tahun', $tahun)
                ->update(['nomor_terakhir' => $urut]);

            // Langkah 6: Format nomor surat
            $nomor = self::formatNomor(
                $urut,
                $jenis->value,
                $unit->kode,
                $company->kode_surat,
                $bulan,
                $tahun,
            );

            // Langkah 7: INSERT surat
            // Kolom 'nomor' punya UNIQUE constraint sebagai pengaman kedua.
            $surat = Surat::create([
                ...$dataSurat,
                'nomor'      => $nomor,
                'nomor_urut' => $urut,
            ]);

            return $surat;
        });
    }
}
