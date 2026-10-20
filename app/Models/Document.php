<?php

namespace App\Models;

use App\Enum\JenisSurat;
use App\Notifications\DocumentApprovedByKabidNotification;
use App\Notifications\DocumentCreatedNotification;
use App\Notifications\DocumentFinalDecisionNotification;
use App\Notifications\DocumentSubmittedNotification;
use App\Services\LetterNumberService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Document extends Model implements Auditable
{
    use LogsActivity, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_KABID = 'pending_kabid';

    public const STATUS_PENDING_DIREKTUR = 'pending_direktur';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_ARCHIVED = 'archived';

    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING_KABID => 'Menunggu Kabid',
            self::STATUS_PENDING_DIREKTUR => 'Menunggu Direktur',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
            self::STATUS_ARCHIVED => 'Diarsipkan',
        ];
    }

    protected $fillable = [
        'title',
        'code_number',
        'letter_number_id',
        'description',
        'file_path',
        'file_name',
        'file_hash',
        'content',
        'document_type',
        'generated_file_path',
        'company_id',
        'department_id',
        'unit_id',
        'category_id',
        'jenis_surat',
        'tanggal_surat',
        'user_id',
        'updated_by',
        'status',
        'version',
        'expires_at',
        'review_reminder_sent_at',
        'reviewed_by_kabid',
        'kabid_reviewed_at',
        'kabid_notes',
        'reviewed_by_direktur',
        'direktur_reviewed_at',
        'direktur_notes',
        'is_mandatory_read',
        'is_public',
    ];

    protected $casts = [
        'kabid_reviewed_at' => 'datetime',
        'direktur_reviewed_at' => 'datetime',
        'expires_at' => 'date',
        'tanggal_surat' => 'date',
        'jenis_surat' => JenisSurat::class,
        'review_reminder_sent_at' => 'date',
        'is_mandatory_read' => 'boolean',
        'is_public' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($document) {
            // ⭐ AUTO-SET user_id dari user yang login
            if (Auth::check() && ! $document->user_id) {
                $document->user_id = Auth::id();
            }

            // Setiap dokumen memakai nomor surat dari register. code_number yang diisi
            // langsung hanya untuk impor dokumen lama yang sudah bernomor.
            if (! $document->code_number || $document->letter_number_id) {
                $document->attachLetterNumber();
            }

            // Auto-set version jika belum ada
            if (! $document->version) {
                $document->version = '1.0';
            }

            // Auto-detect document type
            if (! $document->document_type) {
                $document->detectDocumentType();
            }
        });

        static::updating(function ($document) {
            // ⭐ AUTO-SET updated_by saat dokumen diedit
            if (Auth::check()) {
                $document->updated_by = Auth::id();
            }
        });

        static::created(function ($document) {
            // Generate file untuk form/hybrid documents
            if (in_array($document->document_type, ['form', 'hybrid']) && $document->content) {
                $document->generateFileFromContent();
            }

            // Kirim notifikasi dokumen baru ke anggota departemen & kabid (kecuali creator)
            $recipients = User::where('department_id', $document->department_id)
                ->where('id', '!=', $document->user_id)
                ->get();

            // Jika tidak ada penerima di departemen, fallback ke kabid & super_admin
            if ($recipients->isEmpty()) {
                $recipients = User::role(['kabid', 'super_admin'])
                    ->where('id', '!=', $document->user_id)
                    ->get();
            }

            if ($recipients->isNotEmpty()) {
                try {
                    Notification::send($recipients, new DocumentCreatedNotification($document));
                } catch (\Throwable $e) {
                    Log::error('Failed to send DocumentCreatedNotification: '.$e->getMessage());
                }
            }
        });
    }

    /**
     * Hubungkan dokumen baru ke nomor surat di register, lalu salin nomor, jenis, tanggal,
     * dan organisasi penerbitnya. Nomor yang dipilih harus belum dipakai dokumen lain; tanpa
     * nomor, dokumen yang membawa jenis_surat langsung diterbitkan nomor baru.
     * Dipanggil dari event `creating`, di dalam transaksi save() (lihat save()).
     */
    public function attachLetterNumber(): void
    {
        if ($this->letter_number_id) {
            $letter = LetterNumber::query()->lockForUpdate()->findOrFail($this->letter_number_id);

            if ($letter->isUsed()) {
                throw ValidationException::withMessages([
                    'letter_number_id' => "Nomor surat {$letter->nomor} sudah dipakai dokumen lain.",
                ]);
            }
        } elseif ($this->jenis_surat && $this->unit) {
            $letter = app(LetterNumberService::class)->issue(
                $this->unit,
                $this->jenis_surat,
                $this->tanggal_surat ?? today(),
                $this->title,
                by: $this->user,
            );
            $this->letter_number_id = $letter->id;
        } else {
            throw ValidationException::withMessages([
                'letter_number_id' => 'Dokumen wajib memakai nomor surat. Ambil nomor surat terlebih dahulu.',
            ]);
        }

        $this->code_number = $letter->nomor;
        $this->jenis_surat = $letter->jenis_surat;
        $this->tanggal_surat = $letter->tanggal_surat;
        $this->company_id = $letter->company_id;
        $this->department_id = $letter->department_id ?? $this->department_id;
        $this->unit_id = $letter->unit_id;
    }

    /**
     * INSERT dokumen baru dibungkus transaksi supaya kunci counter nomor surat (diambil di
     * event `creating`) bertahan sampai commit, dan nomor tidak terpakai kalau insert gagal.
     * Di dalam transaksi lain (mis. halaman Filament) ini menjadi savepoint, jadi counter
     * tetap ikut dibatalkan walau transaksi luarnya menangkap error dan lanjut.
     */
    public function save(array $options = []): bool
    {
        if ($this->exists) {
            return parent::save($options);
        }

        return DB::transaction(fn () => parent::save($options));
    }

    /**
     * Auto-detect document type
     */
    public function detectDocumentType(): void
    {
        if ($this->file_path && $this->content) {
            $this->document_type = 'hybrid';
        } elseif ($this->file_path) {
            $this->document_type = 'file';
        } elseif ($this->content) {
            $this->document_type = 'form';
        }
    }

    /**
     * Get accessible URL for the primary file or generated file.
     */
    public function getFileUrlAttribute(): ?string
    {
        $path = $this->file_path ?? $this->generated_file_path;

        if (! $path) {
            return null;
        }

        try {
            $disk = Storage::disk('documents');
            if ($disk->exists($path)) {
                return asset('storage/documents/'.ltrim($path, '/'));
            }

            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Generate HTML file dari content
     */
    public function generateFileFromContent(): bool
    {
        if (! $this->content) {
            return false;
        }

        try {
            $filename = "doc-{$this->id}-".time().'.html';
            $filePath = "generated/{$filename}";

            $html = $this->buildHtmlContent();
            Storage::disk('documents')->put($filePath, $html);

            $this->generated_file_path = $filePath;
            $this->saveQuietly();

            return true;
        } catch (\Exception $e) {
            Log::error("Generate file failed: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Build HTML content
     */
    private function buildHtmlContent(): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>{$this->title}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
                .header { border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
                .title { font-size: 24px; font-weight: bold; color: #333; }
                .meta { color: #666; font-size: 14px; margin-top: 10px; }
                .content { margin-top: 20px; white-space: pre-wrap; }
            </style>
        </head>
        <body>
            <div class='header'>
                <div class='title'>{$this->title}</div>
                <div class='meta'>
                    <strong>Document Number:</strong> {$this->code_number} | 
                    <strong>Type:</strong> {$this->document_type} |
                    <strong>Status:</strong> {$this->status}
                </div>
            </div>
            <div class='content'>{$this->content}</div>
        </body>
        </html>";
    }

    // ========== RELATIONSHIPS ==========

    public function bookmarks(): HasMany
    {
        return $this->hasMany(DocumentBookmark::class);
    }

    public function bookmarkedByUsers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'document_bookmarks')->withTimestamps();
    }

    public function readAcknowledgments(): HasMany
    {
        return $this->hasMany(DocumentReadAcknowledgment::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(\Spatie\Activitylog\Models\Activity::class, 'subject_id')->where('subject_type', static::class);
    }

    public function rejections(): HasMany
    {
        return $this->hasMany(DocumentRejection::class)->latest();
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(DocumentChangeRequest::class);
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(DocumentDiscussion::class);
    }

    /**
     * Cek apakah dokumen di-bookmark oleh user tertentu.
     */
    public function isBookmarkedBy(?User $user = null): bool
    {
        /** @var User|null $user */
        $user ??= Auth::user();
        if (! $user) {
            return false;
        }

        return $this->bookmarks()->where('user_id', $user->id)->exists();
    }

    /**
     * Toggle bookmark dokumen untuk user saat ini.
     */
    public function toggleBookmark(?User $user = null): bool
    {
        /** @var User|null $user */
        $user ??= Auth::user();
        if (! $user) {
            return false;
        }

        $existing = $this->bookmarks()->where('user_id', $user->id)->first();
        if ($existing) {
            $existing->delete();

            return false; // removed
        }

        $this->bookmarks()->create(['user_id' => $user->id]);

        return true; // added
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function allowedUnits(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'document_unit_access', 'document_id', 'unit_id')->withTimestamps();
    }

    public function letterNumber(): BelongsTo
    {
        return $this->belongsTo(LetterNumber::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class);
    }

    /**
     * Relasi ke User (pembuat/pemilik dokumen)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke User yang terakhir mengedit dokumen
     */
    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function kabidReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_kabid');
    }

    public function direkturReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_direktur');
    }

    // ========== WORKFLOW & NOTIFIKASI ==========

    /**
     * Ajukan dokumen untuk direview oleh Kabid.
     */
    public function submitForReview(): void
    {
        // Semua dokumen (termasuk dari Kabid) wajib melalui review Kabid terlebih dahulu
        $this->update(['status' => self::STATUS_PENDING_KABID]);

        // Cari Kabid di departemen yang sama
        $kabidUsers = User::role('kabid')
            ->where('department_id', $this->department_id)
            ->get();

        // Fallback: Kabid di perusahaan yang sama (jika tidak ada di departemen)
        if ($kabidUsers->isEmpty()) {
            $kabidUsers = User::role('kabid')
                ->where('company_id', $this->company_id)
                ->get();
        }

        if ($kabidUsers->isNotEmpty()) {
            try {
                Notification::send($kabidUsers, new DocumentSubmittedNotification($this));
            } catch (\Throwable $e) {
                Log::error('Failed to send DocumentSubmittedNotification: '.$e->getMessage());
            }
        }
    }

    /**
     * Persetujuan oleh Kabid -> lanjut ke Direktur
     */
    public function approveByKabid(User $kabid, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_PENDING_DIREKTUR,
            'reviewed_by_kabid' => $kabid->id,
            'kabid_reviewed_at' => now(),
            'kabid_notes' => $notes,
        ]);

        // Kirim notifikasi ke para Direktur perusahaan
        $direkturUsers = User::role('direktur')
            ->where('company_id', $this->company_id)
            ->get();

        if ($direkturUsers->isEmpty()) {
            // Fallback jika direktur tidak diset per company
            $direkturUsers = User::role('direktur')->get();
        }

        if ($direkturUsers->isNotEmpty()) {
            try {
                Notification::send($direkturUsers, new DocumentApprovedByKabidNotification($this));
            } catch (\Throwable $e) {
                Log::error('Failed to send DocumentApprovedByKabidNotification: '.$e->getMessage());
            }
        }
    }

    /**
     * Penolakan oleh Kabid
     */
    public function rejectByKabid(User $kabid, string $notes): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'reviewed_by_kabid' => $kabid->id,
            'kabid_reviewed_at' => now(),
            'kabid_notes' => $notes,
        ]);

        $this->rejections()->create([
            'user_id' => $kabid->id,
            'role' => 'kabid',
            'notes' => $notes,
        ]);

        if ($this->user) {
            try {
                $this->user->notify(new DocumentFinalDecisionNotification($this, 'rejected'));
            } catch (\Throwable $e) {
                Log::error('Failed to send DocumentFinalDecisionNotification (rejected): '.$e->getMessage());
            }
        }
    }

    /**
     * Persetujuan akhir oleh Direktur
     */
    public function approveByDirektur(User $direktur, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'reviewed_by_direktur' => $direktur->id,
            'direktur_reviewed_at' => now(),
            'direktur_notes' => $notes,
        ]);

        try {
            if ($this->user) {
                $this->user->notify(new DocumentFinalDecisionNotification($this, 'approved'));
            }

            if ($this->kabidReviewer && $this->kabidReviewer->id !== $this->user_id) {
                $this->kabidReviewer->notify(new DocumentFinalDecisionNotification($this, 'approved'));
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send DocumentFinalDecisionNotification (approved): '.$e->getMessage());
        }
    }

    /**
     * Penolakan akhir oleh Direktur
     */
    public function rejectByDirektur(User $direktur, string $notes): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'reviewed_by_direktur' => $direktur->id,
            'direktur_reviewed_at' => now(),
            'direktur_notes' => $notes,
        ]);

        $this->rejections()->create([
            'user_id' => $direktur->id,
            'role' => 'direktur',
            'notes' => $notes,
        ]);

        try {
            if ($this->user) {
                $this->user->notify(new DocumentFinalDecisionNotification($this, 'rejected'));
            }

            if ($this->kabidReviewer && $this->kabidReviewer->id !== $this->user_id) {
                $this->kabidReviewer->notify(new DocumentFinalDecisionNotification($this, 'rejected'));
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send DocumentFinalDecisionNotification (rejected): '.$e->getMessage());
        }
    }

    /**
     * Ajukan ulang dokumen yang sebelumnya ditolak, kembali ke alur review Kabid.
     * Dipanggil saat atasan menyetujui DocumentChangeRequest.
     */
    public function resubmit(): void
    {
        $this->update(['status' => self::STATUS_PENDING_KABID]);

        // Kirim notifikasi ke Kabid departemen yang sama
        $kabidUsers = User::role('kabid')
            ->where('department_id', $this->department_id)
            ->get();

        // Fallback: Kabid di perusahaan yang sama
        if ($kabidUsers->isEmpty()) {
            $kabidUsers = User::role('kabid')
                ->where('company_id', $this->company_id)
                ->get();
        }

        if ($kabidUsers->isNotEmpty()) {
            try {
                Notification::send($kabidUsers, new DocumentSubmittedNotification($this));
            } catch (\Throwable $e) {
                Log::error('Failed to send DocumentSubmittedNotification (resubmit): '.$e->getMessage());
            }
        }
    }

    /**
     * Scope khusus untuk Reviewer Panel
     */
    public function scopeForReviewer(Builder $query): Builder
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('super_admin')) {
            return $query;
        }

        if ($user->hasRole('direktur')) {
            // Direktur HANYA melihat dokumen yang sudah di-ACC Kabid (pending_direktur)
            // dan dokumen yang sudah selesai (approved/rejected)
            return $query->where('company_id', $user->company_id)
                ->whereIn('status', [
                    self::STATUS_PENDING_DIREKTUR,
                    self::STATUS_APPROVED,
                    self::STATUS_REJECTED,
                ]);
        }

        if ($user->hasRole('kabid')) {
            // Kabid HANYA melihat dokumen di departemennya sendiri yang menunggu ACC Kabid
            // dan dokumen yang sudah selesai
            return $query->where('department_id', $user->department_id)
                ->whereIn('status', [
                    self::STATUS_PENDING_KABID,
                    self::STATUS_APPROVED,
                    self::STATUS_REJECTED,
                ]);
        }

        return $query->whereRaw('1 = 0');
    }

    // ========== EXPIRY SCOPES ==========

    /**
     * Dokumen yang akan kedaluwarsa dalam $days hari ke depan.
     */
    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query
            ->where('status', self::STATUS_APPROVED)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '>=', today())
            ->whereDate('expires_at', '<=', today()->addDays($days));
    }

    /**
     * Dokumen yang sudah melewati tanggal kedaluwarsa.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_APPROVED)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<', today());
    }

    // ========== FULL-TEXT SEARCH ==========

    /**
     * Full-text search scope menggunakan MySQL MATCH...AGAINST.
     * Fallback ke LIKE jika keyword terlalu pendek (< 3 karakter).
     *
     * Kolom yang dicari: title, code_number, description
     * Juga menyertakan pencarian ke relasi: department.name, unit.name, category.name
     */
    public function scopeFullTextSearch(Builder $query, string $search): Builder
    {
        $trimmed = trim($search);

        if ($trimmed === '') {
            return $query;
        }

        // Untuk kata sangat pendek (1-2 karakter), gunakan LIKE biasa
        if (mb_strlen($trimmed) < 3) {
            return $query->where(function (Builder $q) use ($trimmed) {
                $like = '%'.$trimmed.'%';
                $q->where('title', 'LIKE', $like)
                    ->orWhere('code_number', 'LIKE', $like)
                    ->orWhere('description', 'LIKE', $like);
            });
        }

        // Gunakan MySQL FULLTEXT (BOOLEAN MODE) untuk efisiensi
        // Tambahkan wildcard (*) di akhir setiap kata untuk partial matching
        $words = preg_split('/\s+/', $trimmed);
        $booleanQuery = implode(' ', array_map(
            fn (string $word) => '+'.$word.'*',
            array_filter($words)
        ));

        return $query->where(function (Builder $q) use ($booleanQuery, $trimmed) {
            // Primary: FULLTEXT BOOLEAN search
            $q->whereRaw(
                'MATCH(title, code_number, description) AGAINST(? IN BOOLEAN MODE)',
                [$booleanQuery]
            )
                // Fallback: cek relasi department & unit dengan LIKE
                ->orWhereHas('department', fn (Builder $dq) => $dq->where('name', 'LIKE', '%'.$trimmed.'%'))
                ->orWhereHas('unit', fn (Builder $uq) => $uq->where('name', 'LIKE', '%'.$trimmed.'%'))
                ->orWhereHas('category', fn (Builder $cq) => $cq->where('name', 'LIKE', '%'.$trimmed.'%'));
        });
    }

    /**
     * Apakah dokumen sudah kedaluwarsa.
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Apakah dokumen akan segera kedaluwarsa (< 30 hari).
     */
    public function getIsExpiringSoonAttribute(): bool
    {
        if (! $this->expires_at) {
            return false;
        }

        return $this->expires_at->isFuture() && $this->expires_at->diffInDays(today()) <= 30;
    }

    // ========== ACTIVITY LOG ==========
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'title',
                'code_number',
        'letter_number_id',
                'status',
                'document_type',
                'version',
                'expires_at',
                'is_public',
                'is_mandatory_read',
                'department_id',
                'unit_id',
                'category_id',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Dokumen '{$this->title}' ({$this->code_number}) {$eventName}");
    }

    /**
     * Compute and store MD5 hash of uploaded file for duplicate detection.
     */
    public static function computeFileHash(string $filePath): ?string
    {
        try {
            $disk = Storage::disk('documents');
            if (! $disk->exists($filePath)) {
                return null;
            }

            return md5($disk->get($filePath));
        } catch (\Exception $e) {
            Log::warning("Could not compute file hash for {$filePath}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Check if a file with the same hash already exists (excluding the given document ID).
     */
    public static function findDuplicateByHash(string $hash, ?int $excludeId = null): ?self
    {
        return static::withTrashed()
            ->where('file_hash', $hash)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->first();
    }

    public function scopeAccess(Builder $query)
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        // Super Admin: full bypass
        if ($user->hasRole('super_admin')) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            // Cabang 1: Dokumen Publik -> Terbuka untuk seluruh akun di perusahaan yang sama
            $q->where(function ($pub) use ($user) {
                $pub->where('is_public', true);
                if ($user->company_id) {
                    $pub->where('company_id', $user->company_id);
                }
            })

                // Cabang 2: Pembuat dokumen (Uploader) selalu dapat melihat dokumen miliknya sendiri
                ->orWhere('user_id', $user->id)

                // Cabang 3: Dokumen Non-Publik (is_public = false) -> HANYA untuk akun dengan departemen yang SAMA
                ->orWhere(function ($nonPub) use ($user) {
                    $nonPub->where('is_public', false);

                    if ($user->company_id) {
                        $nonPub->where('company_id', $user->company_id);
                    }

                    // Wajib mencocokkan departemen!
                    if ($user->department_id) {
                        $nonPub->where('department_id', $user->department_id);
                    } else {
                        // Jika akun user tidak punya departemen, kunci akses non-publik
                        $nonPub->whereRaw('1 = 0');
                    }

                    // Filter unit untuk Staff (bukan direktur/kabid/manager)
                    if (! $user->hasRole(['super_admin', 'direktur', 'kabid', 'manager']) && $user->unit_id) {
                        $nonPub->where(function ($unit) use ($user) {
                            $unit->where('unit_id', $user->unit_id)
                                ->orWhereNull('unit_id');
                        });
                    }
                });

            // Cabang 4: Granular unit access (via allowedUnits pivot)
            if ($user->unit_id) {
                $q->orWhereHas('allowedUnits', function ($au) use ($user) {
                    $au->where('units.id', $user->unit_id);
                });
            }
        });
    }

    /**
     * Scope query untuk dokumen Wajib Dibaca (Compliance Hub) yang relevan untuk user tertentu.
     */
    public function scopeMandatoryForUser(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        /** @var User $user */
        return $query->where('status', self::STATUS_APPROVED)
            ->where('is_mandatory_read', true)
            ->where(function ($q) use ($user) {
                // 1. Dokumen Publik & Buatan User Sendiri
                $q->where(function ($pub) use ($user) {
                    $pub->where('is_public', true);
                    if ($user->company_id) {
                        $pub->where('company_id', $user->company_id);
                    }
                })
                    ->orWhere('user_id', $user->id);

                // 2. Super Admin
                if ($user->hasRole('super_admin')) {
                    $q->orWhereRaw('1 = 1');

                    return;
                }

                // 3. Dokumen Non-Publik -> HANYA untuk akun di departemen yang sama
                $q->orWhere(function ($nonPub) use ($user) {
                    $nonPub->where('is_public', false);

                    if ($user->company_id) {
                        $nonPub->where('company_id', $user->company_id);
                    }

                    if ($user->department_id) {
                        $nonPub->where('department_id', $user->department_id);
                    } else {
                        $nonPub->whereRaw('1 = 0');
                    }

                    if (! $user->hasRole(['super_admin', 'direktur', 'kabid', 'manager']) && $user->unit_id) {
                        $nonPub->where(function ($unit) use ($user) {
                            $unit->where('unit_id', $user->unit_id)
                                ->orWhereNull('unit_id');
                        });
                    }
                });

                // 4. Granular Unit Permission (allowedUnits pivot)
                if ($user->unit_id) {
                    $q->orWhereHas('allowedUnits', function ($au) use ($user) {
                        $au->where('units.id', $user->unit_id);
                    });
                }
            });
    }
}
