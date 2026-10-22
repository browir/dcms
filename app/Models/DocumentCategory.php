<?php

// app/Models/DocumentCategory.php

namespace App\Models;

use App\Enum\JenisSurat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kategori Dokumen = master jenis dokumen. `code` adalah kode jenis HRIS yang dipakai di
 * nomor dokumen, `kelompok` menentukan dokumen tampil di menu Dokumen Resmi atau Surat Keluar.
 * Kategori tanpa perusahaan berlaku untuk semua perusahaan.
 */
class DocumentCategory extends Model
{
    public const KELOMPOK_DOKUMEN = 'dokumen';

    public const KELOMPOK_SURAT_KELUAR = 'surat_keluar';

    public const KELOMPOK = [
        self::KELOMPOK_DOKUMEN => 'Dokumen Resmi',
        self::KELOMPOK_SURAT_KELUAR => 'Surat Keluar',
    ];

    protected $fillable = ['name', 'prefix', 'code', 'kelompok', 'company_id', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'category_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function jenis(): ?JenisSurat
    {
        return JenisSurat::tryFrom((string) $this->code);
    }

    /** Kategori aktif yang bisa dipakai menomori dokumen perusahaan ini (milik perusahaan + umum). */
    public function scopeUsableFor(Builder $query, ?int $companyId): Builder
    {
        return $query->where('is_active', true)
            ->whereNotNull('code')
            ->where(fn (Builder $q) => $q->whereNull('company_id')->when($companyId, fn (Builder $c) => $c->orWhere('company_id', $companyId)));
    }

    /** Kategori untuk kode jenis tertentu; kategori milik perusahaan didahulukan dari yang umum. */
    public static function forCode(JenisSurat|string $code, ?int $companyId): ?self
    {
        $code = $code instanceof JenisSurat ? $code->value : $code;

        return static::query()
            ->where('code', $code)
            ->where(fn (Builder $q) => $q->whereNull('company_id')->when($companyId, fn (Builder $c) => $c->orWhere('company_id', $companyId)))
            ->orderByRaw('company_id is null')
            ->first();
    }

    /** "C — Surat Edaran" */
    public function getOptionLabelAttribute(): string
    {
        return "{$this->code} — {$this->name}";
    }
}
