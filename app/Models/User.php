<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use NotificationChannels\WebPush\HasPushSubscriptions;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements Auditable, FilamentUser, HasAvatar
{
    use HasFactory, HasPushSubscriptions, HasRoles, Notifiable, \OwenIt\Auditing\Auditable;

    // ── 2FA traits & helpers ─────────────────────────────────────────────────
    use \App\Concerns\HasTwoFactor;

    protected $fillable = [
        'name',
        'email',
        'avatar_url',
        'signature_path',
        'phone',
        'username',
        'nip',
        'password',
        'password_changed_at',
        'company_id',
        'department_id',
        'unit_id',
        'email_verified_at',
        'is_active',
        'two_factor_enabled',
        'two_factor_enabled_at',
        'two_factor_type',
        'two_factor_secret',
        'default_trust_days',
    ];

    /**
     * URL Avatar untuk Filament Navbar & Profile.
     */
    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url ? Storage::disk('public')->url($this->avatar_url) : null;
    }

    /**
     * Nomor tujuan untuk channel notifikasi WhatsApp via n8n.
     * Laravel memanggil routeNotificationFor{Channel}() secara otomatis.
     */
    public function routeNotificationForN8n(): ?string
    {
        return $this->phone;
    }

    public function routeNotificationForWhatsapp(): ?string
    {
        return $this->phone;
    }

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    protected $casts = [
        'email_verified_at'      => 'datetime',
        'password'               => 'hashed',
        'is_active'              => 'boolean',
        'company_id'             => 'integer',
        'department_id'          => 'integer',
        'unit_id'                => 'integer',
        'two_factor_enabled'     => 'boolean',
        'two_factor_enabled_at'  => 'datetime',
        'two_factor_secret'      => 'encrypted',
        'default_trust_days'     => 'integer',
        'password_changed_at'    => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (User $user) {
            if ($user->isDirty('password')) {
                $user->password_changed_at = now();

                // Arsipkan kata sandi lama ke riwayat jika ada
                if ($user->getOriginal('password')) {
                    \App\Models\PasswordHistory::create([
                        'user_id'    => $user->id,
                        'password'   => $user->getOriginal('password'),
                        'created_at' => now(),
                    ]);
                }
            }
        });

        static::created(function (User $user) {
            if ($user->password && ! $user->password_changed_at) {
                $user->password_changed_at = now();
            }
        });
    }

    public function passwordHistories(): HasMany
    {
        return $this->hasMany(\App\Models\PasswordHistory::class);
    }

    public function loginHistories(): HasMany
    {
        return $this->hasMany(\App\Models\UserLoginHistory::class);
    }

    /**
     * Berapa hari sejak kata sandi terakhir diubah.
     */
    public function passwordAgeDays(): ?int
    {
        if (! $this->password_changed_at) {
            return null;
        }

        return (int) $this->password_changed_at->diffInDays(now());
    }

    /**
     * Apakah disarankan untuk rotasi kata sandi (> 90 hari).
     */
    public function isPasswordRotationRecommended(): bool
    {
        $age = $this->passwordAgeDays();

        return $age !== null && $age >= 90;
    }

    /**
     * Filament Panel Access
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Anda bisa menyesuaikan ini, misalnya: return $this->is_active;
        return true;
    }

    /**
     * Query Scopes
     * Menangani error: Call to undefined method User::active()
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Relations
     */
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

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function approvedDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'approver_id');
    }

    public function documentBookmarks(): HasMany
    {
        return $this->hasMany(DocumentBookmark::class);
    }

    public function readAcknowledgments(): HasMany
    {
        return $this->hasMany(DocumentReadAcknowledgment::class);
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(DocumentChangeRequest::class);
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(DocumentDiscussion::class);
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(Signature::class);
    }

    /**
     * Helpers
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function isEmailVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * User biasa (pegawai/user) tanpa peran manajemen atau persetujuan surat.
     */
    public function isRegularUser(): bool
    {
        return ! $this->hasRole(['super_admin', 'direktur', 'kabid', 'Sekretaris', 'manager']);
    }

    /**
     * Accessors
     */
    public function getOrganizationPathAttribute(): string
    {
        $path = [];
        if ($this->company) {
            $path[] = $this->company->name;
        }
        if ($this->department) {
            $path[] = $this->department->name;
        }
        if ($this->unit) {
            $path[] = $this->unit->name;
        }

        return implode(' → ', $path);
    }
}
