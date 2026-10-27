<?php

namespace App\Enums;

/**
 * Jenis-jenis surat keluar yang dikenal sistem.
 *
 * Kode (value) disimpan di kolom surat.jenis (varchar 10).
 * Urutan case TIDAK boleh diubah sembarangan jika ada logika yang bergantung
 * pada urutan (misalnya indeks warna label).
 *
 * Cara menambah jenis baru:
 *   1. Tambah case di bawah SKK.
 *   2. Tambah label di metode label().
 *   3. Jalankan migration jika ada kolom DB yang menyimpan enum ini.
 *      (Tabel surat memakai varchar, jadi tidak perlu migrasi kolom.)
 */
enum JenisSurat: string
{
    case A     = 'A';
    case B     = 'B';
    case C     = 'C';
    case D     = 'D';
    case E     = 'E';
    case F     = 'F';
    case G     = 'G';
    case H     = 'H';
    case I     = 'I';
    case J     = 'J';
    case K     = 'K';
    case L     = 'L';
    case M     = 'M';
    case N     = 'N';
    case O     = 'O';
    case SP    = 'SP';
    case P     = 'P';
    case MCU   = 'MCU';
    case SOP   = 'SOP';
    case FRM   = 'FRM';
    case Q     = 'Q';
    case PKWT  = 'PKWT';
    case PKWTT = 'PKWTT';
    case CERT  = 'CERT';
    case SPKK  = 'SPKK';
    case SKK   = 'SKK';

    /** Kembalikan label deskriptif Bahasa Indonesia. */
    public function label(): string
    {
        return match ($this) {
            self::A     => 'Kebijakan / Peraturan / PP',
            self::B     => 'Pedoman / Panduan Pelaksanaan',
            self::C     => 'Surat Edaran / Pengumuman',
            self::D     => 'Internal Memo',
            self::E     => 'Surat Keputusan',
            self::F     => 'Surat Perintah',
            self::G     => 'Surat Tugas',
            self::H     => 'Surat Undangan',
            self::I     => 'Surat Kuasa',
            self::J     => 'Berita Acara',
            self::K     => 'Surat Perjanjian / MoU',
            self::L     => 'Surat Permohonan Barang / Jasa',
            self::M     => 'Surat Balasan',
            self::N     => 'Surat Tanda Terima',
            self::O     => 'Surat Pengantar',
            self::SP    => 'Surat Peringatan',
            self::P     => 'Surat Rekomendasi',
            self::MCU   => 'Surat MCU',
            self::SOP   => 'Standard Operational Procedure',
            self::FRM   => 'Formulir',
            self::Q     => 'Surat Keterangan',
            self::PKWT  => 'Perjanjian Kerja Waktu Tertentu',
            self::PKWTT => 'Perjanjian Kerja Waktu Tidak Tentu',
            self::CERT  => 'Sertifikat',
            self::SPKK  => 'Surat Penugasan Kerja Klinik',
            self::SKK   => 'Surat Keterangan Kesehatan',
        };
    }

    /**
     * Kembalikan semua kasus sebagai array [value => label] untuk dropdown Filament/select.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn (self $j) => $j->label(), self::cases()),
        );
    }
}
