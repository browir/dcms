<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Artisan command untuk mengisi kode_surat dan legacy_id pada tabel companies.
 *
 * Pemetaan berdasarkan ID eksplisit (bukan nama, agar tidak rentan typo):
 *   id=1 → kode_surat=SGG,       legacy_id=1  (PT. Syifa Global Group)
 *   id=5 → kode_surat=RSUSM,     legacy_id=2  (RSU Syifa Medika Banjarbaru)
 *   id=6 → kode_surat=RSUSM-BRB, legacy_id=3  (RSU Syifa Medika Barabai)
 *
 * Perusahaan id=2,3,4 dibiarkan kode_surat NULL (tidak punya kode surat).
 * Tidak pernah membuat baris baru di tabel companies.
 *
 * Penggunaan:
 *   php artisan surat:seed-perusahaan            # interaktif, tanya konfirmasi
 *   php artisan surat:seed-perusahaan --dry-run  # hanya cetak, tidak menulis
 *   php artisan surat:seed-perusahaan --force    # langsung tulis tanpa konfirmasi
 */
class SeedPerusahaanCommand extends Command
{
    protected $signature = 'surat:seed-perusahaan
                            {--dry-run : Hanya cetak hasil pencocokan tanpa mengubah database}
                            {--force   : Langsung update tanpa konfirmasi interaktif}';

    protected $description = 'Isi kode_surat dan legacy_id perusahaan untuk fitur penomoran surat keluar';

    /**
     * Pemetaan eksplisit: id database → data yang akan diisi.
     * JANGAN ubah id ini kecuali struktur data di database berubah.
     */
    private array $pemetaan = [
        1 => ['kode_surat' => 'SGG',       'legacy_id' => 1],
        5 => ['kode_surat' => 'RSUSM',     'legacy_id' => 2],
        6 => ['kode_surat' => 'RSUSM-BRB', 'legacy_id' => 3],
    ];

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('[DRY-RUN] Mode aktif — tidak ada yang ditulis ke database.');
            $this->newLine();
        }

        // ── 1. Cetak semua perusahaan di DB ─────────────────────────────────
        $this->info('=== Perusahaan yang ada di tabel companies ===');

        $semuaPerusahaan = DB::table('companies')
            ->select('id', 'name', 'code', 'kode_surat', 'legacy_id')
            ->orderBy('id')
            ->get();

        $this->table(
            ['id', 'name', 'code', 'kode_surat (sekarang)', 'legacy_id (sekarang)'],
            $semuaPerusahaan->map(fn ($p) => [
                $p->id,
                $p->name,
                $p->code,
                $p->kode_surat ?? 'NULL',
                $p->legacy_id  ?? 'NULL',
            ])->toArray()
        );

        // ── 2. Cocokkan pemetaan dengan DB ───────────────────────────────────
        $this->newLine();
        $this->info('=== Hasil pencocokan (berdasarkan id) ===');

        $akanDiupdate = [];
        $tidakDitemukan = [];

        foreach ($this->pemetaan as $id => $data) {
            $row = $semuaPerusahaan->firstWhere('id', $id);

            if ($row) {
                $akanDiupdate[] = [
                    'id'          => $row->id,
                    'nama_db'     => $row->name,
                    'kode_surat'  => $data['kode_surat'],
                    'legacy_id'   => $data['legacy_id'],
                ];
            } else {
                $tidakDitemukan[] = "id={$id} (kode_surat={$data['kode_surat']}, legacy_id={$data['legacy_id']})";
            }
        }

        if (! empty($akanDiupdate)) {
            $this->info('✅ Yang akan di-update:');
            $this->table(
                ['id DB', 'Nama di DB', 'kode_surat baru', 'legacy_id baru'],
                array_map(fn ($c) => [
                    $c['id'],
                    $c['nama_db'],
                    $c['kode_surat'],
                    $c['legacy_id'],
                ], $akanDiupdate)
            );
        }

        if (! empty($tidakDitemukan)) {
            $this->newLine();
            $this->error('❌ ID berikut tidak ditemukan di database:');
            foreach ($tidakDitemukan as $item) {
                $this->warn("   • {$item}");
            }
            $this->newLine();
            $this->comment('Tindakan: periksa apakah id tersebut ada di tabel companies.');

            return self::FAILURE;
        }

        // ── 3. Dry-run: berhenti di sini ────────────────────────────────────
        if ($isDryRun) {
            $this->newLine();
            $this->warn('[DRY-RUN] Selesai. Tidak ada yang ditulis.');

            return self::SUCCESS;
        }

        // ── 4. Konfirmasi (kecuali --force) ─────────────────────────────────
        $this->newLine();

        if (! $this->option('force') && ! $this->confirm(
            count($akanDiupdate) . ' perusahaan siap di-update. Lanjutkan?',
            true
        )) {
            $this->warn('Dibatalkan. Tidak ada yang ditulis.');

            return self::SUCCESS;
        }

        // ── 5. Eksekusi UPDATE ───────────────────────────────────────────────
        $this->newLine();

        foreach ($akanDiupdate as $item) {
            DB::table('companies')
                ->where('id', $item['id'])
                ->update([
                    'kode_surat' => $item['kode_surat'],
                    'legacy_id'  => $item['legacy_id'],
                ]);

            $this->line(
                "   ✅ [id={$item['id']}] {$item['nama_db']}"
                . " → kode_surat={$item['kode_surat']}, legacy_id={$item['legacy_id']}"
            );
        }

        $this->newLine();
        $this->info('🎉 Selesai. ' . count($akanDiupdate) . ' perusahaan berhasil di-update.');

        return self::SUCCESS;
    }
}
