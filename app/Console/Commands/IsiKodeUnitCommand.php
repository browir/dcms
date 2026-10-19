<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Isi kolom units.kode dari data sistem lama (kode-unit-lama.csv).
 *
 * Sumber: storage/app/legacy/kode-unit-lama.csv
 *
 * Pencocokan unit lama ke units DCMS:
 *   - Nama dinormalisasi: huruf kecil, trim, spasi ganda → satu, "&" = "dan"
 *   - Perusahaan harus sama (pemetaan: lama 1→1, 2→5, 3→6; lama 7 dilewati)
 *   - Hanya baris CSV yang kolom kode_unit_dari_nomor terisi yang diproses
 *
 * Aturan keselamatan:
 *   - Tepat satu cocok: isi kode (jika belum terisi atau sama)
 *   - Nol atau lebih dari satu cocok: JANGAN diisi, laporkan
 *   - units.kode sudah ada dengan nilai berbeda: JANGAN timpa, laporkan
 *   - TIDAK membuat unit atau department baru
 *
 * Jalankan --dry-run dulu (default). Gunakan --execute hanya setelah verifikasi.
 */
class IsiKodeUnitCommand extends Command
{
    protected $signature = 'surat:isi-kode-unit
        {--dry-run   : Tampilkan rencana tanpa menulis ke database (default)}
        {--execute   : Jalankan penulisan ke database}
        {--file=     : Jalur file CSV (override; default: storage/app/legacy/kode-unit-lama.csv)}';

    protected $description = 'Isi kolom units.kode dari data sistem lama (kode-unit-lama.csv).';

    private const DEFAULT_CSV = 'storage/app/legacy/kode-unit-lama.csv';

    /**
     * Pemetaan perusahaan_id_lama → companies.id di DCMS.
     * Method ini dapat di-override di subclass untuk keperluan testing.
     */
    protected function getCompanyMap(): array
    {
        return [
            1 => 1,
            2 => 5,
            3 => 6,
            // 7 dilewati
        ];
    }

    public function handle(): int
    {
        $isDryRun = ! $this->option('execute');

        if ($isDryRun) {
            $this->warn('MODE: DRY-RUN — tidak ada perubahan ke database.');
        } else {
            $this->warn('MODE: EXECUTE — akan menulis ke database!');
        }

        // ── 1. Tentukan dan baca file CSV ──────────────────────────────────────
        $csvPath = $this->option('file')
            ? $this->option('file')
            : base_path(self::DEFAULT_CSV);

        if (! file_exists($csvPath)) {
            $this->error("File tidak ditemukan: {$csvPath}");
            return Command::FAILURE;
        }

        $rows = $this->parseCsv($csvPath);
        if (empty($rows)) {
            $this->error('CSV kosong atau tidak dapat dibaca.');
            return Command::FAILURE;
        }

        // ── 2. Filter: hanya baris dengan kode_unit_dari_nomor terisi ──────────
        $rows = array_filter($rows, fn($r) => isset($r['kode_unit_dari_nomor']) && trim($r['kode_unit_dari_nomor']) !== '');
        $rows = array_values($rows);

        $this->info(count($rows) . ' baris CSV dengan kode_unit_dari_nomor terisi akan diproses.');

        // ── 3. Ambil semua unit DCMS dari perusahaan yang relevan ──────────────
        $companyMap     = $this->getCompanyMap();
        $dcmsCompanyIds = array_values($companyMap);
        $dcmsUnits = DB::table('units')
            ->whereIn('company_id', $dcmsCompanyIds)
            ->select('id', 'name', 'kode', 'company_id')
            ->get()
            ->toArray();

        // ── 4. Proses tiap baris CSV ───────────────────────────────────────────
        $results = [];

        foreach ($rows as $row) {
            $legacyCompanyId = (int) ($row['perusahaan_id_lama'] ?? 0);
            $legacyName      = trim($row['nama_unit'] ?? '');
            $kodeLama        = trim($row['kode_unit_dari_nomor']);

            // Lewati jika perusahaan tidak ada di peta
            if (! array_key_exists($legacyCompanyId, $companyMap)) {
                // perusahaan 7 → dilewati (tidak dilaporkan)
                continue;
            }

            $dcmsCompanyId = $companyMap[$legacyCompanyId];

            // Normalisasi nama lama
            $normalizedLegacy = $this->normalizeName($legacyName);

            // Cari cocok di unit DCMS (perusahaan sama + nama ternormalisasi sama)
            $matches = array_filter(
                $dcmsUnits,
                fn($u) => $u->company_id === $dcmsCompanyId
                    && $this->normalizeName($u->name) === $normalizedLegacy
            );
            $matches = array_values($matches);

            $unitId   = null;
            $unitName = null;
            $currentKode = null;
            $status = null;

            if (count($matches) === 0) {
                $status = 'tidak_cocok';
            } elseif (count($matches) > 1) {
                $status   = 'ganda';
                $unitName = implode(' | ', array_map(fn($u) => $u->name . '(id=' . $u->id . ')', $matches));
            } else {
                // Tepat satu cocok
                $unitId      = $matches[0]->id;
                $unitName    = $matches[0]->name;
                $currentKode = $matches[0]->kode;

                if ($currentKode !== null && $currentKode !== $kodeLama) {
                    $status = 'bentrok';
                } elseif ($currentKode === $kodeLama) {
                    $status = 'sudah_sama';
                } else {
                    $status = 'akan_diisi';
                }
            }

            $results[] = [
                'unit_id_lama'     => $row['unit_id_lama'] ?? '',
                'nama_lama'        => $legacyName,
                'perusahaan_lama'  => $legacyCompanyId,
                'kode_lama'        => $kodeLama,
                'unit_dcms_id'     => $unitId,
                'unit_dcms_name'   => $unitName,
                'kode_dcms_saat_ini' => $currentKode,
                'status'           => $status,
            ];
        }

        // ── 5. Tampilkan tabel hasil ───────────────────────────────────────────
        $this->newLine();
        $this->info('=== RENCANA PENGISIAN KODE UNIT ===');
        $this->table(
            ['ID Lama', 'Nama Lama', 'Perush Lama', 'Kode', 'Unit DCMS', 'Kode DCMS', 'Status'],
            array_map(fn($r) => [
                $r['unit_id_lama'],
                $r['nama_lama'],
                $r['perusahaan_lama'],
                $r['kode_lama'],
                $r['unit_dcms_name'] ?? '-',
                $r['kode_dcms_saat_ini'] ?? 'NULL',
                $r['status'],
            ], $results)
        );

        // ── 6. Ringkasan ───────────────────────────────────────────────────────
        $countWillFill    = count(array_filter($results, fn($r) => $r['status'] === 'akan_diisi'));
        $countAlreadySame = count(array_filter($results, fn($r) => $r['status'] === 'sudah_sama'));
        $countNoMatch     = count(array_filter($results, fn($r) => $r['status'] === 'tidak_cocok'));
        $countDuplicate   = count(array_filter($results, fn($r) => $r['status'] === 'ganda'));
        $countConflict    = count(array_filter($results, fn($r) => $r['status'] === 'bentrok'));

        $this->newLine();
        $this->info('=== RINGKASAN ===');
        $this->line("  Akan diisi      : {$countWillFill}");
        $this->line("  Sudah sama      : {$countAlreadySame}");
        $this->line("  Tidak cocok     : {$countNoMatch}");
        $this->line("  Ganda (>1 unit) : {$countDuplicate}");
        $this->line("  Bentrok (beda)  : {$countConflict}");

        // ── 6b. Unit DCMS per perusahaan yang TIDAK tersentuh ─────────────────
        $touchedUnitIds = array_filter(
            array_column($results, 'unit_dcms_id'),
            fn($id) => $id !== null
        );
        $touchedUnitIds = array_values(array_unique($touchedUnitIds));

        $this->newLine();
        $this->info('=== UNIT DCMS YANG TIDAK TERSENTUH (per perusahaan) ===');
        foreach ($dcmsCompanyIds as $cid) {
            $companyUnits = array_filter($dcmsUnits, fn($u) => $u->company_id === $cid);
            $untouched = array_filter($companyUnits, fn($u) => ! in_array($u->id, $touchedUnitIds, true));
            $companyKode = DB::table('companies')->where('id', $cid)->value('kode_surat') ?? "id={$cid}";
            $this->line("  Perusahaan {$companyKode} (id={$cid}):");
            if (empty($untouched)) {
                $this->line('    (semua unit tersentuh)');
            } else {
                foreach ($untouched as $u) {
                    $kodeInfo = $u->kode ? " [kode={$u->kode}]" : ' [kode=NULL]';
                    $this->line("    - [{$u->id}] {$u->name}{$kodeInfo}");
                }
            }
        }

        // ── 6c. Unit lama SGG (perusahaan 1) yang tidak ditemukan di DCMS ──────
        $this->newLine();
        $this->info('=== UNIT LAMA SGG (perush lama 1) YANG TIDAK DITEMUKAN DI DCMS ===');
        $sggNotFound = array_filter($results, fn($r) => $r['perusahaan_lama'] === 1 && $r['status'] === 'tidak_cocok');
        if (empty($sggNotFound)) {
            $this->line('  (semua unit SGG ditemukan)');
        } else {
            foreach ($sggNotFound as $r) {
                $this->line("  - [{$r['unit_id_lama']}] {$r['nama_lama']} (kode={$r['kode_lama']})");
            }
        }

        // ── 7. Eksekusi jika --execute ─────────────────────────────────────────
        if ($isDryRun) {
            $this->newLine();
            $this->warn('DRY-RUN selesai. Jalankan dengan --execute untuk menyimpan.');
            return Command::SUCCESS;
        }

        // Mode execute
        $toFill = array_filter($results, fn($r) => $r['status'] === 'akan_diisi');

        if (empty($toFill)) {
            $this->info('Tidak ada unit yang perlu diisi. Selesai.');
            return Command::SUCCESS;
        }

        $this->newLine();
        $this->info("Mengisi kode untuk {$countWillFill} unit...");

        DB::transaction(function () use ($toFill) {
            foreach ($toFill as $r) {
                DB::table('units')
                    ->where('id', $r['unit_dcms_id'])
                    ->whereNull('kode') // keselamatan: hanya timpa yang NULL
                    ->update(['kode' => $r['kode_lama'], 'updated_at' => now()]);
            }
        });

        $this->info("Selesai. {$countWillFill} unit berhasil diisi kodenya.");
        return Command::SUCCESS;
    }

    /**
     * Normalisasi nama untuk pencocokan:
     *   - trim, huruf kecil
     *   - spasi ganda jadi satu
     *   - "&" → "dan"
     *   - Strip sufiks kota (" barabai", " banjarbaru") di akhir nama
     *     agar nama DCMS seperti "Instalasi Rawat Jalan Barabai" cocok
     *     dengan nama lama "Instalasi Rawat Jalan"
     */
    private function normalizeName(string $name): string
    {
        $name = mb_strtolower(trim($name));
        $name = preg_replace('/\s+/', ' ', $name);
        // Ganti " & " (dengan spasi) dan "&" langsung
        $name = str_replace(' & ', ' dan ', $name);
        $name = str_replace('&', ' dan ', $name);
        // Bersihkan spasi ganda lagi setelah penggantian
        $name = preg_replace('/\s+/', ' ', $name);
        // Strip sufiks kota di akhir nama (yang ditambahkan di nama DCMS)
        foreach ([' barabai', ' banjarbaru'] as $citySuffix) {
            if (str_ends_with($name, $citySuffix)) {
                $name = substr($name, 0, -strlen($citySuffix));
                break; // hanya strip satu sufiks
            }
        }
        $name = trim($name);
        return $name;
    }

    /**
     * Baca CSV dan kembalikan array asosiatif.
     */
    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            return [];
        }

        $rows = [];
        $headers = null;

        while (($line = fgets($handle)) !== false) {
            // Hapus BOM dan CRLF
            $line = preg_replace('/^\xEF\xBB\xBF/', '', $line);
            $line = rtrim($line, "\r\n");

            if ($line === '') {
                continue;
            }

            $cols = str_getcsv($line, ',', '"');

            if ($headers === null) {
                $headers = array_map('trim', $cols);
                continue;
            }

            // Pad kolom jika kurang dari header
            while (count($cols) < count($headers)) {
                $cols[] = '';
            }

            $rows[] = array_combine($headers, array_slice($cols, 0, count($headers)));
        }

        fclose($handle);
        return $rows;
    }
}
