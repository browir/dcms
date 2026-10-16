<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Impor counter nomor surat dari dump sistem lama.
 *
 * Sumber: storage/app/legacy/audit-nomor.sql
 *   Tabel 1: nomor_surat_counter — counter per (perusahaan, tahun)
 *   Tabel 2: nomor_surat         — nomor surat aktual, surat keluar saja
 *
 * Untuk tiap (legacy_id, tahun):
 *   nilai = MAX(counter_lama, nomor_urut_tertinggi_dari_surat_keluar)
 *
 * Format nomor surat: ^(\d+)/.../(YYYY)$
 *   Contoh: "307/G.SGG/X/2025" -> urut=307, tahun=2025
 *
 * Pemetaan: companies.legacy_id -> companies.id
 * Perusahaan tanpa padanan (mis. legacy 4 Koperasi, legacy 7 SMP) -> dilewati.
 *
 * Penulisan: INSERT ... ON DUPLICATE KEY UPDATE
 *     nomor_terakhir = GREATEST(nomor_terakhir, VALUES(nomor_terakhir))
 * Counter yang sudah lebih besar tidak akan diturunkan.
 *
 * PERHATIAN: Jalankan --dry-run terlebih dahulu.
 *            Jangan --execute ke dcms tanpa persetujuan eksplisit.
 */
class ImportCounterLamaCommand extends Command
{
    protected $signature = 'surat:import-counter-lama
        {--dry-run   : Tampilkan rencana tanpa menulis ke database (default)}
        {--execute   : Jalankan penulisan ke database dalam satu transaksi}
        {--file=     : Jalur file dump (override; default: storage/app/legacy/audit-nomor.sql)}';

    protected $description = 'Impor counter nomor surat dari dump sistem lama ke nomor_surat_counter.';

    // Lokasi file dump default
    private const DEFAULT_DUMP = 'storage/app/legacy/audit-nomor.sql';

    // Nama tabel dan kolom di dump sistem lama
    // Sesuaikan jika dump sistem lama menggunakan nama berbeda.
    private const LEG_COUNTER_TABLE   = 'nomor_surat_counter';
    private const LEG_COUNTER_COMPANY = 'id_perusahaan';
    private const LEG_COUNTER_YEAR    = 'tahun';
    private const LEG_COUNTER_VALUE   = 'nomor_terakhir';

    private const LEG_SURAT_TABLE   = 'nomor_surat';
    private const LEG_SURAT_COMPANY = 'id_perusahaan';
    private const LEG_SURAT_NOMOR   = 'nomor';
    private const LEG_SURAT_TYPE    = 'jenis_surat';
    private const LEG_SURAT_KELUAR  = 'keluar';

    public function handle(): int
    {
        $isExecute = (bool) $this->option('execute');
        $dumpPath  = $this->option('file') ?? base_path(self::DEFAULT_DUMP);

        $this->line($isExecute
            ? '<fg=yellow>-- MODE --execute : menulis ke database --</>'
            : '<fg=cyan>-- MODE --dry-run  : tidak ada perubahan  --</>');
        $this->newLine();

        // 1. Validasi file
        if (! file_exists($dumpPath)) {
            $this->error("File dump tidak ditemukan: {$dumpPath}");
            $this->line('Letakkan file di: ' . self::DEFAULT_DUMP);
            return self::FAILURE;
        }

        $this->line("Membaca: {$dumpPath}");

        // 2. Parse dump
        [$counterData, $suratData] = $this->parseDump($dumpPath);

        $nCounter = (int) array_sum(array_map('count', $counterData));
        $nSurat   = (int) array_sum(array_map('count', $suratData));
        $this->line("  nomor_surat_counter: {$nCounter} baris | nomor_surat keluar: {$nSurat} baris");

        if ($nCounter === 0 && $nSurat === 0) {
            $this->warn('Tidak ada data terparsing. Periksa nama tabel/kolom di dump.');
            return self::FAILURE;
        }

        // 3. Gabung & hitung MAX
        $merged = $this->mergeAndMax($counterData, $suratData);

        // 4. Petakan legacy_id -> company_id
        $companyMap = $this->getCompanyMap();

        // 5. Buat rencana
        [$plan, $skipped] = $this->buildPlan($merged, $companyMap);

        // 6. Tampilkan rencana
        $this->displayPlan($plan, $skipped);

        // 7. Execute jika diminta
        if ($isExecute) {
            if (empty($plan)) {
                $this->warn('Tidak ada baris untuk ditulis.');
            } else {
                $this->executeImport($plan);
            }
        } else {
            $this->newLine();
            $this->line('<fg=cyan>Ini adalah dry-run. Gunakan --execute untuk menulis ke database.</>');
        }

        return self::SUCCESS;
    }

    // =========================================================================
    // Parsing SQL dump
    // =========================================================================

    /**
     * Parse file dump SQL.
     *
     * @return array{
     *   0: array<int, array<int, int>>,
     *   1: array<int, array<int, int>>
     * }
     */
    private function parseDump(string $path): array
    {
        $content     = file_get_contents($path);
        $counterData = [];
        $suratData   = [];

        // Tabel counter
        foreach ($this->extractInserts($content, self::LEG_COUNTER_TABLE) as $row) {
            $comp  = $this->toInt($row[self::LEG_COUNTER_COMPANY] ?? null);
            $tahun = $this->toInt($row[self::LEG_COUNTER_YEAR]    ?? null);
            $nilai = $this->toInt($row[self::LEG_COUNTER_VALUE]   ?? null);

            if ($comp === null || $tahun === null || $nilai === null) {
                continue;
            }

            $counterData[$comp][$tahun] = max($counterData[$comp][$tahun] ?? 0, $nilai);
        }

        // Tabel surat keluar
        foreach ($this->extractInserts($content, self::LEG_SURAT_TABLE) as $row) {
            if (($row[self::LEG_SURAT_TYPE] ?? '') !== self::LEG_SURAT_KELUAR) {
                continue;
            }

            $comp  = $this->toInt($row[self::LEG_SURAT_COMPANY] ?? null);
            $nomor = trim((string) ($row[self::LEG_SURAT_NOMOR] ?? ''));

            if ($comp === null || $nomor === '') {
                continue;
            }

            // Urut dari awal nomor: ^(\d+)/
            if (! preg_match('/^(\d+)\//', $nomor, $m)) {
                continue;
            }
            $urut = (int) $m[1];

            // Tahun dari akhir nomor: /(\d{4})$
            if (! preg_match('/\/(\d{4})$/', $nomor, $m)) {
                continue;
            }
            $tahun = (int) $m[1];

            $suratData[$comp][$tahun] = max($suratData[$comp][$tahun] ?? 0, $urut);
        }

        return [$counterData, $suratData];
    }

    /**
     * Ekstrak baris INSERT untuk tabel tertentu dari konten SQL dump.
     * Mendukung single-row dan multi-row VALUES, dengan/tanpa backtick.
     *
     * @return iterable<array<string, string|int|null>>
     */
    private function extractInserts(string $sql, string $tableName): iterable
    {
        $tEsc = preg_quote($tableName, '/');

        // Tangkap: INSERT INTO `table` (cols) VALUES ...;
        $pattern = '/INSERT\s+INTO\s+`?' . $tEsc . '`?\s*\(([^)]+)\)\s*VALUES\s*([\s\S]*?);/i';

        if (! preg_match_all($pattern, $sql, $matches, PREG_SET_ORDER)) {
            return;
        }

        foreach ($matches as $match) {
            // Parse nama kolom (hapus backtick dan spasi)
            $cols = array_map(
                static fn (string $c): string => trim(trim($c), '`"\' '),
                explode(',', $match[1])
            );

            // Parse semua baris VALUES
            foreach ($this->parseValueRows($match[2]) as $values) {
                if (count($values) !== count($cols)) {
                    continue;
                }
                yield array_combine($cols, $values);
            }
        }
    }

    /**
     * Tokenizer VALUES: pisahkan baris nilai dari segmen VALUES SQL.
     * Menangani: string kutip tunggal/ganda, escape MySQL, NULL, angka.
     *
     * @return list<list<string|int|null>>
     */
    private function parseValueRows(string $rawValues): array
    {
        $rows = [];
        $pos  = 0;
        $len  = strlen($rawValues);

        while ($pos < $len) {
            // Cari pembuka '('
            while ($pos < $len && $rawValues[$pos] !== '(') {
                $pos++;
            }
            if ($pos >= $len) {
                break;
            }
            $pos++; // lewati '('

            $row     = [];
            $token   = '';
            $inStr   = false;
            $strChar = '';

            while ($pos < $len) {
                $ch = $rawValues[$pos];

                if ($inStr) {
                    if ($ch === '\\' && $pos + 1 < $len) {
                        $next   = $rawValues[$pos + 1];
                        $token .= match ($next) {
                            'n'    => "\n",
                            'r'    => "\r",
                            't'    => "\t",
                            '\\'   => '\\',
                            "'"    => "'",
                            '"'    => '"',
                            '0'    => "\0",
                            default => $next,
                        };
                        $pos += 2;
                        continue;
                    }

                    if ($ch === $strChar) {
                        // Double-quote escape: '' atau ""
                        if ($pos + 1 < $len && $rawValues[$pos + 1] === $strChar) {
                            $token .= $strChar;
                            $pos   += 2;
                            continue;
                        }
                        $inStr = false;
                        $pos++;
                        continue;
                    }

                    $token .= $ch;
                    $pos++;
                    continue;
                }

                // Di luar string
                if ($ch === "'" || $ch === '"') {
                    $inStr   = true;
                    $strChar = $ch;
                    $pos++;
                    continue;
                }

                if ($ch === ',') {
                    $row[] = $this->normalizeToken($token);
                    $token = '';
                    $pos++;
                    continue;
                }

                if ($ch === ')') {
                    $row[] = $this->normalizeToken($token);
                    $rows[] = $row;
                    $pos++;
                    break;
                }

                $token .= $ch;
                $pos++;
            }
        }

        return $rows;
    }

    /**
     * Normalisasi satu token SQL: NULL->null, digit->int, lainnya->string.
     */
    private function normalizeToken(string $token): string|int|null
    {
        $token = trim($token);

        if (strtoupper($token) === 'NULL') {
            return null;
        }

        if (ctype_digit($token)) {
            return (int) $token;
        }

        if ($token !== '' && $token[0] === '-' && ctype_digit(substr($token, 1))) {
            return (int) $token;
        }

        return $token;
    }

    private function toInt(mixed $v): ?int
    {
        if ($v === null) {
            return null;
        }
        if (is_int($v)) {
            return $v;
        }
        if (is_string($v) && is_numeric($v)) {
            return (int) $v;
        }
        return null;
    }

    // =========================================================================
    // Logika bisnis
    // =========================================================================

    /**
     * Gabungkan counter dan max-urut surat, ambil MAX per (legacy_id, tahun).
     *
     * @param  array<int, array<int, int>> $counterData
     * @param  array<int, array<int, int>> $suratData
     * @return array<int, array<int, array{counter: int, max_urut: int, nilai: int}>>
     */
    private function mergeAndMax(array $counterData, array $suratData): array
    {
        $allComp = array_unique([...array_keys($counterData), ...array_keys($suratData)]);
        $result  = [];

        foreach ($allComp as $legacyId) {
            $allYears = array_unique([
                ...array_keys($counterData[$legacyId] ?? []),
                ...array_keys($suratData[$legacyId]   ?? []),
            ]);

            foreach ($allYears as $tahun) {
                $ctr   = $counterData[$legacyId][$tahun] ?? 0;
                $urut  = $suratData[$legacyId][$tahun]   ?? 0;
                $nilai = max($ctr, $urut);

                $result[$legacyId][$tahun] = [
                    'counter'  => $ctr,
                    'max_urut' => $urut,
                    'nilai'    => $nilai,
                ];
            }
        }

        return $result;
    }

    /**
     * Ambil pemetaan legacy_id -> current company_id dari tabel companies.
     *
     * @return array<int, int>
     */
    private function getCompanyMap(): array
    {
        return DB::table('companies')
            ->whereNotNull('legacy_id')
            ->pluck('id', 'legacy_id')
            ->map(static fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Buat daftar rencana dan daftar perusahaan yang dilewati.
     *
     * @param  array<int, array<int, array{counter: int, max_urut: int, nilai: int}>> $merged
     * @param  array<int, int> $companyMap
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>}
     */
    private function buildPlan(array $merged, array $companyMap): array
    {
        $plan    = [];
        $skipped = [];

        foreach ($merged as $legacyId => $years) {
            if (! isset($companyMap[$legacyId])) {
                foreach (array_keys($years) as $tahun) {
                    $skipped[] = ['legacy_id' => $legacyId, 'tahun' => $tahun];
                }
                continue;
            }

            $currentId = $companyMap[$legacyId];

            foreach ($years as $tahun => $data) {
                $berbeda = $data['counter'] > 0
                    && $data['max_urut'] > 0
                    && $data['counter'] !== $data['max_urut'];

                $plan[] = [
                    'legacy_id'  => $legacyId,
                    'company_id' => $currentId,
                    'tahun'      => $tahun,
                    'counter'    => $data['counter'],
                    'max_urut'   => $data['max_urut'],
                    'nilai'      => $data['nilai'],
                    'berbeda'    => $berbeda,
                ];
            }
        }

        usort($plan, static fn ($a, $b) => [$a['company_id'], $a['tahun']] <=> [$b['company_id'], $b['tahun']]);

        return [$plan, $skipped];
    }

    // =========================================================================
    // Tampilan
    // =========================================================================

    private function displayPlan(array $plan, array $skipped): void
    {
        if (empty($plan)) {
            $this->warn('Tidak ada data yang cocok (semua legacy_id tanpa padanan?).');
            return;
        }

        $companyNames = DB::table('companies')
            ->whereIn('id', array_column($plan, 'company_id'))
            ->pluck('kode_surat', 'id')
            ->all();

        $tableRows = array_map(
            static function (array $r) use ($companyNames): array {
                $kode = $companyNames[$r['company_id']] ?? "id={$r['company_id']}";
                $flag = $r['berbeda'] ? '!' : '';
                return [$kode, $r['tahun'], $r['counter'], $r['max_urut'], $r['nilai'], $flag];
            },
            $plan,
        );

        $this->table(
            ['Perusahaan', 'Tahun', 'Counter Lama', 'Max Nomor Surat', 'Nilai Ditulis', 'Beda?'],
            $tableRows,
        );

        foreach ($plan as $r) {
            if ($r['berbeda']) {
                $kode = $companyNames[$r['company_id']] ?? "id={$r['company_id']}";
                $this->warn(
                    "BEDA {$kode} {$r['tahun']}: "
                    . "counter_lama={$r['counter']}, max_nomor_surat={$r['max_urut']}"
                    . " -> akan ditulis {$r['nilai']}"
                );
            }
        }

        if (! empty($skipped)) {
            $this->newLine();
            $this->line('<fg=yellow>Dilewati (tidak ada padanan di companies.legacy_id):</>');
            $byLegacy = [];
            foreach ($skipped as $s) {
                $byLegacy[$s['legacy_id']][] = $s['tahun'];
            }
            foreach ($byLegacy as $legacyId => $tahuns) {
                $this->line('  legacy_id=' . $legacyId . ', tahun: [' . implode(', ', $tahuns) . ']');
            }
        }
    }

    // =========================================================================
    // Database
    // =========================================================================

    private function executeImport(array $plan): void
    {
        $this->line('Menulis ke database...');

        DB::transaction(function () use ($plan): void {
            foreach ($plan as $r) {
                // GREATEST: counter yang sudah lebih besar tidak diturunkan
                DB::statement(
                    'INSERT INTO nomor_surat_counter (perusahaan_id, tahun, nomor_terakhir)
                     VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE
                         nomor_terakhir = GREATEST(nomor_terakhir, VALUES(nomor_terakhir))',
                    [$r['company_id'], $r['tahun'], $r['nilai']],
                );
            }
        });

        $this->info('OK ' . count($plan) . ' baris berhasil diimpor ke nomor_surat_counter.');
    }
}
