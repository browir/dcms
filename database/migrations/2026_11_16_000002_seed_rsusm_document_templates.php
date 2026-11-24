<?php

use App\Models\Company;
use App\Models\DocumentCategory;
use App\Models\DocumentTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $company = Company::find(5) ?? Company::where('code', 'RSUSMBJB')->first();
        if (! $company) {
            return;
        }

        $companyId = $company->id;

        $catD = DocumentCategory::where('company_id', $companyId)->where('code', 'D')->first();
        $catJ = DocumentCategory::where('company_id', $companyId)->where('code', 'J')->first();
        $catN = DocumentCategory::where('company_id', $companyId)->where('code', 'N')->first();
        $catO = DocumentCategory::where('company_id', $companyId)->where('code', 'O')->first();
        $catG = DocumentCategory::where('company_id', $companyId)->where('code', 'G')->first();

        // 1. SURAT EDARAN (Kode D)
        if ($catD) {
            DocumentTemplate::updateOrCreate(
                ['code' => 'RSUSM-SE'],
                [
                    'name' => 'Surat Edaran RSU Syifa Medika Banjarbaru',
                    'description' => 'Template resmi Surat Edaran internal/eksternal RSU Syifa Medika Banjarbaru',
                    'company_id' => $companyId,
                    'category_id' => $catD->id,
                    'is_active' => true,
                    'allow_file_upload' => true,
                    'allow_manual_input' => true,
                    'template_fields' => [
                        ['name' => 'tentang', 'label' => 'Tentang', 'type' => 'text', 'required' => true],
                        ['name' => 'tujuan', 'label' => 'Tujuan Edaran', 'type' => 'text', 'required' => true],
                    ],
                    'template_content' => '<p style="text-align: center;"><strong><span style="font-size: 14pt;">SURAT EDARAN</span></strong><br>Nomor: {nomor_surat}</p><p style="text-align: center;"><strong>TENTANG<br>[JUDUL / TENTANG EDARAN]</strong></p><p><br>Yth.<br>1. [Penerima 1]<br>2. [Penerima 2]<br>di Tempat</p><p>Dengan hormat,</p><p>[Tuliskan isi surat edaran di sini...]</p><p>Demikian Surat Edaran ini disampaikan untuk menjadi perhatian dan dilaksanakan sebagaimana mestinya.</p><br><table class="letter-signature-table" style="width: 100%; border: none;"><tbody><tr><td style="width: 58%; border: none;">&nbsp;</td><td class="letter-signature-block" style="width: 42%; border: none; text-align: left;">Banjarbaru, {tanggal}<br><strong>Direktur,</strong><br><br><br><br><strong><u>{nama_direktur}</u></strong><br>NIP. {nip_direktur}</td></tr></tbody></table>',
                ]
            );
        }

        // 2. SURAT UNDANGAN (Kode J)
        if ($catJ) {
            DocumentTemplate::updateOrCreate(
                ['code' => 'RSUSM-UND'],
                [
                    'name' => 'Surat Undangan RSU Syifa Medika Banjarbaru',
                    'description' => 'Template resmi Surat Undangan RSU Syifa Medika Banjarbaru dengan lampiran daftar undangan',
                    'company_id' => $companyId,
                    'category_id' => $catJ->id,
                    'is_active' => true,
                    'allow_file_upload' => true,
                    'allow_manual_input' => true,
                    'template_fields' => [
                        ['name' => 'perihal', 'label' => 'Perihal', 'type' => 'text', 'required' => true],
                        ['name' => 'hari_tanggal', 'label' => 'Hari, Tanggal', 'type' => 'text', 'required' => true],
                        ['name' => 'pukul', 'label' => 'Pukul', 'type' => 'text', 'required' => true],
                        ['name' => 'tempat', 'label' => 'Tempat', 'type' => 'text', 'required' => true],
                    ],
                    'template_content' => '<table class="letter-meta-table" style="width: 100%; border: none;"><tbody><tr><td style="width: 60%; border: none; vertical-align: top;">Nomor : {nomor_surat}<br>Lampiran : Satu lembar<br>Perihal : <strong>Undangan [Nama Pertemuan/Kegiatan]</strong></td><td style="width: 40%; border: none; vertical-align: top; text-align: right;">Banjarbaru, {tanggal}</td></tr></tbody></table><br><p>Yth.<br><strong>[Bapak/Ibu/Saudara/i Penerima Undangan]</strong><br>(daftar undangan terlampir)<br>di Tempat</p><p>Dengan hormat,</p><p>Sehubungan akan diadakannya [Nama Agenda Kegiatan], maka kami mengundang Bapak/Ibu untuk dapat berhadir pada:</p><table class="letter-layout-table" style="width: 100%; border: none; margin-left: 20px;"><tbody><tr><td style="width: 25%; border: none;"><strong>Hari, Tanggal</strong></td><td style="border: none;">: [Hari, Tanggal]</td></tr><tr><td style="width: 25%; border: none;"><strong>Pukul</strong></td><td style="border: none;">: [00.00] WITA s.d. Selesai</td></tr><tr><td style="width: 25%; border: none;"><strong>Tempat</strong></td><td style="border: none;">: [Ruang Rapat / Aula RSUSM]</td></tr></tbody></table><p>Demikian undangan ini kami sampaikan, atas perhatian dan kerja sama Bapak/Ibu, kami ucapkan terima kasih.</p><br><table class="letter-signature-table" style="width: 100%; border: none;"><tbody><tr><td style="width: 58%; border: none;">&nbsp;</td><td class="letter-signature-block" style="width: 42%; border: none; text-align: left;">Hormat kami,<br><strong>Direktur,</strong><br><br><br><br><strong><u>{nama_direktur}</u></strong><br>NIP. {nip_direktur}</td></tr></tbody></table><br><hr><br><p style="text-align: right; font-size: 10pt;">Lampiran Surat<br>Nomor: {nomor_surat}<br>Tanggal: {tanggal}</p><p style="text-align: center;"><strong>DAFTAR UNDANGAN</strong></p><table class="letter-data-table" style="width: 100%; border-collapse: collapse; border: 1px solid #333;" border="1"><thead><tr style="background-color: #f2f2f2;"><th style="width: 8%; padding: 8px; text-align: center;">No.</th><th style="width: 52%; padding: 8px;">Nama</th><th style="width: 40%; padding: 8px;">Unit / Jabatan</th></tr></thead><tbody><tr><td style="text-align: center; padding: 8px;">1.</td><td style="padding: 8px;">[Nama Undangan 1]</td><td style="padding: 8px;">[Unit 1]</td></tr><tr><td style="text-align: center; padding: 8px;">2.</td><td style="padding: 8px;">[Nama Undangan 2]</td><td style="padding: 8px;">[Unit 2]</td></tr><tr><td style="text-align: center; padding: 8px;">3.</td><td style="padding: 8px;">[Nama Undangan 3]</td><td style="padding: 8px;">[Unit 3]</td></tr></tbody></table>',
                ]
            );
        }

        // 3. SURAT KETERANGAN (Kode N)
        if ($catN) {
            DocumentTemplate::updateOrCreate(
                ['code' => 'RSUSM-SKET'],
                [
                    'name' => 'Surat Keterangan Karyawan RSU Syifa Medika Banjarbaru',
                    'description' => 'Template resmi Surat Keterangan Karyawan RSU Syifa Medika Banjarbaru',
                    'company_id' => $companyId,
                    'category_id' => $catN->id,
                    'is_active' => true,
                    'allow_file_upload' => true,
                    'allow_manual_input' => true,
                    'template_fields' => [
                        ['name' => 'nama_karyawan', 'label' => 'Nama Karyawan', 'type' => 'text', 'required' => true],
                        ['name' => 'nip_karyawan', 'label' => 'NIP Karyawan', 'type' => 'text', 'required' => true],
                        ['name' => 'jabatan_karyawan', 'label' => 'Jabatan', 'type' => 'text', 'required' => true],
                        ['name' => 'tanggal_masuk', 'label' => 'Tanggal Masuk', 'type' => 'text', 'required' => true],
                        ['name' => 'keperluan', 'label' => 'Keperluan Surat', 'type' => 'text', 'required' => true],
                    ],
                    'template_content' => '<p style="text-align: center;"><strong><span style="font-size: 14pt;">SURAT KETERANGAN</span></strong><br>Nomor: {nomor_surat}</p><br><p>Yang bertanda tangan di bawah ini:</p><table class="letter-layout-table" style="width: 100%; border: none; margin-left: 20px;"><tbody><tr><td style="width: 25%; border: none;"><strong>Nama</strong></td><td style="border: none;">: {nama_direktur}</td></tr><tr><td style="width: 25%; border: none;"><strong>NIP</strong></td><td style="border: none;">: {nip_direktur}</td></tr><tr><td style="width: 25%; border: none;"><strong>Jabatan</strong></td><td style="border: none;">: Direktur</td></tr></tbody></table><br><p>Dengan ini menerangkan bahwa:</p><table class="letter-layout-table" style="width: 100%; border: none; margin-left: 20px;"><tbody><tr><td style="width: 25%; border: none;"><strong>Nama</strong></td><td style="border: none;">: [Nama Karyawan]</td></tr><tr><td style="width: 25%; border: none;"><strong>NIP</strong></td><td style="border: none;">: [NIP Karyawan]</td></tr><tr><td style="width: 25%; border: none;"><strong>Jabatan</strong></td><td style="border: none;">: [Jabatan Karyawan]</td></tr></tbody></table><br><p style="text-align: justify;">Adalah benar Karyawan di Rumah Sakit Umum Syifa Medika Banjarbaru, sejak Tanggal [Tanggal Mulai Bekerja] sampai dengan surat ini dibuat dengan kinerja <strong>BAIK</strong>.</p><p style="text-align: justify;">Demikian Surat Keterangan ini dibuat sebagai salah satu persyaratan dalam [Keperluan Persyaratan] dan agar dapat dipergunakan sebagaimana mestinya.</p><br><table class="letter-signature-table" style="width: 100%; border: none;"><tbody><tr><td style="width: 58%; border: none;">&nbsp;</td><td class="letter-signature-block" style="width: 42%; border: none; text-align: left;">Banjarbaru, {tanggal}<br><strong>Direktur,</strong><br><br><br><br><strong><u>{nama_direktur}</u></strong><br>NIP. {nip_direktur}</td></tr></tbody></table>',
                ]
            );
        }

        // 4. SURAT PERMOHONAN / PENGANTAR (Kode O)
        if ($catO) {
            DocumentTemplate::updateOrCreate(
                ['code' => 'RSUSM-SPM'],
                [
                    'name' => 'Surat Permohonan / Pengantar RSU Syifa Medika Banjarbaru',
                    'description' => 'Template resmi Surat Permohonan / Pengantar RSU Syifa Medika Banjarbaru',
                    'company_id' => $companyId,
                    'category_id' => $catO->id,
                    'is_active' => true,
                    'allow_file_upload' => true,
                    'allow_manual_input' => true,
                    'template_fields' => [
                        ['name' => 'perihal', 'label' => 'Perihal', 'type' => 'text', 'required' => true],
                        ['name' => 'tujuan', 'label' => 'Pihak Tujuan', 'type' => 'text', 'required' => true],
                    ],
                    'template_content' => '<table class="letter-meta-table" style="width: 100%; border: none;"><tbody><tr><td style="width: 60%; border: none; vertical-align: top;">Nomor : {nomor_surat}<br>Perihal : <strong>Permohonan [Perihal Permohonan]</strong></td><td style="width: 40%; border: none; vertical-align: top; text-align: right;">Banjarbaru, {tanggal}</td></tr></tbody></table><br><p>Yth.<br><strong>[Nama / Pimpinan Instansi Tujuan]</strong><br>di Tempat</p><p>Dengan hormat,</p><p style="text-align: justify;">[Tuliskan maksud dan penjelasan rinci permohonan di sini...]</p><p style="text-align: justify;">Demikian surat permohonan ini kami sampaikan, atas perhatian dan kerja sama Bapak/Ibu, kami ucapkan terima kasih.</p><br><table class="letter-signature-table" style="width: 100%; border: none;"><tbody><tr><td style="width: 58%; border: none;">&nbsp;</td><td class="letter-signature-block" style="width: 42%; border: none; text-align: left;">Hormat kami,<br><strong>Direktur,</strong><br><br><br><br><strong><u>{nama_direktur}</u></strong><br>NIP. {nip_direktur}</td></tr></tbody></table>',
                ]
            );
        }

        // 5. SURAT PERINTAH TUGAS & SPPD (Kode G)
        if ($catG) {
            DocumentTemplate::updateOrCreate(
                ['code' => 'RSUSM-SPT'],
                [
                    'name' => 'Surat Perintah Tugas (SPT) & SPPD RSU Syifa Medika Banjarbaru',
                    'description' => 'Template resmi 1 paket Surat Perintah Tugas beserta Surat Perintah Perjalanan Dinas (SPPD) RSU Syifa Medika Banjarbaru',
                    'company_id' => $companyId,
                    'category_id' => $catG->id,
                    'is_active' => true,
                    'allow_file_upload' => true,
                    'allow_manual_input' => true,
                    'template_fields' => [
                        ['name' => 'tugas', 'label' => 'Tugas Yang Diberikan', 'type' => 'text', 'required' => true],
                        ['name' => 'hari_tanggal', 'label' => 'Hari, Tanggal', 'type' => 'text', 'required' => true],
                        ['name' => 'waktu', 'label' => 'Waktu', 'type' => 'text', 'required' => true],
                        ['name' => 'tempat', 'label' => 'Tempat / Kota Tujuan', 'type' => 'text', 'required' => true],
                        ['name' => 'nama_karyawan', 'label' => 'Nama Karyawan', 'type' => 'text', 'required' => false],
                        ['name' => 'jabatan', 'label' => 'Jabatan', 'type' => 'text', 'required' => false],
                        ['name' => 'transportasi', 'label' => 'Transportasi', 'type' => 'text', 'required' => false],
                    ],
                    'template_content' => '<p style="text-align: center;"><strong><span style="font-size: 14pt;">SURAT PERINTAH TUGAS</span></strong><br>Nomor: {nomor_surat}</p><br><p>Yang bertanda tangan di bawah ini:</p><table class="letter-layout-table" style="width: 100%; border: none; margin-left: 20px;"><tbody><tr><td style="width: 25%; border: none;"><strong>Nama</strong></td><td style="border: none;">: {nama_direktur}</td></tr><tr><td style="width: 25%; border: none;"><strong>NIP</strong></td><td style="border: none;">: {nip_direktur}</td></tr><tr><td style="width: 25%; border: none;"><strong>Jabatan</strong></td><td style="border: none;">: Direktur</td></tr></tbody></table><br><p>Dengan ini memberikan tugas kepada:</p><table class="letter-data-table" style="width: 100%; border-collapse: collapse; border: 1px solid #333;" border="1"><thead><tr style="background-color: #f2f2f2;"><th style="width: 40%; padding: 8px;">Nama</th><th style="width: 35%; padding: 8px;">Jabatan</th><th style="width: 25%; padding: 8px;">NIP</th></tr></thead><tbody><tr><td style="padding: 8px;">[Nama Petugas 1]</td><td style="padding: 8px;">[Jabatan 1]</td><td style="padding: 8px;">[NIP 1]</td></tr><tr><td style="padding: 8px;">[Nama Petugas 2]</td><td style="padding: 8px;">[Jabatan 2]</td><td style="padding: 8px;">[NIP 2]</td></tr></tbody></table><br><p>Dalam rangka <strong>[Kegiatan / Keperluan Penugasan]</strong> yang akan dilaksanakan pada:</p><table class="letter-layout-table" style="width: 100%; border: none; margin-left: 20px;"><tbody><tr><td style="width: 25%; border: none;"><strong>Hari, Tanggal</strong></td><td style="border: none;">: [Hari, Tanggal Pelaksanaan]</td></tr><tr><td style="width: 25%; border: none;"><strong>Waktu</strong></td><td style="border: none;">: [00.00] WITA s.d. Selesai</td></tr><tr><td style="width: 25%; border: none;"><strong>Tempat</strong></td><td style="border: none;">: [Lokasi Penugasan]</td></tr></tbody></table><br><p>Demikian surat tugas ini diberikan kepada yang bersangkutan untuk dilaksanakan sebagaimana mestinya.</p><br><table class="letter-signature-table" style="width: 100%; border: none;"><tbody><tr><td style="width: 58%; border: none;">&nbsp;</td><td class="letter-signature-block" style="width: 42%; border: none; text-align: left;">Banjarbaru, {tanggal}<br><strong>Direktur,</strong><br><br><br><br><strong><u>{nama_direktur}</u></strong><br>NIP. {nip_direktur}</td></tr></tbody></table><div style="page-break-before: always; break-before: page; margin-top: 30px;"></div><p style="text-align: center;"><strong><span style="font-size: 14pt;">SURAT PERINTAH PERJALANAN DINAS (SPPD)</span></strong><br>Nomor: {nomor_surat}</p><br><table class="letter-data-table" style="width: 100%; border-collapse: collapse; border: 1px solid #333;" border="1"><tbody><tr><td style="width: 5%; padding: 8px; text-align: center;">1.</td><td style="width: 45%; padding: 8px;">Pejabat berwenang pemberi perintah</td><td style="width: 50%; padding: 8px;">Direktur RSU Syifa Medika Banjarbaru</td></tr><tr><td style="width: 5%; padding: 8px; text-align: center;">2.</td><td style="width: 45%; padding: 8px;">Nama karyawan yang diberi perintah</td><td style="width: 50%; padding: 8px;">[Nama Karyawan]</td></tr><tr><td style="width: 5%; padding: 8px; text-align: center;">3.</td><td style="width: 45%; padding: 8px;">Jabatan</td><td style="width: 50%; padding: 8px;">[Jabatan Karyawan]</td></tr><tr><td style="width: 5%; padding: 8px; text-align: center;">4.</td><td style="width: 45%; padding: 8px;">Tempat yang dituju</td><td style="width: 50%; padding: 8px;">[Kota / Tempat Tujuan]</td></tr><tr><td style="width: 5%; padding: 8px; text-align: center;">5.</td><td style="width: 45%; padding: 8px;">Tujuan penugasan</td><td style="width: 50%; padding: 8px;">[Tujuan Perjalanan Dinas]</td></tr><tr><td style="width: 5%; padding: 8px; text-align: center;">6.</td><td style="width: 45%; padding: 8px;">Hari / tanggal tugas</td><td style="width: 50%; padding: 8px;">[Hari, Tanggal Perjalanan]</td></tr><tr><td style="width: 5%; padding: 8px; text-align: center;">7.</td><td style="width: 45%; padding: 8px;">Transportasi yang digunakan</td><td style="width: 50%; padding: 8px;">[Kendaraan Dinas / Umum / Pesawat]</td></tr><tr><td style="width: 5%; padding: 8px; text-align: center;">8.</td><td style="width: 45%; padding: 8px;">Nama, tanda tangan petugas dan stempel tempat yang dikunjungi</td><td style="width: 50%; padding: 8px; height: 80px;"><br><br><br></td></tr></tbody></table><br><table class="letter-signature-table" style="width: 100%; border: none;"><tbody><tr><td style="width: 58%; border: none;">&nbsp;</td><td class="letter-signature-block" style="width: 42%; border: none; text-align: left;">Banjarbaru, {tanggal}<br><strong>Direktur,</strong><br><br><br><br><strong><u>{nama_direktur}</u></strong><br>NIP. {nip_direktur}</td></tr></tbody></table>',
                ]
            );

            // 6. SURAT PERINTAH PERJALANAN DINAS (SPPD) (Kode G)
            DocumentTemplate::updateOrCreate(
                ['code' => 'RSUSM-SPPD'],
                [
                    'name' => 'Surat Perintah Perjalanan Dinas (SPPD) RSU Syifa Medika Banjarbaru',
                    'description' => 'Template resmi SPPD RSU Syifa Medika Banjarbaru',
                    'company_id' => $companyId,
                    'category_id' => $catG->id,
                    'is_active' => true,
                    'allow_file_upload' => true,
                    'allow_manual_input' => true,
                    'template_fields' => [
                        ['name' => 'nama_karyawan', 'label' => 'Nama Karyawan', 'type' => 'text', 'required' => true],
                        ['name' => 'jabatan', 'label' => 'Jabatan', 'type' => 'text', 'required' => true],
                        ['name' => 'tempat_tujuan', 'label' => 'Tempat Tujuan', 'type' => 'text', 'required' => true],
                        ['name' => 'tujuan_penugasan', 'label' => 'Tujuan Penugasan', 'type' => 'text', 'required' => true],
                        ['name' => 'hari_tanggal', 'label' => 'Hari, Tanggal', 'type' => 'text', 'required' => true],
                        ['name' => 'transportasi', 'label' => 'Transportasi', 'type' => 'text', 'required' => true],
                    ],
                    'template_content' => '<p style="text-align: center;"><strong><span style="font-size: 14pt;">SURAT PERINTAH PERJALANAN DINAS (SPPD)</span></strong><br>Nomor: {nomor_surat}</p><br><table style="width: 100%; border-collapse: collapse; border: 1px solid #333;" border="1"><tbody><tr><td style="width: 5%; padding: 8px; text-align: center;">1.</td><td style="width: 45%; padding: 8px;">Pejabat berwenang pemberi perintah</td><td style="width: 50%; padding: 8px;">Direktur RSU Syifa Medika Banjarbaru</td></tr><tr><td style="width: 5%; padding: 8px; text-align: center;">2.</td><td style="width: 45%; padding: 8px;">Nama karyawan yang diberi perintah</td><td style="width: 50%; padding: 8px;">[Nama Karyawan]</td></tr><tr><td style="width: 5%; padding: 8px; text-align: center;">3.</td><td style="width: 45%; padding: 8px;">Jabatan</td><td style="width: 50%; padding: 8px;">[Jabatan Karyawan]</td></tr><tr><td style="width: 5%; padding: 8px; text-align: center;">4.</td><td style="width: 45%; padding: 8px;">Tempat yang dituju</td><td style="width: 50%; padding: 8px;">[Kota / Tempat Tujuan]</td></tr><tr><td style="width: 5%; padding: 8px; text-align: center;">5.</td><td style="width: 45%; padding: 8px;">Tujuan penugasan</td><td style="width: 50%; padding: 8px;">[Tujuan Perjalanan Dinas]</td></tr><tr><td style="width: 5%; padding: 8px; text-align: center;">6.</td><td style="width: 45%; padding: 8px;">Hari / tanggal tugas</td><td style="width: 50%; padding: 8px;">[Hari, Tanggal Perjalanan]</td></tr><tr><td style="width: 5%; padding: 8px; text-align: center;">7.</td><td style="width: 45%; padding: 8px;">Transportasi yang digunakan</td><td style="width: 50%; padding: 8px;">[Kendaraan Dinas / Umum / Pesawat]</td></tr><tr><td style="width: 5%; padding: 8px; text-align: center;">8.</td><td style="width: 45%; padding: 8px;">Nama, tanda tangan petugas dan stempel tempat yang dikunjungi</td><td style="width: 50%; padding: 8px; height: 80px;"><br><br><br></td></tr></tbody></table><br><table class="letter-signature-table" style="width: 100%; border: none;"><tbody><tr><td style="width: 58%; border: none;">&nbsp;</td><td class="letter-signature-block" style="width: 42%; border: none; text-align: left;">Banjarbaru, {tanggal}<br><strong>Direktur,</strong><br><br><br><br><strong><u>{nama_direktur}</u></strong><br>NIP. {nip_direktur}</td></tr></tbody></table>',
                ]
            );
        }
    }

    public function down(): void
    {
        $companyId = 5;
        DocumentTemplate::where('company_id', $companyId)->delete();
    }
};

