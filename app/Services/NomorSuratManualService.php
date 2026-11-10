<?php

namespace App\Services;

use App\Enums\JenisSurat;
use App\Models\Surat;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

/**
 * Service untuk penomoran surat manual.
 *
 * Mengelola normalisasi, validasi keunikan case-insensitive, deteksi pola peringatan,
 * pembuatan surat manual, serta pengeditan nomor manual dengan audit trail.
 *
 * Aturan Desain:
 * - Nomor manual TIDAK menyentuh atau mengubah tabel nomor_surat_counter.
 * - Format nomor manual bebas; jika tidak mengikuti pola standar, berikan peringatan non-blocking.
 * - Pengecekan keunikan bersifat case-insensitive, mengabaikan spasi berlebih, dan memeriksa
 *   seluruh surat (termasuk yang otomatis maupun soft-deleted).
 */
class NomorSuratManualService
{
    /**
     * Regex pola format nomor standar:
     *   {urut:3+}/{jenis}.{unit}/{perusahaan}/{bulan_romawi}/{tahun}
     *   Contoh: 004/G.3C/SGG/IX/2025
     */
    public const POLA_STANDAR_REGEX = '/^\d{3,}\/[A-Za-z0-9-]+\.[A-Za-z0-9-]+\/[A-Za-z0-9-]+\/[IVXLCDMivxlcdm]+\/\d{4}$/';

    /**
     * Normalisasi nomor surat: trim spasi di awal/akhir dan rapikan spasi berlebih di tengah.
     */
    public static function normalize(?string $nomor): string
    {
        if ($nomor === null) {
            return '';
        }

        return preg_replace('/\s+/', ' ', trim($nomor)) ?? '';
    }

    /**
     * Cek apakah nomor mengikuti pola format standar nomor surat.
     */
    public static function matchesStandardPattern(string $nomor): bool
    {
        $normalized = self::normalize($nomor);

        return (bool) preg_match(self::POLA_STANDAR_REGEX, $normalized);
    }

    /**
     * Berikan pesan peringatan jika nomor manual tidak mengikuti pola standar.
     * Mengembalikan null jika nomor mengikuti pola.
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
     * Validasi nomor surat manual:
     * - Tidak boleh kosong
     * - Maksimal 255 karakter
     * - Unik secara case-insensitive (memeriksa auto dan manual, termasuk soft-deleted)
     *
     * @param  string   $nomor
     * @param  int|null $excludeSuratId ID surat yang diabaikan (saat edit)
     * @return string Nomor yang sudah dinormalisasi
     *
     * @throws ValidationException
     */
    public static function validate(string $nomor, ?int $excludeSuratId = null): string
    {
        $normalized = self::normalize($nomor);

        if ($normalized === '') {
            throw ValidationException::withMessages([
                'nomor' => 'Nomor surat manual wajib diisi.',
            ]);
        }

        if (mb_strlen($normalized) > 255) {
            throw ValidationException::withMessages([
                'nomor' => 'Nomor surat maksimal 255 karakter.',
            ]);
        }

        $exists = Surat::withTrashed()
            ->whereRaw('LOWER(nomor) = ?', [mb_strtolower($normalized)])
            ->when($excludeSuratId, fn ($q) => $q->where('id', '!=', $excludeSuratId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'nomor' => "Nomor surat '{$normalized}' sudah digunakan. Harap gunakan nomor yang berbeda.",
            ]);
        }

        return $normalized;
    }

    /**
     * Otorisasi hak akses pembuatan surat manual.
     * Diizinkan untuk super_admin, Sekretaris, atau user dengan permission CreateManual:SuratKeluar.
     */
    public static function canCreateManual(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasRole(['super_admin', 'Sekretaris'])
            || $user->can('CreateManual:SuratKeluar');
    }

    /**
     * Otorisasi hak akses pengeditan surat manual.
     */
    public static function canEditManual(?User $user, Surat $surat): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasRole(['super_admin', 'Sekretaris'])
            || $user->can('Update:SuratKeluar')
            || $user->can('CreateManual:SuratKeluar');
    }

    /**
     * Buat surat dengan nomor manual.
     *
     * @param  Unit              $unit
     * @param  JenisSurat|string $jenis
     * @param  Carbon            $tanggal
     * @param  string            $nomorManual
     * @param  array             $data
     * @param  User|null         $user
     * @return Surat
     *
     * @throws AuthorizationException|ValidationException
     */
    public static function create(
        Unit $unit,
        JenisSurat|string $jenis,
        Carbon $tanggal,
        string $nomorManual,
        array $data,
        ?User $user = null,
    ): Surat {
        if ($user && ! self::canCreateManual($user)) {
            throw new AuthorizationException('Anda tidak memiliki hak akses untuk menerbitkan surat dengan nomor manual.');
        }

        $nomor = self::validate($nomorManual);

        $jenisCode = $jenis instanceof JenisSurat ? $jenis->value : (string) $jenis;

        return DB::transaction(function () use ($unit, $jenisCode, $tanggal, $nomor, $data, $user) {
            // Re-validate inside transaction with lock to prevent race conditions
            $exists = Surat::withTrashed()
                ->whereRaw('LOWER(nomor) = ?', [mb_strtolower($nomor)])
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'nomor' => "Nomor surat '{$nomor}' sudah digunakan. Harap gunakan nomor yang berbeda.",
                ]);
            }

            return Surat::create([
                'nomor'            => $nomor,
                'numbering_type'   => Surat::NUMBERING_MANUAL,
                'tipe_surat'       => 'keluar',
                'status'           => $data['status'] ?? 'Tersimpan',
                'nomor_urut'       => null,
                'jenis'            => $jenisCode,
                'unit_id'          => $unit->id,
                'tanggal'          => $tanggal->format('Y-m-d'),
                'judul_surat'      => $data['judul_surat'],
                'perihal'          => $data['perihal'] ?? null,
                'deskripsi'        => $data['deskripsi'] ?? null,
                'dibuat_oleh_id'   => $user?->id ?? $data['dibuat_oleh_id'] ?? null,
                'dibuat_oleh_nama' => $user?->name ?? $data['dibuat_oleh_nama'] ?? 'Sistem',
                'legacy_id'        => $data['legacy_id'] ?? null,
            ]);
        });
    }

    /**
     * Update surat yang ada (termasuk nomor manual atau pergantian mode penomoran).
     *
     * @param  Surat     $surat
     * @param  array     $data
     * @param  User|null $user
     * @return Surat
     *
     * @throws AuthorizationException|ValidationException
     */
    public static function update(Surat $surat, array $data, ?User $user = null): Surat
    {
        if ($user && ! self::canEditManual($user, $surat)) {
            throw new AuthorizationException('Anda tidak memiliki hak akses untuk mengubah nomor surat ini.');
        }

        $targetMode = $data['numbering_type'] ?? $surat->numbering_type ?? Surat::NUMBERING_MANUAL;
        $nomorLama  = $surat->nomor;

        return DB::transaction(function () use ($surat, $data, $user, $targetMode, $nomorLama) {
            $unit = isset($data['unit_id'])
                ? Unit::with(['company', 'department'])->findOrFail($data['unit_id'])
                : $surat->unit;

            $tanggal = isset($data['tanggal'])
                ? Carbon::parse($data['tanggal'])
                : Carbon::parse($surat->tanggal);

            $jenis = isset($data['jenis'])
                ? ($data['jenis'] instanceof JenisSurat ? $data['jenis'] : JenisSurat::from($data['jenis']))
                : ($surat->jenis instanceof JenisSurat ? $surat->jenis : JenisSurat::from((string) $surat->jenis));

            // Kasus 1: Berpindah dari manual ke otomatis
            if ($surat->isManual() && $targetMode === Surat::NUMBERING_AUTO) {
                // Generate nomor otomatis baru
                $tahun = (int) $tanggal->format('Y');
                $bulan = (int) $tanggal->format('n');
                $company = $unit->company;

                do {
                    $urut = NomorSuratService::incrementCounter($company->id, $tahun);
                    $nomorBaru = NomorSuratService::formatNomor(
                        $urut,
                        $jenis->value,
                        $unit->kode,
                        $company->kode_surat,
                        $bulan,
                        $tahun,
                    );
                } while (Surat::withTrashed()->where('nomor', $nomorBaru)->exists());

                $surat->nomor          = $nomorBaru;
                $surat->nomor_urut     = $urut;
                $surat->numbering_type = Surat::NUMBERING_AUTO;
            }
            // Kasus 2: Berpindah dari otomatis ke manual, atau tetap manual dengan nomor baru
            elseif ($targetMode === Surat::NUMBERING_MANUAL) {
                $nomorInput = $data['nomor'] ?? $surat->nomor;
                $nomorBaru  = self::validate($nomorInput, $surat->id);

                // Lock check inside transaction
                $exists = Surat::withTrashed()
                    ->whereRaw('LOWER(nomor) = ?', [mb_strtolower($nomorBaru)])
                    ->where('id', '!=', $surat->id)
                    ->lockForUpdate()
                    ->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        'nomor' => "Nomor surat '{$nomorBaru}' sudah digunakan. Harap gunakan nomor yang berbeda.",
                    ]);
                }

                $surat->nomor          = $nomorBaru;
                $surat->nomor_urut     = null; // manual tidak punya counter urut
                $surat->numbering_type = Surat::NUMBERING_MANUAL;
            }

            // Perbarui kolom lain jika ada
            if (isset($data['judul_surat'])) {
                $surat->judul_surat = $data['judul_surat'];
            }
            if (array_key_exists('perihal', $data)) {
                $surat->perihal = $data['perihal'];
            }
            if (array_key_exists('deskripsi', $data)) {
                $surat->deskripsi = $data['deskripsi'];
            }
            if (isset($data['unit_id'])) {
                $surat->unit_id = $data['unit_id'];
            }
            if (isset($data['jenis'])) {
                $surat->jenis = $jenis->value;
            }
            if (isset($data['tanggal'])) {
                $surat->tanggal = $tanggal->format('Y-m-d');
            }

            $surat->save();

            // Catat perubahan nomor di ActivityLog
            if ($nomorLama !== $surat->nomor && class_exists(Activity::class)) {
                activity('surat')
                    ->performedOn($surat)
                    ->causedBy($user)
                    ->withProperties([
                        'nomor_lama' => $nomorLama,
                        'nomor_baru' => $surat->nomor,
                        'mode_lama'  => $surat->getOriginal('numbering_type'),
                        'mode_baru'  => $surat->numbering_type,
                    ])
                    ->log("Nomor surat diubah dari '{$nomorLama}' menjadi '{$surat->nomor}'");
            }

            return $surat;
        });
    }
}

