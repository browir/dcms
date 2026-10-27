<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model NomorSuratCounter.
 *
 * Tabel nomor_surat_counter menyimpan nomor urut terakhir per (perusahaan, tahun surat).
 * Tabel ini TIDAK punya auto-increment id — primary key adalah (perusahaan_id, tahun).
 *
 * JANGAN mengubah nomor_terakhir langsung via model ini dari luar NomorSuratService.
 * Seluruh logika increment harus ada di NomorSuratService::generate() dalam satu transaksi.
 */
class NomorSuratCounter extends Model
{
    // Tabel ini tidak punya timestamps
    public $timestamps = false;

    // Composite primary key — Eloquent tidak mendukung natively,
    // tapi cukup untuk kebutuhan kita karena semua akses via DB::statement / lockForUpdate()
    protected $primaryKey = null;

    public $incrementing = false;

    protected $table = 'nomor_surat_counter';

    protected $fillable = [
        'perusahaan_id',
        'tahun',
        'nomor_terakhir',
    ];

    protected $casts = [
        'tahun'          => 'integer',
        'nomor_terakhir' => 'integer',
    ];

    // ── Relasi ──────────────────────────────────────────────────────────────

    public function perusahaan(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'perusahaan_id');
    }
}
