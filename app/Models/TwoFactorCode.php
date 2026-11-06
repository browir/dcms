<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class TwoFactorCode extends Model
{
    protected $fillable = [
        'user_id',
        'code_hash',
        'purpose',
        'attempts',
        'expires_at',
        'used_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
        'attempts'   => 'integer',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function isExhausted(): bool
    {
        return $this->attempts >= config('two_factor.max_attempts', 5);
    }

    public function isValid(): bool
    {
        return ! $this->isExpired() && ! $this->isUsed() && ! $this->isExhausted();
    }

    /**
     * Periksa kode tanpa membocorkan alasan kegagalan ke luar.
     * Return true jika cocok dan kode masih valid.
     */
    public function verify(string $plainCode): bool
    {
        if (! $this->isValid()) {
            return false;
        }

        if (! Hash::check($plainCode, $this->code_hash)) {
            $this->increment('attempts');
            return false;
        }

        $this->update(['used_at' => now()]);
        return true;
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->where('attempts', '<', config('two_factor.max_attempts', 5));
    }

    public function scopeForPurpose($query, string $purpose)
    {
        return $query->where('purpose', $purpose);
    }
}
