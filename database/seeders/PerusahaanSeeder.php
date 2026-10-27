<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * PerusahaanSeeder — mengisi kode_surat dan legacy_id untuk perusahaan yang sudah ada.
 *
 * KEBIJAKAN SEEDER INI:
 * - TIDAK membuat baris baru di tabel companies.
 * - HANYA mengupdate baris yang ditemukan berdasarkan pencocokan nama (case-insensitive).
 * - Jika ada perusahaan yang tidak ditemukan di DB, seeder BERHENTI dan melaporkan
 *   perusahaan mana yang tidak cocok agar admin bisa memutuskan.
 * - Selalu mencetak daftar perusahaan di DB dan hasil pencocokan sebelum menulis.
 *
 * Data yang di-assign (legacy_id = id dari sistem HRIS lama):
 *   legacy_id 1 → kode SGG       → PT Syifa Global Group
 *   legacy_id 2 → kode RSUSM     → RSU Syifa Medika Banjarbaru
 *   legacy_id 3 → kode RSUSM-BRB → RSU Syifa Medika Barabai
 *   legacy_id 4 → kode KOP-SMP   → Koperasi Syifa Medika Persada
 *   legacy_id 7 → kode SMP       → PT Syifa Medika Persada
 *
 * Jalankan:
 *   php artisan db:seed --class=PerusahaanSeeder
 *
 * Mode dry-run (hanya cetak pencocokan, tidak menulis ke DB):
 *   Jawab "no" saat prompt konfirmasi interaktif.
 *   Atau set properti $dryRun = true di bawah sebelum dijalankan (untuk CI/non-interaktif).
 */
class PerusahaanSeeder extends Seeder
{
    /**
     * Set true untuk memaksa mode dry-run tanpa prompt interaktif.
     */
    public bool $dryRun = false;

    /**
     * Daftar perusahaan yang akan di-update.
     *
     * nama_db    : nama yang DIHARAPKAN ada di database (dicocokkan case-insensitive).
     *              ⚠️  Sesuaikan dengan nama aktual di DB jika berbeda dari spesifikasi.
     * kode_surat : kode yang akan diisi ke companies.kode_surat.
     * legacy_id  : id dari sistem HRIS lama, diisi ke companies.legacy_id.
     */
    private array $perusahaan = [
        [
            'nama_db'    => 'PT Syifa Global Group',
            'kode_surat' => 'SGG',
            'legacy_id'  => 1,
        ],
        [
            'nama_db'    => 'RSU Syifa Medika Banjarbaru',
            'kode_surat' => 'RSUSM',
            'legacy_id'  => 2,
        ],
        [
            'nama_db'    => 'RSU Syifa Medika Barabai',
            'kode_surat' => 'RSUSM-BRB',
            'legacy_id'  => 3,
        ],
        [
            'nama_db'    => 'Koperasi Syifa Medika Persada',
            'kode_surat' => 'KOP-SMP',
            'legacy_id'  => 4,
        ],
        [
            'nama_db'    => 'PT Syifa Medika Persada',
            'kode_surat' => 'SMP',
            'legacy_id'  => 7,
        ],
    ];

    public function run(): void
    {
        // ── 1. Cetak semua perusahaan di DB ─────────────────────────────────
        $this->command->newLine();
        $this->command->info('=== Perusahaan yang ada di tabel companies ===');

        $semuaPerusahaan = DB::table('companies')
            ->select('id', 'name', 'code', 'kode_surat', 'legacy_id')
            ->orderBy('id')
            ->get();

        $this->command->table(
            ['id', 'name', 'code', 'kode_surat (sekarang)', 'legacy_id (sekarang)'],
            $semuaPerusahaan->map(fn ($p) => [
                $p->id,
                $p->name,
                $p->code,
                $p->kode_surat ?? 'NULL',
                $p->legacy_id  ?? 'NULL',
            ])->toArray()
        );

        // ── 2. Cocokkan setiap entri ─────────────────────────────────────────
        $this->command->newLine();
        $this->command->info('=== Hasil pencocokan ===');

        $cocok      = [];
        $tidakCocok = [];

        foreach ($this->perusahaan as $data) {
            $row = DB::table('companies')
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($data['nama_db'])])
                ->first();

            if ($row) {
                $cocok[] = [
                    'id_db'      => $row->id,
                    'nama_db'    => $row->name,
                    'kode_surat' => $data['kode_surat'],
                    'legacy_id'  => $data['legacy_id'],
                ];
            } else {
                $tidakCocok[] = $data;
            }
        }

        // Cetak yang cocok
        if (! empty($cocok)) {
            $this->command->info('✅ Cocok — akan di-update:');
            $this->command->table(
                ['id DB', 'Nama di DB', 'kode_surat baru', 'legacy_id baru'],
                array_map(fn ($c) => [
                    $c['id_db'],
                    $c['nama_db'],
                    $c['kode_surat'],
                    $c['legacy_id'],
                ], $cocok)
            );
        }

        // Cetak yang tidak cocok — dan langsung berhenti
        if (! empty($tidakCocok)) {
            $this->command->newLine();
            $this->command->error('❌ Tidak ditemukan di database (seeder BERHENTI):');

            foreach ($tidakCocok as $d) {
                $this->command->warn(
                    "   • nama_db='{$d['nama_db']}' | kode_surat={$d['kode_surat']} | legacy_id={$d['legacy_id']}"
                );
            }

            $this->command->newLine();
            $this->command->comment('Tindakan yang diperlukan:');
            $this->command->comment('  a) Sesuaikan kolom nama_db di seeder ini dengan nama aktual di DB, ATAU');
            $this->command->comment('  b) Buat baris perusahaan yang belum ada secara manual, lalu jalankan ulang.');

            throw new \RuntimeException(
                'PerusahaanSeeder: ' . count($tidakCocok) . ' perusahaan tidak ditemukan. Lihat output di atas.'
            );
        }

        // ── 3. Konfirmasi atau dry-run ───────────────────────────────────────
        $this->command->newLine();

        $isDryRun = $this->dryRun;

        if (! $isDryRun && $this->command->getOutput()->isInteractive()) {
            $isDryRun = ! $this->command->confirm(
                count($cocok) . ' perusahaan siap di-update. Lanjutkan? (no = dry-run)',
                true
            );
        }

        if ($isDryRun) {
            $this->command->newLine();
            $this->command->warn('[DRY-RUN] Tidak ada yang ditulis ke database.');
            $this->command->info('Konfirmasi "yes" untuk benar-benar mengupdate.');

            return;
        }

        // ── 4. Eksekusi UPDATE ───────────────────────────────────────────────
        $this->command->newLine();

        foreach ($cocok as $item) {
            DB::table('companies')
                ->where('id', $item['id_db'])
                ->update([
                    'kode_surat' => $item['kode_surat'],
                    'legacy_id'  => $item['legacy_id'],
                ]);

            $this->command->line(
                "   ✅ [{$item['id_db']}] {$item['nama_db']}"
                . " → kode_surat={$item['kode_surat']}, legacy_id={$item['legacy_id']}"
            );
        }

        $this->command->newLine();
        $this->command->info('🎉 PerusahaanSeeder selesai. ' . count($cocok) . ' perusahaan berhasil di-update.');
    }
}
