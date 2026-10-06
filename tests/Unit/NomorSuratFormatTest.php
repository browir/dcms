<?php

namespace Tests\Unit;

use App\Services\NomorSuratService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit test untuk fungsi-fungsi format di NomorSuratService.
 *
 * Test ini TIDAK menggunakan database — semua fungsi yang diuji adalah murni
 * (pure functions: input → output, tanpa side effect).
 * Ini membuatnya cepat dan bisa berjalan di lingkungan manapun.
 */
class NomorSuratFormatTest extends TestCase
{
    // ── formatUrut() ─────────────────────────────────────────────────────────

    #[Test]
    public function format_urut_satu_digit_jadi_tiga_digit(): void
    {
        $this->assertSame('001', NomorSuratService::formatUrut(1));
    }

    #[Test]
    public function format_urut_dua_digit_jadi_tiga_digit(): void
    {
        $this->assertSame('042', NomorSuratService::formatUrut(42));
    }

    #[Test]
    public function format_urut_tepat_tiga_digit(): void
    {
        $this->assertSame('100', NomorSuratService::formatUrut(100));
        $this->assertSame('999', NomorSuratService::formatUrut(999));
    }

    #[Test]
    public function format_urut_di_atas_999_tanpa_padding(): void
    {
        $this->assertSame('1000', NomorSuratService::formatUrut(1000));
        $this->assertSame('1001', NomorSuratService::formatUrut(1001));
        $this->assertSame('9999', NomorSuratService::formatUrut(9999));
    }

    #[Test]
    public function format_urut_nol_melempar_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/harus >= 1/');
        NomorSuratService::formatUrut(0);
    }

    #[Test]
    public function format_urut_negatif_melempar_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        NomorSuratService::formatUrut(-1);
    }

    // ── bulanRomawi() ─────────────────────────────────────────────────────────

    #[Test]
    public function bulan_romawi_semua_nilai(): void
    {
        $ekspektasi = [
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

        foreach ($ekspektasi as $angka => $romawi) {
            $this->assertSame(
                $romawi,
                NomorSuratService::bulanRomawi($angka),
                "Bulan {$angka} seharusnya '{$romawi}'"
            );
        }
    }

    #[Test]
    public function bulan_romawi_nol_melempar_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/antara 1 dan 12/');
        NomorSuratService::bulanRomawi(0);
    }

    #[Test]
    public function bulan_romawi_13_melempar_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/antara 1 dan 12/');
        NomorSuratService::bulanRomawi(13);
    }

    // ── formatNomor() ─────────────────────────────────────────────────────────

    #[Test]
    public function format_nomor_contoh_dari_spesifikasi(): void
    {
        // Contoh dari spesifikasi: 004/G.3C/SGG/IX/2025
        $hasil = NomorSuratService::formatNomor(
            urut: 4,
            kodeJenis: 'G',
            kodeUnit: '3C',
            kodePerusahaan: 'SGG',
            bulan: 9,
            tahun: 2025,
        );

        $this->assertSame('004/G.3C/SGG/IX/2025', $hasil);
    }

    #[Test]
    public function format_nomor_surat_peringatan_kode_dua_karakter(): void
    {
        // SP = Surat Peringatan (kode dua karakter)
        $hasil = NomorSuratService::formatNomor(
            urut: 1,
            kodeJenis: 'SP',
            kodeUnit: 'HRD',
            kodePerusahaan: 'RSUSM',
            bulan: 1,
            tahun: 2025,
        );

        $this->assertSame('001/SP.HRD/RSUSM/I/2025', $hasil);
    }

    #[Test]
    public function format_nomor_kode_jenis_panjang(): void
    {
        // PKWTT = Perjanjian Kerja Waktu Tidak Tentu (5 karakter)
        $hasil = NomorSuratService::formatNomor(
            urut: 10,
            kodeJenis: 'PKWTT',
            kodeUnit: 'DIR',
            kodePerusahaan: 'SGG',
            bulan: 12,
            tahun: 2025,
        );

        $this->assertSame('010/PKWTT.DIR/SGG/XII/2025', $hasil);
    }

    #[Test]
    public function format_nomor_urut_di_atas_999(): void
    {
        // Urutan > 999 tidak boleh error, hanya 4 digit (tanpa padding)
        $hasil = NomorSuratService::formatNomor(
            urut: 1000,
            kodeJenis: 'A',
            kodeUnit: 'DIR',
            kodePerusahaan: 'SGG',
            bulan: 6,
            tahun: 2025,
        );

        $this->assertSame('1000/A.DIR/SGG/VI/2025', $hasil);
    }

    #[Test]
    public function format_nomor_bulan_januari_menggunakan_I(): void
    {
        $hasil = NomorSuratService::formatNomor(1, 'A', 'IT', 'SGG', 1, 2026);
        $this->assertStringContainsString('/I/2026', $hasil);
    }

    #[Test]
    public function format_nomor_bulan_desember_menggunakan_XII(): void
    {
        $hasil = NomorSuratService::formatNomor(1, 'A', 'IT', 'SGG', 12, 2025);
        $this->assertStringContainsString('/XII/2025', $hasil);
    }

    // ── Tes pergantian tahun / surat bertanggal tahun lalu ───────────────────

    /**
     * Surat yang tanggalnya tahun 2025 (dibuat di 2026) harus pakai angka
     * tahun 2025 di nomor surat — bukan tahun hari ini.
     * Fungsi formatNomor() sudah benar karena menerima tahun eksplisit.
     * Test ini mendokumentasikan kontrak bahwa caller harus meneruskan tahun
     * dari tanggal surat, bukan dari tanggal sistem.
     */
    #[Test]
    public function surat_bertanggal_2025_dibuat_di_2026_memakai_tahun_2025(): void
    {
        $tanggalSurat = new \DateTime('2025-12-31');
        $tahunSurat   = (int) $tanggalSurat->format('Y'); // 2025, bukan 2026

        $hasil = NomorSuratService::formatNomor(
            urut: 50,
            kodeJenis: 'G',
            kodeUnit: '3C',
            kodePerusahaan: 'SGG',
            bulan: (int) $tanggalSurat->format('n'), // 12
            tahun: $tahunSurat,
        );

        $this->assertSame('050/G.3C/SGG/XII/2025', $hasil);
        $this->assertStringNotContainsString('2026', $hasil);
    }

    /**
     * Pergantian tahun: counter 2025 dan 2026 harus menghasilkan nomor
     * dengan tahun yang berbeda meski kode perusahaan dan unit sama.
     */
    #[Test]
    public function nomor_berbeda_tahun_mengandung_tahun_yang_benar(): void
    {
        $nomor2025 = NomorSuratService::formatNomor(1, 'G', '3C', 'SGG', 12, 2025);
        $nomor2026 = NomorSuratService::formatNomor(1, 'G', '3C', 'SGG', 1, 2026);

        $this->assertStringEndsWith('/2025', $nomor2025);
        $this->assertStringEndsWith('/2026', $nomor2026);
        $this->assertNotSame($nomor2025, $nomor2026);
    }

    /**
     * Urutan per tahun: nomor pertama tahun 2026 dimulai dari 001,
     * tidak melanjutkan urutan tahun sebelumnya.
     * (Counter-nya mandiri per tahun — ini didokumentasikan di test ini
     * meski logika counter ada di generate(); test format saja cukup untuk
     * membuktikan format string sudah benar.)
     */
    #[Test]
    public function nomor_pertama_tahun_baru_dimulai_dari_001(): void
    {
        $nomorAkhir2025 = NomorSuratService::formatNomor(999, 'G', '3C', 'SGG', 12, 2025);
        $nomorAwal2026  = NomorSuratService::formatNomor(1, 'G', '3C', 'SGG', 1, 2026);

        $this->assertStringStartsWith('999/', $nomorAkhir2025);
        $this->assertStringStartsWith('001/', $nomorAwal2026);
    }
}
