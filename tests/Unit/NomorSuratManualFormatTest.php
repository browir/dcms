<?php

namespace Tests\Unit;

use App\Services\NomorSuratManualService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit test murni untuk logika format dan deteksi pola nomor manual.
 * Tidak membutuhkan koneksi database.
 */
class NomorSuratManualFormatTest extends TestCase
{
    #[Test]
    public function normalisasi_nomor_menghilangkan_spasi_di_awal_dan_akhir(): void
    {
        $this->assertSame('001/G.3C/SGG/IX/2026', NomorSuratManualService::normalize('   001/G.3C/SGG/IX/2026   '));
    }

    #[Test]
    public function normalisasi_nomor_merapikan_spasi_berlebih_di_tengah(): void
    {
        $this->assertSame('001 / G.3C / SGG / IX / 2026', NomorSuratManualService::normalize('001    /   G.3C   /   SGG   /   IX   /   2026'));
    }

    #[Test]
    public function normalisasi_nomor_null_dan_string_kosong_menghasilkan_string_kosong(): void
    {
        $this->assertSame('', NomorSuratManualService::normalize(null));
        $this->assertSame('', NomorSuratManualService::normalize(''));
        $this->assertSame('', NomorSuratManualService::normalize("   \t  \n  "));
    }

    #[Test]
    public function mendeteksi_format_standar_dengan_benar(): void
    {
        // Format standar: {urut:3+}/{jenis}.{unit}/{perusahaan}/{bulan_romawi}/{tahun}
        $validStandard = [
            '001/G.3C/SGG/IX/2025',
            '042/A.1A/MEDIKA/I/2026',
            '1000/B.IT-DEV/HOLDING/XII/2024',
            '9999/P.HRD/RSU-SM/XI/2026',
        ];

        foreach ($validStandard as $nomor) {
            $this->assertTrue(
                NomorSuratManualService::matchesStandardPattern($nomor),
                "Gagal mendeteksi '{$nomor}' sebagai format standar."
            );
            $this->assertNull(
                NomorSuratManualService::getPatternWarning($nomor),
                "Pola standar '{$nomor}' tidak boleh menghasilkan peringatan."
            );
        }
    }

    #[Test]
    public function memberikan_peringatan_non_blocking_jika_tidak_sesuai_format_standar(): void
    {
        $nonStandard = [
            '001/SK-DIR/2026',
            'MANUAL-2026-001',
            '42/A.1A/SGG/I/2026', // urut hanya 2 digit
            'SURAT-TUGAS/01',
            '001/G/SGG/IX/2026', // jenis tidak ada titik unit
        ];

        foreach ($nonStandard as $nomor) {
            $this->assertFalse(
                NomorSuratManualService::matchesStandardPattern($nomor),
                "Nomor '{$nomor}' seharusnya tidak dianggap format standar."
            );
            $warning = NomorSuratManualService::getPatternWarning($nomor);
            $this->assertNotNull($warning, "Nomor '{$nomor}' harus menghasilkan teks peringatan.");
            $this->assertStringContainsString('tidak mengikuti format standar', $warning);
        }
    }

    #[Test]
    public function get_pattern_warning_mengembalikan_null_untuk_input_kosong(): void
    {
        $this->assertNull(NomorSuratManualService::getPatternWarning(''));
        $this->assertNull(NomorSuratManualService::getPatternWarning('   '));
    }
}

