<?php

namespace App\Models;

use App\Enum\JenisSurat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Register nomor dokumen. Nomor diterbitkan lebih dulu lewat LetterNumberService::issue(),
 * lalu dokumen resminya diisi ke nomor ini (satu nomor = satu dokumen, lihat
 * documents.letter_number_id). Nomor yang sudah terbit tidak diubah dan tidak dibatalkan.
 */
class LetterNumber extends Model
{
    protected $fillable = [
        'nomor',
        'nomor_urut',
        'jenis_surat',
        'category_id',
        'company_id',
        'department_id',
        'unit_id',
        'tanggal_surat',
        'judul_surat',
        'perihal',
        'deskripsi',
        'created_by',
    ];

    protected $casts = [
        'jenis_surat' => JenisSurat::class,
        'tanggal_surat' => 'date',
        'nomor_urut' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Kategori dokumen (jenis) nomor ini; kodenya = jenis_surat. */
    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'category_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Dokumen resmi yang memakai nomor ini (termasuk yang ada di Recycle Bin). */
    public function document(): HasOne
    {
        return $this->hasOne(Document::class)->withTrashed();
    }

    /** Kegiatan unit yang memakai nomor ini sebagai surat pemberitahuan. */
    public function agenda(): HasOne
    {
        return $this->hasOne(Agenda::class);
    }

    /** Nomor sudah dipakai dokumen resmi atau surat pemberitahuan kegiatan. */
    public function isUsed(): bool
    {
        $document = $this->relationLoaded('document') ? $this->document !== null : $this->document()->exists();
        $agenda = $this->relationLoaded('agenda') ? $this->agenda !== null : $this->agenda()->exists();

        return $document || $agenda;
    }

    /** Nomor yang belum dipakai (dokumen di Recycle Bin tetap dihitung memakai nomor). */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query
            ->whereNotIn('id', DB::table('documents')->whereNotNull('letter_number_id')->select('letter_number_id'))
            ->whereNotIn('id', DB::table('agendas')->whereNotNull('letter_number_id')->select('letter_number_id'));
    }

    public function scopeUsed(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->whereIn('id', DB::table('documents')->whereNotNull('letter_number_id')->select('letter_number_id'))
            ->orWhereIn('id', DB::table('agendas')->whereNotNull('letter_number_id')->select('letter_number_id')));
    }

    /** Nomor yang boleh dilihat user: semua perusahaan untuk admin global, selain itu perusahaannya sendiri. */
    public function scopeAccess(Builder $query): Builder
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user || $user->hasRole('super_admin') || $user->can('view_all_companies_data')) {
            return $query;
        }

        return $query->where('company_id', $user->company_id);
    }

    /** Label pilihan di form dokumen: "001/A.2/SGG/VII/2025 — Judul surat". */
    public function getOptionLabelAttribute(): string
    {
        return "{$this->nomor} — {$this->judul_surat}";
    }
}
