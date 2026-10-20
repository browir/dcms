<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements Auditable, FilamentUser
{
    use HasFactory, HasPushSubscriptions, HasRoles, Notifiable, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'username',
        'nip',
        'password',
        'company_id',
        'department_id',
        'unit_id',
        'email_verified_at',
        'is_active',
    ];

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
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'company_id' => 'integer',
        'department_id' => 'integer',
        'unit_id' => 'integer',
    ];

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
