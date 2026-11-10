<?php

namespace App\Services;

use App\Enum\JenisSurat;
use App\Models\DocumentCategory;
use App\Models\LetterNumber;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Service untuk penomoran dokumen MANUAL.
 *
 * Bertanggung jawab atas:
 *  - Normalisasi dan validasi keunikan nomor manual (case-insensitive, termasuk nomor otomatis).
 *  - Deteksi kesesuaian format standar (peringatan non-blocking).
 *  - Pembuatan LetterNumber dengan numbering_type = 'manual' tanpa menyentuh counter otomatis.
 *  - Otorisasi hak akses pembuatan nomor manual.
 *
 * Aturan Desain:
 *  - Nomor manual TIDAK memengaruhi tabel letter_number_counters.
 *  - Format bebas; bila tidak sesuai pola standar, peringatan dikembalikan (bukan error).
 *  - Keunikan dicek secara case-insensitive terhadap SELURUH LetterNumber (auto + manual).
 *  - letter_numbers tidak memiliki soft delete, namun documents yang mengguna nomor juga
 *    memegang nomor selamanya (deleted documents masih "memakai" nomor tersebut).
 */
class LetterNumberManualService
{
    /**
     * Pola format nomor standar:
     *   {urut:3+}/{kode_jenis}.{kode_unit}/{kode_perusahaan}/{bulan_romawi}/{tahun}
     *   Contoh: 001/SOP.2/SGG/VII/2026
     */
    public const POLA_STANDAR_REGEX = '/^\d{3,}\/[A-Za-z0-9\-]+\.[A-Za-z0-9\-]+\/[A-Za-z0-9\-]+\/[IVXLCDMivxlcdm]+\/\d{4}$/';

    /**
     * Normalisasi nomor: trim spasi di awal/akhir dan rapikan spasi ganda di tengah.
     */
    public static function normalize(?string $nomor): string
    {
        if ($nomor === null) {
            return '';
        }

        return preg_replace('/\s+/', ' ', trim($nomor)) ?? '';
    }

    /**
     * Cek apakah nomor mengikuti pola format standar.
     */
    public static function matchesStandardPattern(string $nomor): bool
    {
        return (bool) preg_match(self::POLA_STANDAR_REGEX, self::normalize($nomor));
    }

    /**
     * Kembalikan pesan peringatan jika nomor tidak sesuai pola standar, null jika sesuai.
     * Input kosong tidak menghasilkan peringatan.
     */
    public static function getPatternWarning(string $nomor): ?string
    {
        $normalized = self::normalize($nomor);

        if ($normalized === '') {
            return null;
        }

        if (! self::matchesStandardPattern($normalized)) {
            return 'Nomor tidak mengikuti format standar ({urut}/{jenis}.{unit}/{perusahaan}/{bulan_romawi}/{tahun}). Nomor manual tetap dapat disimpan.';
        }

        return null;
    }

    /**
     * Validasi nomor manual:
     *  - Tidak boleh kosong
     *  - Maksimal 255 karakter (sesuai kolom database)
     *  - Unik secara case-insensitive terhadap seluruh LetterNumber
     *
     * @param  string   $nomor
     * @param  int|null $excludeId ID LetterNumber yang diabaikan saat edit (lewati diri sendiri)
     * @return string Nomor yang sudah dinormalisasi
     *
     * @throws ValidationException
     */
    public static function validate(string $nomor, ?int $excludeId = null): string
    {
        $normalized = self::normalize($nomor);

        if ($normalized === '') {
            throw ValidationException::withMessages([
                'nomor_manual' => 'Nomor dokumen manual wajib diisi.',
            ]);
        }

        if (mb_strlen($normalized) > 255) {
            throw ValidationException::withMessages([
                'nomor_manual' => 'Nomor dokumen maksimal 255 karakter.',
            ]);
        }

        $exists = LetterNumber::query()
            ->whereRaw('LOWER(nomor) = ?', [mb_strtolower($normalized)])
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'nomor_manual' => "Nomor dokumen '{$normalized}' sudah digunakan. Gunakan nomor yang berbeda.",
            ]);
        }

        return $normalized;
    }

    /**
     * Cek hak akses pembuatan nomor manual.
     * Diizinkan untuk super_admin, Sekretaris, atau pemegang izin CreateManual:LetterNumber.
     */
    public static function canCreateManual(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasRole(['super_admin', 'Sekretaris'])
            || $user->can('CreateManual:LetterNumber');
    }

    /**
     * Terbitkan LetterNumber dengan nomor manual.
     *
     * @param  Unit                           $unit
     * @param  DocumentCategory|JenisSurat|string $jenis
     * @param  Carbon                         $tanggal
     * @param  string                         $nomorManual
     * @param  string                         $judul
     * @param  string|null                    $perihal
     * @param  string|null                    $deskripsi
     * @param  User|null                      $by
     * @return LetterNumber
     *
     * @throws AuthorizationException|ValidationException
     */
    public static function issue(
        Unit $unit,
        DocumentCategory|JenisSurat|string $jenis,
        Carbon $tanggal,
        string $nomorManual,
        string $judul,
        ?string $perihal = null,
        ?string $deskripsi = null,
        ?User $by = null,
    ): LetterNumber {
        if ($by && ! self::canCreateManual($by)) {
            throw new AuthorizationException('Anda tidak memiliki hak akses untuk menerbitkan nomor dokumen manual.');
        }

        // Normalisasi dan validasi awal (sebelum transaksi, untuk UX yang lebih cepat).
        $nomor = self::validate($nomorManual);

        // Resolve jenis dan kategori (sama seperti LetterNumberService::issue).
        $department = $unit->department;
        $company    = $department?->company;

        if (! $company) {
            throw new \InvalidArgumentException("Unit {$unit->name} belum terhubung ke perusahaan.");
        }

        $category = $jenis instanceof DocumentCategory ? $jenis : null;
        $jenisResolved = match (true) {
            $category !== null           => $category->jenis() ?? throw new \InvalidArgumentException("Kategori {$category->name} belum punya kode jenis."),
            $jenis instanceof JenisSurat => $jenis,
            default                      => JenisSurat::from((string) $jenis),
        };
        $category ??= DocumentCategory::forCode($jenisResolved, $company->id);

        return DB::transaction(function () use ($unit, $jenisResolved, $category, $tanggal, $nomor, $judul, $perihal, $deskripsi, $by, $department, $company) {
            // Re-validasi keunikan di dalam transaksi dengan lock untuk mencegah race condition.
            $exists = LetterNumber::query()
                ->whereRaw('LOWER(nomor) = ?', [mb_strtolower($nomor)])
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'nomor_manual' => "Nomor dokumen '{$nomor}' sudah digunakan. Gunakan nomor yang berbeda.",
                ]);
            }

            return LetterNumber::create([
                'nomor'          => $nomor,
                'nomor_urut'     => null,   // nomor manual tidak memiliki urutan counter
                'numbering_type' => LetterNumber::NUMBERING_MANUAL,
                'jenis_surat'    => $jenisResolved,
                'category_id'    => $category?->id,
                'company_id'     => $company->id,
                'department_id'  => $department->id,
                'unit_id'        => $unit->id,
                'tanggal_surat'  => $tanggal->toDateString(),
                'judul_surat'    => $judul,
                'perihal'        => $perihal,
                'deskripsi'      => $deskripsi,
                'created_by'     => $by?->id,
            ]);
        });
    }
}

