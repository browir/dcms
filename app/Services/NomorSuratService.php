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
 * Pola counter (atomik, tanpa SELECT FOR UPDATE):
 *   INSERT INTO nomor_surat_counter (perusahaan_id, tahun, nomor_terakhir)
 *   VALUES (?, ?, LAST_INSERT_ID(1))
 *   ON DUPLICATE KEY UPDATE nomor_terakhir = LAST_INSERT_ID(nomor_terakhir + 1)
 *   --
 *   SELECT LAST_INSERT_ID() → nilai urut yang baru saja di-assign untuk koneksi ini.
 *
 * Untuk keperluan test, dua method protected dapat di-override via subclass:
 *   - static::incrementCounter() — increment counter, kembalikan urut
 *   - static::persistSurat()    — simpan surat ke DB
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

    /**
     * Jumlah retry yang dilakukan oleh DB::transaction() pada pemanggilan generate() terakhir.
     * 0 = tidak ada deadlock, 1 = satu kali retry, dst.
     * Tersedia di level service-process setelah generate() selesai.
     * Tidak thread-safe lintas proses; setiap proses worker punya nilainya sendiri.
     */
    public static int $lastDeadlockRetries = 0;

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
     * Increment counter secara atomik dan kembalikan nomor urut yang baru.
     *
     * Implementasi default menggunakan:
     *   INSERT ... ON DUPLICATE KEY UPDATE nomor_terakhir = LAST_INSERT_ID(nomor_terakhir + 1)
     *   diikuti SELECT LAST_INSERT_ID()
     *
     * Pola ini atomik di level MySQL (satu pernyataan, satu row-lock implisit)
     * tanpa membutuhkan SELECT FOR UPDATE terpisah. LAST_INSERT_ID() bersifat
     * session-scoped sehingga aman dalam transaksi concurrent.
     *
     * Dapat di-override oleh subclass test untuk mensimulasikan race condition
     * (kontrol negatif). JANGAN override di kode produksi.
     *
     * @return positive-int Nomor urut baru
     * @throws RuntimeException jika LAST_INSERT_ID() mengembalikan 0
     */
    public static function incrementCounter(int $perusahaanId, int $tahun): int
    {
        DB::statement(
            'INSERT INTO nomor_surat_counter (perusahaan_id, tahun, nomor_terakhir) '
            . 'VALUES (?, ?, LAST_INSERT_ID(1)) '
            . 'ON DUPLICATE KEY UPDATE nomor_terakhir = LAST_INSERT_ID(nomor_terakhir + 1)',
            [$perusahaanId, $tahun]
        );

        $urut = (int) DB::selectOne('SELECT LAST_INSERT_ID() AS urut', [], false)->urut;

        if ($urut < 1) {
            throw new RuntimeException(
                "LAST_INSERT_ID() mengembalikan nilai tidak valid ({$urut}) "
                . "untuk perusahaan={$perusahaanId} tahun={$tahun}. "
                . 'Pastikan statement INSERT ON DUPLICATE KEY UPDATE berjalan dalam transaksi aktif.'
            );
        }

        return $urut;
    }

    /**
     * Menyimpan surat ke database.
     *
     * Dapat di-override oleh subclass test untuk mensimulasikan kegagalan
     * setelah counter di-increment (test rollback atomicity).
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

        if (trim((string) $unit->kode) === '') {
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

        if (trim((string) $company->kode_surat) === '') {
            throw new RuntimeException(
                "Perusahaan [{$company->id}] \"{$company->name}\" belum memiliki kode_surat. "
                . 'Jalankan perintah surat:seed-perusahaan atau isi companies.kode_surat secara manual.'
            );
        }

        // Cek inkonsistensi company antara unit dan department-nya
        $unit->loadMissing('department');
        $dept = $unit->department;
        if (
            $dept !== null
            && $unit->company_id !== null
            && $dept->company_id !== null
            && (int) $unit->company_id !== (int) $dept->company_id
        ) {
            throw new RuntimeException(
                "Inkonsistensi data: unit [{$unit->id}] \"{$unit->name}\" "
                . "memiliki company_id={$unit->company_id} "
                . "tetapi department [{$dept->id}] \"{$dept->name}\" "
                . "memiliki company_id={$dept->company_id}. "
                . 'Periksa dan selaraskan data sebelum membuat surat keluar.'
            );
        }
    }

    // ── Fungsi utama generate ────────────────────────────────────────────────

    /**
     * Generate nomor surat keluar dan simpan surat baru dalam satu transaksi atomik.
     *
     * Algoritma:
     *   1. Validasi (unit.kode, unit.company_id, company.kode_surat) SEBELUM transaksi.
     *   2. Buka transaksi (retry hingga 5x saat deadlock MySQL SQLSTATE 40001).
     *   3. static::incrementCounter() — INSERT ON DUPLICATE KEY UPDATE + SELECT LAST_INSERT_ID()
     *   4. Format nomor surat.
     *   5. static::persistSurat() — INSERT surat.
     *   6. COMMIT. Rollback otomatis jika ada kegagalan di langkah 3–5.
     *
     * Setelah selesai, jumlah retry karena deadlock tersedia di:
     *   NomorSuratService::$lastDeadlockRetries
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
            'numbering_type'   => 'auto',
            'jenis'            => $jenis->value,
            'unit_id'          => $unit->id,
            'tanggal'          => $tanggal->format('Y-m-d'),
            'judul_surat'      => $data['judul_surat'],
            'perihal'          => $data['perihal']        ?? null,
            'deskripsi'        => $data['deskripsi']      ?? null,
            'dibuat_oleh_id'   => $data['dibuat_oleh_id'] ?? null,
            'dibuat_oleh_nama' => $data['dibuat_oleh_nama'] ?? 'Sistem',
            'legacy_id'        => $data['legacy_id']      ?? null,
        ];

        // Hitung percobaan transaksi untuk melaporkan retry deadlock
        $transactionAttempts = 0;

        // Langkah 2–6: Satu transaksi atomik, retry hingga 5x saat deadlock
        $surat = DB::transaction(function () use (
            $company, $tahun, $bulan, $jenis, $unit, $dataSurat, &$transactionAttempts
        ) {
            $transactionAttempts++;

            // Langkah 3 & 4: Increment counter dan format nomor.
            // Edge case: Jika nomor yang dihasilkan sudah pernah dipakai (misal oleh nomor manual),
            // lewati nomor tersebut dan ulangi increment sampai nomor yang belum terpakai ditemukan.
            do {
                $urut = static::incrementCounter($company->id, $tahun);

                $nomor = self::formatNomor(
                    $urut,
                    $jenis->value,
                    $unit->kode,
                    $company->kode_surat,
                    $bulan,
                    $tahun,
                );
            } while (Surat::withTrashed()->where('nomor', $nomor)->exists());

            // Langkah 5: Simpan surat (static:: untuk late binding)
            return static::persistSurat([
                ...$dataSurat,
                'nomor'          => $nomor,
                'nomor_urut'     => $urut,
                'numbering_type' => 'auto',
            ]);

        }, 5); // 5 attempts: retry otomatis saat deadlock SQLSTATE 40001

        // Simpan jumlah retry untuk pelaporan (0 = tidak ada deadlock)
        self::$lastDeadlockRetries = $transactionAttempts - 1;

        return $surat;
    }
}
