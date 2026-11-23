<?php

use App\Models\DocumentTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $spt = DocumentTemplate::where('code', 'RSUSM-SPT')->first();
        if ($spt) {
            $combinedContent = '<p style="text-align: center;"><strong><span style="font-size: 14pt;">SURAT PERINTAH TUGAS</span></strong><br>Nomor: {nomor_surat}</p><br><p>Yang bertanda tangan di bawah ini:</p><table style="width: 100%; border: none; margin-left: 20px;"><tbody><tr><td style="width: 25%; border: none;"><strong>Nama</strong></td><td style="border: none;">: {nama_direktur}</td></tr><tr><td style="width: 25%; border: none;"><strong>NIP</strong></td><td style="border: none;">: {nip_direktur}</td></tr><tr><td style="width: 25%; border: none;"><strong>Jabatan</strong></td><td style="border: none;">: Direktur</td></tr></tbody></table><br><p>Dengan ini memberikan tugas kepada:</p><table style="width: 100%; border-collapse: collapse; border: 1px solid #333;" border="1"><thead><tr style="background-color: #f2f2f2;"><th style="width: 40%; padding: 8px;">Nama</th><th style="width: 35%; padding: 8px;">Jabatan</th><th style="width: 25%; padding: 8px;">NIP</th></tr></thead><tbody><tr><td style="padding: 8px;">[Nama Petugas 1]</td><td style="padding: 8px;">[Jabatan 1]</td><td style="padding: 8px;">[NIP 1]</td></tr><tr><td style="padding: 8px;">[Nama Petugas 2]</td><td style="padding: 8px;">[Jabatan 2]</td><td style="padding: 8px;">[NIP 2]</td></tr></tbody></table><br><p>Dalam rangka <strong>[Kegiatan / Keperluan Penugasan]</strong> yang akan dilaksanakan pada:</p><table style="width: 100%; border: none; margin-left: 20px;"><tbody><tr><td style="width: 25%; border: none;"><strong>Hari, Tanggal</strong></td><td style="border: none;">: [Hari, Tanggal Pelaksanaan]</td></tr><tr><td style="width: 25%; border: none;"><strong>Waktu</strong></td><td style="border: none;">: [00.00] WITA s.d. Selesai</td></tr><tr><td style="width: 25%; border: none;"><strong>Tempat</strong></td><td style="border: none;">: [Lokasi Penugasan]</td></tr></tbody></table><br><p>Demikian surat tugas ini diberikan kepada yang bersangkutan untuk dilaksanakan sebagaimana mestinya.</p><br><table class="letter-signature-table" style="width: 100%; border: none;"><tbody><tr><td style="width: 58%; border: none;">&nbsp;</td><td class="letter-signature-block" style="width: 42%; border: none; text-align: left;">Banjarbaru, {tanggal}<br><strong>Direktur,</strong><br><br><br><br><strong><u>{nama_direktur}</u></strong><br>NIP. {nip_direktur}</td></tr></tbody></table><div style="page-break-before: always; break-before: page; margin-top: 30px;"></div><p style="text-align: center;"><strong><span style="font-size: 14pt;">SURAT PERINTAH PERJALANAN DINAS (SPPD)</span></strong><br>Nomor: {nomor_surat}</p><br><table style="width: 100%; border-collapse: collapse; border: 1px solid #333;" border="1"><tbody><tr><td style="width: 5%; padding: 8px; text-align: center;">1.</td><td style="width: 45%; padding: 8px;">Pejabat berwenang pemberi perintah</td><td style="width: 50%; padding: 8px;">Direktur RSU Syifa Medika Banjarbaru</td></tr><tr><td style="width: 5%; padding: 8px; text-align: center;">2.</td><td style="width: 45%; padding: 8px;">Nama karyawan yang diberi perintah</td><td style="width: 50%; padding: 8px;">[Nama Karyawan]</td></tr><tr><td style="width: 5%; padding: 8px; text-align: center;">3.</td><td style="width: 45%; padding: 8px;">Jabatan</td><td style="width: 50%; padding: 8px;">[Jabatan Karyawan]</td></tr><tr><td style="width: 5%; padding: 8px; text-align: center;">4.</td><td style="width: 45%; padding: 8px;">Tempat yang dituju</td><td style="width: 50%; padding: 8px;">[Kota / Tempat Tujuan]</td></tr><tr><td style="width: 5%; padding: 8px; text-align: center;">5.</td><td style="width: 45%; padding: 8px;">Tujuan penugasan</td><td style="width: 50%; padding: 8px;">[Tujuan Perjalanan Dinas]</td></tr><tr><td style="width: 5%; padding: 8px; text-align: center;">6.</td><td style="width: 45%; padding: 8px;">Hari / tanggal tugas</td><td style="width: 50%; padding: 8px;">[Hari, Tanggal Perjalanan]</td></tr><tr><td style="width: 5%; padding: 8px; text-align: center;">7.</td><td style="width: 45%; padding: 8px;">Transportasi yang digunakan</td><td style="width: 50%; padding: 8px;">[Kendaraan Dinas / Umum / Pesawat]</td></tr><tr><td style="width: 5%; padding: 8px; text-align: center;">8.</td><td style="width: 45%; padding: 8px;">Nama, tanda tangan petugas dan stempel tempat yang dikunjungi</td><td style="width: 50%; padding: 8px; height: 80px;"><br><br><br></td></tr></tbody></table><br><table class="letter-signature-table" style="width: 100%; border: none;"><tbody><tr><td style="width: 58%; border: none;">&nbsp;</td><td class="letter-signature-block" style="width: 42%; border: none; text-align: left;">Banjarbaru, {tanggal}<br><strong>Direktur,</strong><br><br><br><br><strong><u>{nama_direktur}</u></strong><br>NIP. {nip_direktur}</td></tr></tbody></table>';

            $spt->update([
                'name' => 'Surat Perintah Tugas (SPT) & SPPD RSU Syifa Medika Banjarbaru',
                'description' => 'Template resmi 1 paket Surat Perintah Tugas beserta Surat Perintah Perjalanan Dinas (SPPD) RSU Syifa Medika Banjarbaru',
                'template_content' => $combinedContent,
                'template_fields' => [
                    ['name' => 'tugas', 'label' => 'Tugas Yang Diberikan', 'type' => 'text', 'required' => true],
                    ['name' => 'hari_tanggal', 'label' => 'Hari, Tanggal', 'type' => 'text', 'required' => true],
                    ['name' => 'waktu', 'label' => 'Waktu', 'type' => 'text', 'required' => true],
                    ['name' => 'tempat', 'label' => 'Tempat / Kota Tujuan', 'type' => 'text', 'required' => true],
                    ['name' => 'nama_karyawan', 'label' => 'Nama Karyawan', 'type' => 'text', 'required' => false],
                    ['name' => 'jabatan', 'label' => 'Jabatan', 'type' => 'text', 'required' => false],
                    ['name' => 'transportasi', 'label' => 'Transportasi', 'type' => 'text', 'required' => false],
                ],
            ]);
        }
    }

    public function down(): void
    {
        // No destructive rollback needed
    }
};

