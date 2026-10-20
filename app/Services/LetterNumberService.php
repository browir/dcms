<?php

namespace App\Services;

use App\Enum\JenisSurat;
use App\Models\Company;
use App\Models\LetterNumber;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Penomoran surat format HRIS (lihat PENOMORAN-SURAT-HRIS.md):
 *
 *   {urut:3 digit}/{kode_jenis}.{kode_unit}/{kode_surat_perusahaan}/{bulan_romawi}/{tahun}
 *   contoh: 001/A.2/SGG/VII/2025
 *
 * - Counter per (perusahaan, tahun tanggal surat): semua jenis & unit berbagi satu urutan,
 *   tidak reset per bulan, lewat 999 jadi 4 digit.
 * - Bulan & tahun diambil dari tanggal surat, bukan hari ini.
 * - generate() wajib dipanggil di dalam transaksi yang sama dengan INSERT dokumen, supaya
 *   row lock menahan user lain sampai commit dan nomor tidak bocor kalau insert gagal.
 */
class LetterNumberService
{
    public const ROMAWI = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

    /**
     * Ambil nomor urut berikutnya dan kunci counter-nya.
     *
     * @return array{number: string, sequence: int}
     */
    public function generate(Company $company, JenisSurat|string $jenis, ?Unit $unit, Carbon $tanggal): array
    {
        if (DB::transactionLevel() === 0) {
            return DB::transaction(fn () => $this->generate($company, $jenis, $unit, $tanggal));
        }

        $key = ['company_id' => $company->id, 'year' => $tanggal->year];

        // Pastikan baris counter ada supaya bisa di-lock (INSERT IGNORE).
        DB::table('letter_number_counters')->insertOrIgnore([
            ...$key,
            'last_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $last = (int) DB::table('letter_number_counters')->where($key)->lockForUpdate()->value('last_number');
        $sequence = $last + 1;

        DB::table('letter_number_counters')->where($key)->update([
            'last_number' => $sequence,
            'updated_at' => now(),
        ]);

        return [
            'number' => $this->format($sequence, $jenis, $this->unitCode($unit), $this->companyCode($company), $tanggal),
            'sequence' => $sequence,
        ];
    }

    /**
     * Terbitkan nomor surat baru dan catat ke register. Perusahaan penerbit mengikuti unit
     * (unit → departemen → perusahaan), seperti di HRIS.
     */
    public function issue(
        Unit $unit,
        JenisSurat|string $jenis,
        Carbon $tanggal,
        string $judul,
        ?string $perihal = null,
        ?string $deskripsi = null,
        ?User $by = null,
    ): LetterNumber {
        $jenis = $jenis instanceof JenisSurat ? $jenis : JenisSurat::from($jenis);
        $department = $unit->department;
        $company = $department?->company;

        if (! $company) {
            throw new InvalidArgumentException("Unit {$unit->name} belum terhubung ke perusahaan.");
        }

        return DB::transaction(function () use ($unit, $jenis, $tanggal, $judul, $perihal, $deskripsi, $by, $department, $company) {
            ['number' => $number, 'sequence' => $sequence] = $this->generate($company, $jenis, $unit, $tanggal);

            return LetterNumber::create([
                'nomor' => $number,
                'nomor_urut' => $sequence,
                'jenis_surat' => $jenis,
                'company_id' => $company->id,
                'department_id' => $department->id,
                'unit_id' => $unit->id,
                'tanggal_surat' => $tanggal->toDateString(),
                'judul_surat' => $judul,
                'perihal' => $perihal,
                'deskripsi' => $deskripsi,
                'created_by' => $by?->id,
            ]);
        });
    }

    /** Pratinjau nomor berikutnya tanpa memakai counter (untuk form). */
    public function preview(?Company $company, JenisSurat|string|null $jenis, ?Unit $unit, ?Carbon $tanggal): string
    {
        $tanggal ??= today();

        $last = $company
            ? (int) DB::table('letter_number_counters')
                ->where(['company_id' => $company->id, 'year' => $tanggal->year])
                ->value('last_number')
            : 0;

        return $this->format(
            $last + 1,
            $jenis ?? '?',
            $unit ? $this->unitCode($unit) : '?',
            $company ? $this->companyCode($company) : '?',
            $tanggal,
        );
    }

    public function format(int $sequence, JenisSurat|string $jenis, string $unitCode, string $companyCode, Carbon $tanggal): string
    {
        return sprintf(
            '%03d/%s.%s/%s/%s/%d',
            $sequence,
            $jenis instanceof JenisSurat ? $jenis->value : $jenis,
            $unitCode,
            $companyCode,
            self::ROMAWI[$tanggal->month - 1],
            $tanggal->year,
        );
    }

    /** Kode unit di nomor: kolom kode unit (prefix); cadangan inisial nama unit. */
    public function unitCode(?Unit $unit): string
    {
        if (! $unit) {
            return '-';
        }

        if (filled($unit->prefix)) {
            return strtoupper(trim($unit->prefix));
        }

        $initials = collect(preg_split('/\s+/', trim((string) $unit->name)) ?: [])
            ->filter()
            ->map(fn (string $word) => strtoupper(mb_substr($word, 0, 1)))
            ->implode('');

        return $initials !== '' ? mb_substr($initials, 0, 6) : '-';
    }

    /** Kode penerbit di nomor: kode_surat perusahaan; cadangan kode perusahaan. */
    public function companyCode(Company $company): string
    {
        return strtoupper(trim($company->kode_surat ?: $company->code));
    }
}
