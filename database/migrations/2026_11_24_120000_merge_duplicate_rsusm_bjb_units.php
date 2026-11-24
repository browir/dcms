<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lebur unit dobel RSU Syifa Medika Banjarbaru.
 *
 * Seed 2026_11_16_000001 membuat 57 unit resmi bernomor (01..57), padahal unit lama
 * (mis. "Logistik Banjarbaru" / LOGISTIK-BJB) masih ada dan sudah dipakai pegawai.
 * Akibatnya unit yang sama muncul dua kali di pilihan unit.
 *
 * Untuk tiap pasangan: unit LAMA dipertahankan (pegawai & riwayat tetap utuh) dan
 * diberi kode resmi, data apa pun yang sempat menunjuk unit bernomor dipindah ke
 * unit lama, lalu unit bernomor yang dobel dihapus.
 *
 * Pasangan dicocokkan lewat prefix unit lama + kode unit bernomor (bukan id),
 * sehingga aman dijalankan di server mana pun; pasangan yang tidak ditemukan dilewati.
 */
return new class extends Migration
{
    /** prefix unit lama => kode resmi */
    private const PAIRS = [
        'DIR-BJB'         => '01', // Direktur Banjarbaru             → Direktur
        'KABAGYANMED-BJB' => '03', // Kepala Bagian Pelayanan Medik   → Bidang Pelayanan Medik & Keperawatan
        'KABAGJANGMED-BJB' => '04', // Kepala Bagian Penunjang Banjarbaru → Bidang Penunjang Medik
        'ADUM-KABAG BJB'  => '05', // Kepala Bagian Adum              → Bidang Administrasi & Umum
        'KEPKEU-BJB'      => '06', // Kepala Keuangan                 → Bagian Keuangan
        'KEPEG-BJB'       => '08', // Kepegawaian Banjarbaru          → Sub. Bag Kepegawaian
        'HUMAS-BJB'       => '09', // Humas dan Promkes Banjarbaru    → Sub. Bag Hukum, Humas & Promkes
        'LOGISTIK-BJB'    => '12', // Logistik Banjarbaru             → Unit Logistik
        'IRJABJB'         => '13', // Instalasi Rawat Jalan Banjarbaru
        'IGD-BJB'         => '14', // IGD Banjarbaru                  → Instalasi Gawat Darurat
        'IRNA-BJB'        => '15', // Instalasi Rawat Inap Banjarbaru
        'LAB-BJB'         => '16', // Instalasi Laboratorium Banjarbaru
        'RAD-BJB'         => '17', // Instalasi Radiologi Banjarbaru
        'FARMA-BJB'       => '18', // Instalasi Farmasi Banjarbaru
        'IBS-BJB'         => '19', // Instalasi Bedah Sentral         → Instalasi Kamar Operasi
        'GZ-BJB'          => '20', // Instalasi Gizi Banjarbaru
        'IPSRS-BJB'       => '22', // IPSRS Banjarbaru                → Inst. Pemeliharaan Sarana Prasarana RS
        'ICU-BJB'         => '23', // ICU Banjarbaru                  → Intensive Care Unit & Stroke Center
        'HD-BJB'          => '24', // Instalasi Hemodialisa Banjarbaru → Unit Hemodialisis
        'RM-BJB'          => '25', // Instalasi Rekam Medis Banjarbaru
        'SANITASI-BJB'    => '26', // Instalasi Sanitasi              → Instalasi Sanitasi Lingkungan
        'SATPAM-BJB'      => '27', // SATPAM Banjarbaru               → Instalasi Keamanan & Kedaruratan Bencana
        'AMB'             => '28', // Ambulance, Transportasi & Kamar Jenazah Banjarbaru
        'CASEMIX-BJB'     => '29', // Casemix                         → Tim Pengelola Tagihan Klaim INAcBG/Casemix
        'PPI-BJB'         => '30', // PPI Banjarbaru                  → Tim PPI
        'PMKP-BJB'        => '31', // PMKP Banjarbaru                 → Tim PMKP
        'PKRS-BJB'        => '32', // PKRS Banjarbaru                 → Tim PKRS
        'K3-BJB'          => '33', // K3RS Banjarbaru                 → Tim K3 RS
        'KOMITE-MEDIKBJB' => '37', // Komite Medik Banjarbaru
        'KOMKEP-BJB'      => '38', // Komite Keperawatan Banjarbaru
        'NAKESLAIN-BJB'   => '39', // Komite Nakes Lainnya Banjarbaru → Komite Tenaga Kesehatan Lainnya
    ];

    /** Tabel yang punya kolom unit_id biasa (bukan pivot unik) */
    private const REF_TABLES = ['users', 'documents', 'letter_numbers', 'surat', 'agendas', 'incoming_letters', 'meetings'];

    public function up(): void
    {
        $company = Company::find(5) ?? Company::where('code', 'RSUSMBJB')->first();
        if (! $company) {
            return;
        }
        $companyId = $company->id;

        DB::transaction(function () use ($companyId) {
            foreach (self::PAIRS as $prefix => $kode) {
                $legacy = DB::table('units')
                    ->leftJoin('departments', 'departments.id', '=', 'units.department_id')
                    ->whereRaw('COALESCE(units.company_id, departments.company_id) = ?', [$companyId])
                    ->where('units.prefix', $prefix)
                    ->where(fn ($q) => $q->whereNull('units.kode')->orWhere('units.kode', ''))
                    ->value('units.id');

                if (! $legacy) {
                    continue;
                }

                $numberedIds = DB::table('units')
                    ->where('company_id', $companyId)
                    ->where('kode', $kode)
                    ->where('id', '<>', $legacy)
                    ->pluck('id');

                foreach ($numberedIds as $numbered) {
                    $this->repoint($numbered, $legacy);
                    DB::table('units')->where('id', $numbered)->delete();
                }

                DB::table('units')->where('id', $legacy)->update([
                    'kode'       => $kode,
                    'prefix'     => $kode,
                    'company_id' => $companyId,
                    'is_active'  => true,
                    'updated_at' => now(),
                ]);
            }
        });
    }

    /** Pindahkan semua referensi unit $from ke unit $to */
    private function repoint(int $from, int $to): void
    {
        foreach (self::REF_TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'unit_id')) {
                DB::table($table)->where('unit_id', $from)->update(['unit_id' => $to]);
            }
        }

        if (Schema::hasTable('document_unit_access')) {
            // UNIQUE(document_id, unit_id): buang baris yang sudah dimiliki unit tujuan.
            $existing = DB::table('document_unit_access')->where('unit_id', $to)->pluck('document_id');
            DB::table('document_unit_access')->where('unit_id', $from)->whereIn('document_id', $existing)->delete();
            DB::table('document_unit_access')->where('unit_id', $from)->update(['unit_id' => $to]);
        }
    }

    public function down(): void
    {
        // Peleburan data tidak dapat dibalik secara aman (unit bernomor sudah dihapus).
    }
};
