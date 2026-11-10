<?php

namespace App\Models;

use App\Enums\JenisSurat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Model Surat.
 *
 * Merepresentasikan surat keluar (dan nantinya surat masuk) yang dikelola sistem.
 *
 * Catatan desain penting:
 * - Perusahaan TIDAK disimpan di tabel ini. Gunakan $surat->unit->company untuk
 *   mendapatkan perusahaan, atau eager-load: Surat::with('unit.company')->...
 * - Kolom 'nomor' memiliki UNIQUE constraint di DB sebagai pengaman kedua
 *   setelah transaksi SELECT FOR UPDATE / INSERT ON DUPLICATE di NomorSuratService.
 * - Untuk surat otomatis: gunakan NomorSuratService::generate().
 * - Untuk surat manual: gunakan NomorSuratManualService::create().
 */
class Surat extends Model
{
    use LogsActivity, SoftDeletes;

    public const NUMBERING_AUTO = 'auto';
    public const NUMBERING_MANUAL = 'manual';

    protected $table = 'surat';

    protected $fillable = [
        'legacy_id',
        'nomor',
        'numbering_type',
        'tipe_surat',
        'status',
        'nomor_urut',
        'jenis',
        'unit_id',
        'tanggal',
        'judul_surat',
        'perihal',
        'deskripsi',
        'dibuat_oleh_id',
        'dibuat_oleh_nama',
    ];

    protected $casts = [
        'tanggal'    => 'date',
        'nomor_urut' => 'integer',
        'jenis'      => JenisSurat::class,
    ];

    public function isManual(): bool
    {
        return $this->numbering_type === self::NUMBERING_MANUAL;
    }

    public function isAuto(): bool
    {
        return $this->numbering_type !== self::NUMBERING_MANUAL;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'nomor',
                'numbering_type',
                'judul_surat',
                'perihal',
                'deskripsi',
                'tanggal',
                'jenis',
                'unit_id',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Surat '{$this->judul_surat}' ({$this->nomor}) {$eventName}");
    }

    // ── Relasi ──────────────────────────────────────────────────────────────

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Shortcut ke perusahaan via unit.
     * Memerlukan eager-load: Surat::with('unit.company')->...
     */
    public function perusahaan(): ?Company
    {
        return $this->unit?->company;
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh_id');
    }
}
