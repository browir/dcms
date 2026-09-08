<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class Meeting extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'doc_number', 'agenda', 'content', 'file_path', 'attachments', 'date_time', 'end_time', 'location', 'meeting_location_id', 'status', 'company_id', 'department_id', 'unit_id', 'created_by', 'notulis_id', 'reminder_sent_at'];

    protected $casts = [
        'date_time' => 'datetime',
        'end_time' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'attachments' => 'array',
    ];

    public function getMeetingDateAttribute()
    {
        return $this->date_time;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function notulis(): BelongsTo
    {
        return $this->belongsTo(User::class, 'notulis_id');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'meeting_participants')->withPivot('attendance')->withTimestamps();
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function meetingLocation(): BelongsTo
    {
        return $this->belongsTo(MeetingLocation::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function getParticipantNamesAttribute()
    {
        return $this->participants->pluck('name')->implode(', ');
    }

    /**
     * Scope a query to only include meetings the user has access to.
     */
    public function scopeAccess(Builder $query)
    {
        $user = auth()->user();

        if (! $user) {
            return $query;
        }

        // 1. All Companies Bypass (Super Admin OR Global Permission)
        if ($user->hasRole('super_admin') || $user->can('view_all_companies_data')) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            // 2. Participant always has access (CRITICAL for cross-company invitations)
            $q->whereHas('participants', function ($p) use ($user) {
                $p->where('users.id', $user->id);
            })
                // 3. Creator always has access
                ->orWhere('created_by', $user->id)
                // 4. Notulis always has access
                ->orWhere('notulis_id', $user->id)
                // 4. Role/Permission based organizational access
                ->orWhere(function ($q2) use ($user) {
                    if ($user->can('view_own_company_data') || $user->hasRole('direktur')) {
                        // Direktur or users with 'view_own_company_data' see everything in their company
                        return $q2->where('company_id', $user->company_id);
                    } elseif ($user->hasRole('manager')) {
                        // Manager sees everything in their department
                        return $q2->where('department_id', $user->department_id);
                    } else {
                        // Staff only sees meetings in their unit
                        if ($user->unit_id) {
                            return $q2->where('unit_id', $user->unit_id);
                        }

                        // If no unit/not a participant/not a creator, no access
                        return $q2->whereRaw('1=0');
                    }
                });
        });
    }

    /**
     * Scope a query to only include meetings from the user's own company.
     */
    public function scopeOwnCompany(Builder $query)
    {
        return $query->where('company_id', auth()->user()->company_id);
    }

    /**
     * Durasi default (jam) yang dipakai untuk menghitung "jam berakhir"
     * ketika sebuah rapat tidak mengisi end_time.
     */
    public const DEFAULT_DURATION_HOURS = 2;

    /**
     * Jam berakhir efektif rapat ini (end_time, atau date_time + durasi default).
     */
    public function effectiveEndTime(): Carbon
    {
        return $this->end_time
            ? $this->end_time->copy()
            : $this->date_time->copy()->addHours(self::DEFAULT_DURATION_HOURS);
    }

    /**
     * Scope: rapat yang rentang waktunya berpotensi bentrok dengan [$start, $end].
     * Filter kasar di level DB (batas bawah date_time dibatasi 1 hari sebelum $start);
     * pengecekan tumpang tindih presisi dilakukan di PHP oleh pemanggil.
     */
    public function scopeInTimeWindow(Builder $query, Carbon $start, ?Carbon $end, ?int $excludeMeetingId = null): Builder
    {
        $end = $end ?: $start->copy()->addHours(self::DEFAULT_DURATION_HOURS);

        return $query
            ->withoutGlobalScope('latest')
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->when($excludeMeetingId, fn ($q) => $q->where('id', '!=', $excludeMeetingId))
            ->whereNotNull('date_time')
            ->where('date_time', '<', $end)
            ->where('date_time', '>=', $start->copy()->subDay());
    }

    /**
     * Rapat lain yang benar-benar bertumpang tindih dengan [$start, $end]
     * pada lokasi tertentu (via FK meeting_location_id atau nama lokasi teks bebas).
     */
    public static function locationConflict(
        ?int $locationId,
        ?string $locationName,
        Carbon $start,
        ?Carbon $end,
        ?int $excludeMeetingId = null
    ): ?self {
        $locationName = filled($locationName) ? trim($locationName) : null;

        if (! $locationId && ! $locationName) {
            return null;
        }

        return static::query()
            ->inTimeWindow($start, $end, $excludeMeetingId)
            ->where(function ($q) use ($locationId, $locationName) {
                $q->when($locationId, fn ($qq) => $qq->orWhere('meeting_location_id', $locationId));
                $q->when($locationName, fn ($qq) => $qq->orWhere('location', $locationName));
            })
            ->with('creator')
            ->get()
            ->first(fn (self $m) => $m->effectiveEndTime()->gt($start));
    }

    /**
     * Kumpulan "kunci lokasi" (id FK + nama teks bebas, huruf kecil) yang sudah
     * dipesan pada rentang [$start, $end]. Dipakai form untuk mengunci opsi.
     *
     * @return array{ids: int[], names: string[], meetings: Collection}
     */
    public static function bookedLocationKeys(Carbon $start, ?Carbon $end, ?int $excludeMeetingId = null): array
    {
        $meetings = static::query()
            ->inTimeWindow($start, $end, $excludeMeetingId)
            ->get()
            ->filter(fn (self $m) => $m->effectiveEndTime()->gt($start))
            ->values();

        return [
            'ids' => $meetings->pluck('meeting_location_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all(),
            'names' => $meetings->pluck('location')->filter()->map(fn ($n) => mb_strtolower(trim($n)))->unique()->values()->all(),
            'meetings' => $meetings,
        ];
    }

    /**
     * Apply a global scope to order meetings by latest date_time.
     */
    protected static function booted()
    {
        static::addGlobalScope('latest', function (Builder $builder) {
            $builder->orderBy('date_time', 'desc');
        });
    }
}
