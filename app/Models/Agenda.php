<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Kegiatan / agenda unit (pelatihan, sosialisasi, bakti sosial, dsb.).
 *
 * - Bisa memakai ruangan rapat; jadwal ruangan dibagi dengan rapat (lihat
 *   Meeting::locationConflict(), yang memeriksa rapat DAN kegiatan).
 * - Opsional punya surat pemberitahuan: nomor dari register Nomor Surat (letter_number_id)
 *   dan isi surat (notice_body), bisa dicetak PDF.
 * - Bisa diumumkan di aplikasi (notifikasi + API integrasi) dan ke grup WhatsApp
 *   (lihat App\Services\AgendaAnnouncer). Jangkauan pengumuman = audience.
 */
class Agenda extends Model
{
    public const AUDIENCE_UNIT = 'unit';

    public const AUDIENCE_COMPANY = 'company';

    public const AUDIENCE_ALL = 'all';

    public const KATEGORI = [
        'pelatihan' => 'Pelatihan / Workshop',
        'sosialisasi' => 'Sosialisasi',
        'apel' => 'Apel / Upacara',
        'olahraga' => 'Olahraga',
        'keagamaan' => 'Keagamaan',
        'bakti_sosial' => 'Bakti Sosial',
        'perayaan' => 'Perayaan / Acara',
        'lainnya' => 'Lainnya',
    ];

    protected $fillable = [
        'title',
        'kategori',
        'description',
        'company_id',
        'department_id',
        'unit_id',
        'date_time',
        'end_time',
        'meeting_location_id',
        'location',
        'status',
        'audience',
        'letter_number_id',
        'notice_body',
        'created_by',
    ];

    protected $casts = [
        'date_time' => 'datetime',
        'end_time' => 'datetime',
        'announced_at' => 'datetime',
        'whatsapp_sent_at' => 'datetime',
    ];

    public static function audienceOptions(): array
    {
        return [
            self::AUDIENCE_UNIT => 'Unit penyelenggara',
            self::AUDIENCE_COMPANY => 'Seluruh perusahaan',
            self::AUDIENCE_ALL => 'Semua perusahaan',
        ];
    }

    // ─── Relasi ──────────────────────────────────────────────────────────────

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

    public function meetingLocation(): BelongsTo
    {
        return $this->belongsTo(MeetingLocation::class);
    }

    public function letterNumber(): BelongsTo
    {
        return $this->belongsTo(LetterNumber::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Tampilan ────────────────────────────────────────────────────────────

    /** Nama tempat: ruangan terdaftar, atau lokasi teks bebas. */
    public function placeName(): string
    {
        return $this->meetingLocation?->name ?: ($this->location ?: 'Lokasi belum ditentukan');
    }

    public function organizerName(): string
    {
        return $this->unit?->name ?? $this->department?->name ?? $this->company?->name ?? '—';
    }

    public function scheduleLabel(): string
    {
        $start = $this->date_time->locale('id');
        $end = $this->effectiveEndTime()->locale('id');

        return $start->isSameDay($end)
            ? $start->isoFormat('dddd, D MMMM YYYY').', '.$start->format('H.i').'–'.$end->format('H.i').' WITA'
            : $start->isoFormat('D MMMM YYYY, HH.mm').' – '.$end->isoFormat('D MMMM YYYY, HH.mm').' WITA';
    }

    /** Label pemesan ruangan untuk pesan bentrok jadwal. */
    public function bookingLabel(): string
    {
        return "kegiatan \"{$this->title}\"";
    }

    public function isAnnounced(): bool
    {
        return $this->announced_at !== null;
    }

    // ─── Akses ───────────────────────────────────────────────────────────────

    /**
     * Kegiatan yang boleh dilihat user: admin global melihat semua; selain itu kegiatan
     * berjangkauan "semua perusahaan", kegiatan perusahaannya, kegiatan unitnya, dan
     * kegiatan yang ia buat.
     */
    public function scopeAccess(Builder $query): Builder
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user || $user->hasRole('super_admin') || $user->can('view_all_companies_data')) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q
            ->where('audience', self::AUDIENCE_ALL)
            ->orWhere('created_by', $user->id)
            ->orWhere(fn (Builder $c) => $c->where('company_id', $user->company_id)
                ->where(fn (Builder $a) => $a
                    ->where('audience', self::AUDIENCE_COMPANY)
                    ->orWhere('unit_id', $user->unit_id)
                    ->when($user->can('view_own_company_data'), fn (Builder $o) => $o->orWhereRaw('1 = 1')))));
    }

    // ─── Jadwal ruangan (dibagi dengan rapat) ────────────────────────────────

    public function effectiveEndTime(): Carbon
    {
        return $this->end_time
            ? $this->end_time->copy()
            : $this->date_time->copy()->addHours(Meeting::DEFAULT_DURATION_HOURS);
    }

    /** Sama dengan Meeting::scopeInTimeWindow(): saring kasar di DB, presisi di PHP. */
    public function scopeInTimeWindow(Builder $query, Carbon $start, ?Carbon $end, ?int $excludeAgendaId = null): Builder
    {
        $end = $end ?: $start->copy()->addHours(Meeting::DEFAULT_DURATION_HOURS);

        return $query
            ->where('status', '!=', 'cancelled')
            ->when($excludeAgendaId, fn ($q) => $q->where('id', '!=', $excludeAgendaId))
            ->where('date_time', '<', $end)
            ->where('date_time', '>=', $start->copy()->subDays(7));
    }

    /** Kegiatan yang memakai ruangan/lokasi ini dan bertumpang tindih dengan [$start, $end]. */
    public static function locationConflict(?int $locationId, ?string $locationName, Carbon $start, ?Carbon $end, ?int $excludeAgendaId = null): ?self
    {
        $locationName = filled($locationName) ? trim($locationName) : null;

        if (! $locationId && ! $locationName) {
            return null;
        }

        return static::query()
            ->inTimeWindow($start, $end, $excludeAgendaId)
            ->where(function ($q) use ($locationId, $locationName) {
                $q->when($locationId, fn ($qq) => $qq->orWhere('meeting_location_id', $locationId));
                $q->when($locationName, fn ($qq) => $qq->orWhere('location', $locationName));
            })
            ->with('creator')
            ->get()
            ->first(fn (self $a) => $a->effectiveEndTime()->gt($start));
    }

    /** @return Collection<int, self> kegiatan yang memakai ruangan pada rentang [$start, $end]. */
    public static function bookedIn(Carbon $start, ?Carbon $end, ?int $excludeAgendaId = null): Collection
    {
        return static::query()
            ->inTimeWindow($start, $end, $excludeAgendaId)
            ->get()
            ->filter(fn (self $a) => $a->effectiveEndTime()->gt($start))
            ->values();
    }
}
