<?php

namespace App\Models;

use App\Enums\JenisSurat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Surat.
 *
 * Merepresentasikan surat keluar (dan nantinya surat masuk) yang dikelola sistem.
 *
 * Catatan desain penting:
 * - Perusahaan TIDAK disimpan di tabel ini. Gunakan $surat->unit->company untuk
 *   mendapatkan perusahaan, atau eager-load: Surat::with('unit.company')->...
 * - Kolom 'nomor' memiliki UNIQUE constraint di DB sebagai pengaman kedua
 *   setelah transaksi SELECT FOR UPDATE di NomorSuratService.
 * - Jangan buat Surat secara langsung dengan new Surat() + save() untuk surat keluar.
 *   Selalu gunakan NomorSuratService::generate() agar nomor dibuat dengan benar.
 */
class Surat extends Model
{
    protected $table = 'surat';

    protected $fillable = [
        'legacy_id',
        'nomor',
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
