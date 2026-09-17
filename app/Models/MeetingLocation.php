<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeetingLocation extends Model
{
    protected $fillable = ['name', 'address', 'capacity', 'company_id'];

    // ─── Relations ───────────────────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class);
    }

    // ─── Occupancy Logic ─────────────────────────────────────────────────────

    /**
     * Ruangan dianggap sedang dipakai jika ada rapat berstatus 'scheduled' yang:
     * - Terhubung via FK (meeting_location_id) ATAU nama lokasi cocok (location = name)
     * - Sudah mulai (date_time <= now + 30 menit)
     * - Belum selesai: end_time > now, atau (jika end_time null) date_time >= now - 4 jam
     */
    public function isCurrentlyInUse(): bool
    {
        return \App\Models\Meeting::query()
            ->where('status', 'scheduled')
            ->where('date_time', '<=', now()->addMinutes(30))
            ->where(function ($q) {
                // Belum selesai: end_time di masa depan, atau end_time null dan baru mulai
                $q->where(function ($q2) {
                    $q2->whereNotNull('end_time')
                        ->where('end_time', '>', now());
                })->orWhere(function ($q2) {
                    $q2->whereNull('end_time')
                        ->where('date_time', '>=', now()->subHours(4));
                });
            })
            ->where(function ($q) {
                $q->where('meeting_location_id', $this->id)
                    ->orWhere('location', $this->name);
            })
            ->exists();
    }

    /**
     * Ambil meeting yang sedang berlangsung di lokasi ini.
     */
    public function getCurrentMeeting(): ?Meeting
    {
        return \App\Models\Meeting::query()
            ->where('status', 'scheduled')
            ->where('date_time', '<=', now()->addMinutes(30))
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNotNull('end_time')
                        ->where('end_time', '>', now());
                })->orWhere(function ($q2) {
                    $q2->whereNull('end_time')
                        ->where('date_time', '>=', now()->subHours(4));
                });
            })
            ->where(function ($q) {
                $q->where('meeting_location_id', $this->id)
                    ->orWhere('location', $this->name);
            })
            ->with('creator')
            ->latest('date_time')
            ->first();
    }

    /**
     * Ambil rapat (jika ada) yang membuat lokasi ini terpakai pada rentang waktu tertentu.
     * Menggunakan logika bentrok yang sama dengan validasi form pemesanan rapat.
     */
    public function meetingAt(Carbon $start, ?Carbon $end = null): ?Meeting
    {
        return \App\Models\Meeting::locationConflict($this->id, $this->name, $start, $end);
    }

    /**
     * Cek apakah lokasi ini sedang/akan terpakai pada rentang waktu tertentu.
     */
    public function isInUseAt(Carbon $start, ?Carbon $end = null): bool
    {
        return $this->meetingAt($start, $end) !== null;
    }

    /**
     * Scope: hanya lokasi yang sedang tersedia.
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereDoesntHave('meetings', function (Builder $m) {
                $m->where('status', 'scheduled')
                    ->where('date_time', '<=', now()->addMinutes(30))
                    ->where('date_time', '>=', now()->subHours(4));
            })->whereNotIn('name', function ($sub) {
                $sub->select('location')
                    ->from('meetings')
                    ->where('status', 'scheduled')
                    ->where('date_time', '<=', now()->addMinutes(30))
                    ->where('date_time', '>=', now()->subHours(4))
                    ->whereNotNull('location');
            });
        });
    }

    /**
     * Scope: hanya lokasi yang sedang dipakai.
     */
    public function scopeOccupied(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereHas('meetings', function (Builder $m) {
                $m->where('status', 'scheduled')
                    ->where('date_time', '<=', now()->addMinutes(30))
                    ->where('date_time', '>=', now()->subHours(4));
            })->orWhereIn('name', function ($sub) {
                $sub->select('location')
                    ->from('meetings')
                    ->where('status', 'scheduled')
                    ->where('date_time', '<=', now()->addMinutes(30))
                    ->where('date_time', '>=', now()->subHours(4))
                    ->whereNotNull('location');
            });
        });
    }
}
