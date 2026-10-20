<?php

namespace App\Filament\Admin\Widgets\Dashboard\Concerns;

use App\Models\Document;
use App\Models\Meeting;
use App\Models\MeetingLocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Query bersama untuk widget-widget Beranda, supaya angka di kartu ringkasan
 * dan di panel detail selalu dihitung dengan aturan yang sama.
 */
trait DashboardQueries
{
    protected function currentUser(): User
    {
        /** @var User */
        return Auth::user();
    }

    protected function seesAllCompanies(): bool
    {
        $user = $this->currentUser();

        return $user->hasRole('super_admin') || $user->can('view_all_companies_data');
    }

    /** Rapat yang boleh dilihat user, tanpa urutan default model. */
    protected function meetings(): Builder
    {
        return Meeting::query()->withoutGlobalScope('latest')->access();
    }

    /** Rapat yang sudah selesai, tidak dibatalkan, tapi belum ada notulen/berkas hasil rapat. */
    protected function meetingsWithoutMinutes(): Builder
    {
        return $this->meetings()
            ->where('status', '!=', 'cancelled')
            ->where(fn (Builder $q) => $q
                ->where('status', 'completed')
                ->orWhere(fn (Builder $e) => $e->whereNotNull('end_time')->where('end_time', '<', now()))
                ->orWhere(fn (Builder $e) => $e->whereNull('end_time')
                    ->where('date_time', '<', now()->subHours(Meeting::DEFAULT_DURATION_HOURS))))
            ->where(fn (Builder $q) => $q->whereNull('content')->orWhere('content', '')->orWhere('content', '<p></p>'))
            ->whereNull('file_path')
            ->where(fn (Builder $q) => $q->whereNull('action_items')->orWhereJsonLength('action_items', 0));
    }

    protected function meetingsWithMinutes(): Builder
    {
        return $this->meetings()
            ->where('status', '!=', 'cancelled')
            ->where(fn (Builder $q) => $q
                ->where(fn (Builder $c) => $c->whereNotNull('content')->where('content', '!=', '')->where('content', '!=', '<p></p>'))
                ->orWhereNotNull('file_path')
                ->orWhereJsonLength('action_items', '>', 0));
    }

    /** Dokumen yang menunggu tindakan user ini, sesuai perannya dalam alur review. */
    protected function documentsNeedingAction(): Builder
    {
        $user = $this->currentUser();
        $query = Document::query()->access()->with('category')->latest('updated_at');

        if ($user->hasRole('super_admin')) {
            return $query->whereIn('status', [Document::STATUS_PENDING_KABID, Document::STATUS_PENDING_DIREKTUR]);
        }

        if ($user->hasRole('kabid')) {
            return $query->where('department_id', $user->department_id)->where('status', Document::STATUS_PENDING_KABID);
        }

        if ($user->hasRole('direktur')) {
            return $query->where('company_id', $user->company_id)->where('status', Document::STATUS_PENDING_DIREKTUR);
        }

        return $query->where('user_id', $user->id)->whereIn('status', [
            Document::STATUS_DRAFT,
            Document::STATUS_REJECTED,
            Document::STATUS_PENDING_KABID,
            Document::STATUS_PENDING_DIREKTUR,
        ]);
    }

    /** @return Collection<int, MeetingLocation> */
    protected function locations(): Collection
    {
        $user = $this->currentUser();

        return MeetingLocation::query()
            ->when(! $this->seesAllCompanies(), fn (Builder $q) => $q->where(
                fn (Builder $c) => $c->whereNull('company_id')->orWhere('company_id', $user->company_id)
            ))
            ->orderBy('name')
            ->get();
    }

    /**
     * Status setiap ruangan untuk hari ini: sedang dipakai oleh rapat apa, dan rapat berikutnya.
     *
     * @return Collection<int, array{location: MeetingLocation, current: ?Meeting, next: ?Meeting, today: Collection<int, Meeting>, todayCount: int}>
     */
    protected function roomStatuses(): Collection
    {
        $locations = $this->locations();

        // Sengaja tanpa scope access(): status ruangan berlaku untuk semua orang,
        // walau detail rapatnya sendiri mungkin tidak boleh dibuka.
        $todayMeetings = Meeting::query()
            ->withoutGlobalScope('latest')
            ->where('status', 'scheduled')
            ->whereBetween('date_time', [today()->subDay(), today()->endOfDay()])
            ->orderBy('date_time')
            ->get()
            ->filter(fn (Meeting $m) => $m->effectiveEndTime()->gte(today()));

        $now = now();

        return $locations->map(function (MeetingLocation $location) use ($todayMeetings, $now) {
            $meetings = $todayMeetings->filter(fn (Meeting $m) => $m->meeting_location_id === $location->id
                || ($m->location && mb_strtolower(trim($m->location)) === mb_strtolower(trim($location->name))));

            return [
                'location' => $location,
                'current' => $meetings->first(fn (Meeting $m) => $m->date_time->lte($now) && $m->effectiveEndTime()->gt($now)),
                'next' => $meetings->first(fn (Meeting $m) => $m->date_time->gt($now)),
                'today' => $meetings->values(),
                'todayCount' => $meetings->filter(fn (Meeting $m) => $m->date_time->isToday())->count(),
            ];
        });
    }

    protected function meetingIsOngoing(Meeting $meeting): bool
    {
        return $meeting->status === 'scheduled'
            && $meeting->date_time->lte(now())
            && $meeting->effectiveEndTime()->gt(now());
    }
}
