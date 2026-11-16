<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\Unit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $company = Company::find(5) ?? Company::where('code', 'RSUSMBJB')->first();
        if (! $company) {
            return;
        }

        $companyId = $company->id;
        $company->kode_surat = 'RSUSM';
        $company->save();

        // ── 1. KATEGORI DOKUMEN KHUSUS RSU SYIFA MEDIKA BANJARBARU (A-Z) ───────
        $categories = [
            ['code' => 'A', 'name' => 'Kebijakan Peraturan', 'kelompok' => 'dokumen'],
            ['code' => 'B', 'name' => 'Pedoman & Panduan Pelaksanaan', 'kelompok' => 'dokumen'],
            ['code' => 'C', 'name' => 'SPO', 'kelompok' => 'dokumen'],
            ['code' => 'D', 'name' => 'Surat Edaran', 'kelompok' => 'surat_keluar'],
            ['code' => 'E', 'name' => 'Surat Keputusan', 'kelompok' => 'dokumen'],
            ['code' => 'F', 'name' => 'Surat Perintah', 'kelompok' => 'surat_keluar'],
            ['code' => 'G', 'name' => 'Surat Tugas', 'kelompok' => 'surat_keluar'],
            ['code' => 'H', 'name' => 'Memorandum', 'kelompok' => 'surat_keluar'],
            ['code' => 'I', 'name' => 'Surat Dinas', 'kelompok' => 'surat_keluar'],
            ['code' => 'J', 'name' => 'Surat Undangan', 'kelompok' => 'surat_keluar'],
            ['code' => 'K', 'name' => 'Surat Perjanjian', 'kelompok' => 'dokumen'],
            ['code' => 'L', 'name' => 'Surat Kuasa', 'kelompok' => 'surat_keluar'],
            ['code' => 'M', 'name' => 'Surat Berita Acara', 'kelompok' => 'dokumen'],
            ['code' => 'N', 'name' => 'Surat Keterangan', 'kelompok' => 'surat_keluar'],
            ['code' => 'O', 'name' => 'Surat Pengantar', 'kelompok' => 'surat_keluar'],
            ['code' => 'P', 'name' => 'Surat Pengumuman', 'kelompok' => 'surat_keluar'],
            ['code' => 'Q', 'name' => 'Surat Laporan', 'kelompok' => 'dokumen'],
            ['code' => 'R', 'name' => 'Formulir-formulir', 'kelompok' => 'dokumen'],
            ['code' => 'S', 'name' => 'Surat Permohonan Barang/Jasa', 'kelompok' => 'surat_keluar'],
            ['code' => 'T', 'name' => 'Poster/Brosur', 'kelompok' => 'dokumen'],
            ['code' => 'U', 'name' => 'Surat Peringatan', 'kelompok' => 'surat_keluar'],
            ['code' => 'V', 'name' => 'Surat Balasan', 'kelompok' => 'surat_keluar'],
            ['code' => 'W', 'name' => 'Surat Permohonan Pembayaran Tagihan', 'kelompok' => 'surat_keluar'],
            ['code' => 'X', 'name' => 'Somasi', 'kelompok' => 'surat_keluar'],
            ['code' => 'Y', 'name' => 'Surat Pernyataan', 'kelompok' => 'surat_keluar'],
            ['code' => 'Z', 'name' => 'Sertifikat', 'kelompok' => 'dokumen'],
        ];

        foreach ($categories as $cat) {
            DocumentCategory::updateOrCreate(
                ['company_id' => $companyId, 'code' => $cat['code']],
                [
                    'name' => $cat['name'],
                    'prefix' => $cat['code'],
                    'kelompok' => $cat['kelompok'],
                    'is_active' => true,
                ]
            );
        }

        // ── 2. DEPARTEMEN RSU SYIFA MEDIKA BANJARBARU ──────────────────────────
        $departments = [
            'DIR' => Department::firstOrCreate(
                ['company_id' => $companyId, 'code' => 'DIR-BJB'],
                ['name' => 'Direktur Banjarbaru']
            )->id,
            'ADUM' => Department::firstOrCreate(
                ['company_id' => $companyId, 'code' => 'ADUMBJB'],
                ['name' => 'Administrasi dan Umum Banjarbaru']
            )->id,
            'YANMED' => Department::firstOrCreate(
                ['company_id' => $companyId, 'code' => 'YANMEDBJB'],
                ['name' => 'Pelayanan Medik Banjarbaru']
            )->id,
            'JANGMED' => Department::firstOrCreate(
                ['company_id' => $companyId, 'code' => 'JANGMEDBJB'],
                ['name' => 'Penunjang Medik Banjarbaru']
            )->id,
            'KEU' => Department::firstOrCreate(
                ['company_id' => $companyId, 'code' => 'KEU-BJB'],
                ['name' => 'Keuangan Banjarbaru']
            )->id,
            'KOMITE' => Department::firstOrCreate(
                ['company_id' => $companyId, 'code' => 'COMMITTEE-BJB'],
                ['name' => 'Komite Banjarbaru']
            )->id,
        ];

        // ── 3. 57 UNIT KERJA / JABATAN RSU SYIFA MEDIKA BANJARBARU ─────────────
        $units = [
            ['kode' => '01', 'name' => 'Direktur', 'dept' => 'DIR'],
            ['kode' => '02', 'name' => 'Satuan Pengawas Internal', 'dept' => 'DIR'],
            ['kode' => '03', 'name' => 'Bidang Pelayanan Medik & Keperawatan', 'dept' => 'YANMED'],
            ['kode' => '04', 'name' => 'Bidang Penunjang Medik', 'dept' => 'JANGMED'],
            ['kode' => '05', 'name' => 'Bidang Administrasi & Umum', 'dept' => 'ADUM'],
            ['kode' => '06', 'name' => 'Bagian Keuangan', 'dept' => 'KEU'],
            ['kode' => '07', 'name' => 'Kasi Keperawatan', 'dept' => 'YANMED'],
            ['kode' => '08', 'name' => 'Sub. Bag Kepegawaian', 'dept' => 'ADUM'],
            ['kode' => '09', 'name' => 'Sub. Bag Hukum, Humas & Promkes', 'dept' => 'ADUM'],
            ['kode' => '10', 'name' => 'Sub. Bag. Pembangunan & Pemeliharaan Bangunan', 'dept' => 'ADUM'],
            ['kode' => '11', 'name' => 'Instalasi Teknologi Informasi', 'dept' => 'ADUM'],
            ['kode' => '12', 'name' => 'Unit Logistik', 'dept' => 'ADUM'],
            ['kode' => '13', 'name' => 'Instalasi Rawat Jalan', 'dept' => 'YANMED'],
            ['kode' => '14', 'name' => 'Instalasi Gawat Darurat', 'dept' => 'YANMED'],
            ['kode' => '15', 'name' => 'Instalasi Rawat Inap', 'dept' => 'YANMED'],
            ['kode' => '16', 'name' => 'Instalasi Laboratorium', 'dept' => 'JANGMED'],
            ['kode' => '17', 'name' => 'Instalasi Radiologi', 'dept' => 'JANGMED'],
            ['kode' => '18', 'name' => 'Instalasi Farmasi', 'dept' => 'JANGMED'],
            ['kode' => '19', 'name' => 'Instalasi Kamar Operasi', 'dept' => 'YANMED'],
            ['kode' => '20', 'name' => 'Instalasi Gizi', 'dept' => 'JANGMED'],
            ['kode' => '21', 'name' => 'Instalasi Rehabilitasi Medik', 'dept' => 'JANGMED'],
            ['kode' => '22', 'name' => 'Instalasi Pemeliharaan Sarana Prasarana RS', 'dept' => 'ADUM'],
            ['kode' => '23', 'name' => 'Intensive Care Unit & Stroke Center', 'dept' => 'YANMED'],
            ['kode' => '24', 'name' => 'Unit Hemodialisis', 'dept' => 'YANMED'],
            ['kode' => '25', 'name' => 'Instalasi Rekam Medik', 'dept' => 'JANGMED'],
            ['kode' => '26', 'name' => 'Instalasi Sanitasi Lingkungan', 'dept' => 'JANGMED'],
            ['kode' => '27', 'name' => 'Instalasi Keamanan & Kedaruratan Bencana', 'dept' => 'ADUM'],
            ['kode' => '28', 'name' => 'Instalasi Kamar Jenazah, Ambulans & Transportasi', 'dept' => 'ADUM'],
            ['kode' => '29', 'name' => 'Tim Pengelola Tagihan Klaim INAcBG/Casemix', 'dept' => 'KEU'],
            ['kode' => '30', 'name' => 'Tim PPI', 'dept' => 'KOMITE'],
            ['kode' => '31', 'name' => 'Tim PMKP', 'dept' => 'KOMITE'],
            ['kode' => '32', 'name' => 'Tim PKRS', 'dept' => 'KOMITE'],
            ['kode' => '33', 'name' => 'Tim K3 RS', 'dept' => 'KOMITE'],
            ['kode' => '34', 'name' => 'Tim MFK', 'dept' => 'KOMITE'],
            ['kode' => '35', 'name' => 'Tim MCU & Pelayanan Luar Rumah Sakit', 'dept' => 'YANMED'],
            ['kode' => '36', 'name' => 'Program P2PM', 'dept' => 'YANMED'],
            ['kode' => '37', 'name' => 'Komite Medik', 'dept' => 'KOMITE'],
            ['kode' => '38', 'name' => 'Komite Keperawatan', 'dept' => 'KOMITE'],
            ['kode' => '39', 'name' => 'Komite Tenaga Kesehatan Lainnya', 'dept' => 'KOMITE'],
            ['kode' => '40', 'name' => 'Komite Farmasi & Terapi', 'dept' => 'KOMITE'],
            ['kode' => '41', 'name' => 'Komite Etik & Hukum', 'dept' => 'KOMITE'],
            ['kode' => '42', 'name' => 'Panitia RM', 'dept' => 'KOMITE'],
            ['kode' => '43', 'name' => 'External RS', 'dept' => 'ADUM'],
            ['kode' => '44', 'name' => 'Tim Akreditasi', 'dept' => 'KOMITE'],
            ['kode' => '45', 'name' => 'Tim HPK', 'dept' => 'KOMITE'],
            ['kode' => '46', 'name' => 'Tim KPS', 'dept' => 'KOMITE'],
            ['kode' => '47', 'name' => 'Tim Prognas (Progam Nasional)', 'dept' => 'KOMITE'],
            ['kode' => '48', 'name' => 'Tim PPK', 'dept' => 'KOMITE'],
            ['kode' => '49', 'name' => 'Tim AKP', 'dept' => 'KOMITE'],
            ['kode' => '50', 'name' => 'Tim MRMIK', 'dept' => 'KOMITE'],
            ['kode' => '51', 'name' => 'Tim TKRS', 'dept' => 'KOMITE'],
            ['kode' => '52', 'name' => 'Tim PP', 'dept' => 'KOMITE'],
            ['kode' => '53', 'name' => 'Tim PAP', 'dept' => 'KOMITE'],
            ['kode' => '54', 'name' => 'Tim PAB', 'dept' => 'KOMITE'],
            ['kode' => '55', 'name' => 'Tim PKPO', 'dept' => 'KOMITE'],
            ['kode' => '56', 'name' => 'Tim KE', 'dept' => 'KOMITE'],
            ['kode' => '57', 'name' => 'Tim SKP', 'dept' => 'KOMITE'],
        ];

        // Petakan unit 24 (IPSRS Banjarbaru) yang dipakai user 305 ke kode '22'
        $existingUnit24 = Unit::where('id', 24)->where('company_id', $companyId)->first();
        if ($existingUnit24) {
            $existingUnit24->update([
                'name' => 'Instalasi Pemeliharaan Sarana Prasarana RS',
                'kode' => '22',
                'prefix' => '22',
                'department_id' => $departments['ADUM'],
                'is_active' => true,
            ]);
        }

        // Hapus unit lama company 5 yang belum pernah dipakai dan kode-nya masih null
        Unit::where('company_id', $companyId)
            ->whereNull('kode')
            ->where('id', '<>', 24)
            ->delete();

        // Buat atau perbarui 57 unit kerja resmi
        foreach ($units as $u) {
            if ($u['kode'] === '22' && $existingUnit24) {
                continue;
            }

            Unit::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'kode' => $u['kode'],
                ],
                [
                    'name' => $u['name'],
                    'prefix' => $u['kode'],
                    'department_id' => $departments[$u['dept']],
                    'is_active' => true,
                ]
            );
        }
    }

    public function down(): void
    {
        $companyId = 5;
        DocumentCategory::where('company_id', $companyId)->delete();
        Unit::where('company_id', $companyId)->whereNotNull('kode')->delete();
    }
};

