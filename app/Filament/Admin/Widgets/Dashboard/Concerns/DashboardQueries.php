<?php

namespace App\Filament\Admin\Widgets\Dashboard\Concerns;

use App\Models\Agenda;
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
        return $this->meetings()->missingMinutes();
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
        return Document::query()->access()->needsActionBy($this->currentUser())->with('category')->latest('updated_at');
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
     * Status setiap ruangan untuk hari ini: dipakai rapat/kegiatan apa sekarang, dan berikutnya.
     *
     * @return Collection<int, array{location: MeetingLocation, current: Meeting|Agenda|null, next: Meeting|Agenda|null, today: Collection<int, Meeting|Agenda>, todayCount: int}>
     */
    protected function roomStatuses(): Collection
    {
        $locations = $this->locations();

        // Sengaja tanpa scope access(): status ruangan berlaku untuk semua orang,
        // walau detail rapat/kegiatannya sendiri mungkin tidak boleh dibuka.
        $window = [today()->subDay(), today()->endOfDay()];
        $bookings = Meeting::query()
            ->withoutGlobalScope('latest')
            ->where('status', 'scheduled')
            ->whereBetween('date_time', $window)
            ->get()
            ->toBase()
            ->concat(Agenda::query()->where('status', 'scheduled')->where('date_time', '<=', $window[1])->where('end_time', '>=', today())->get())
            ->filter(fn (Meeting|Agenda $m) => $m->effectiveEndTime()->gte(today()))
            ->sortBy('date_time')
            ->values();

        $now = now();

        return $locations->map(function (MeetingLocation $location) use ($bookings, $now) {
            $meetings = $bookings->filter(fn (Meeting|Agenda $m) => $m->meeting_location_id === $location->id
                || ($m->location && mb_strtolower(trim($m->location)) === mb_strtolower(trim($location->name))));

            return [
                'location' => $location,
                'current' => $meetings->first(fn (Meeting|Agenda $m) => $m->date_time->lte($now) && $m->effectiveEndTime()->gt($now)),
                'next' => $meetings->first(fn (Meeting|Agenda $m) => $m->date_time->gt($now)),
                'today' => $meetings->values(),
                'todayCount' => $meetings->filter(fn (Meeting|Agenda $m) => $m->date_time->isToday() || ($m->date_time->lte(today()) && $m->effectiveEndTime()->gte(today())))->count(),
            ];
        });
    }

    /** Kegiatan unit yang boleh dilihat user (scope akses jangkauan pengumuman). */
    protected function agendas(): Builder
    {
        return Agenda::query()->access();
    }

    protected function meetingIsOngoing(Meeting $meeting): bool
    {
        return $meeting->status === 'scheduled'
            && $meeting->date_time->lte(now())
            && $meeting->effectiveEndTime()->gt(now());
    }
}
