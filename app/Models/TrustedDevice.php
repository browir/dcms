<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrustedDevice extends Model
{
    protected $fillable = [
        'user_id',
        'token_hash',
        'device_name',
        'user_agent',
        'ip_address',
        'last_ip',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'expires_at'   => 'datetime',
        'last_used_at' => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Nama perangkat yang mudah dibaca, dipotong dari user agent jika tidak ada label.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->device_name) {
            return $this->device_name;
        }

        $ua = $this->user_agent ?? '';

        // Deteksi browser sederhana
        $browser = 'Browser Tidak Diketahui';
        if (str_contains($ua, 'Chrome') && ! str_contains($ua, 'Chromium')) {
            $browser = 'Chrome';
        } elseif (str_contains($ua, 'Firefox')) {
            $browser = 'Firefox';
        } elseif (str_contains($ua, 'Safari') && ! str_contains($ua, 'Chrome')) {
            $browser = 'Safari';
        } elseif (str_contains($ua, 'Edge')) {
            $browser = 'Edge';
        }

        // Deteksi OS
        $os = 'OS Tidak Diketahui';
        if (str_contains($ua, 'Windows')) {
            $os = 'Windows';
        } elseif (str_contains($ua, 'Mac OS')) {
            $os = 'macOS';
        } elseif (str_contains($ua, 'Linux') && ! str_contains($ua, 'Android')) {
            $os = 'Linux';
        } elseif (str_contains($ua, 'Android')) {
            $os = 'Android';
        } elseif (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) {
            $os = 'iOS';
        }

        return "{$browser} di {$os}";
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }
}
