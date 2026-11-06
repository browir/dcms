<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TwoFactorLog extends Model
{
    public const UPDATED_AT = null; // Hanya created_at

    protected $fillable = [
        'user_id',
        'event',
        'ip_address',
        'user_agent',
        'meta',
        'actor_id',
    ];

    protected $casts = [
        'meta'       => 'array',
        'created_at' => 'datetime',
    ];

    // Konstanta event
    public const EVENT_CODE_SENT      = 'code_sent';
    public const EVENT_VERIFIED       = 'verified';
    public const EVENT_FAILED         = 'failed';
    public const EVENT_LOCKED         = 'locked';
    public const EVENT_DEVICE_TRUSTED = 'device_trusted';
    public const EVENT_DEVICE_REVOKED = 'device_revoked';
    public const EVENT_2FA_ENABLED    = '2fa_enabled';
    public const EVENT_2FA_DISABLED   = '2fa_disabled';
    public const EVENT_ADMIN_RESET    = 'admin_reset';

    // ── Relations ─────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
