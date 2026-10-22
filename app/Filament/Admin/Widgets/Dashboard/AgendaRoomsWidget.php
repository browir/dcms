<?php

namespace App\Filament\Admin\Widgets\Dashboard;

use App\Filament\Admin\Resources\Agendas\AgendaResource;
use App\Filament\Admin\Resources\MeetingLocationResource;
use App\Filament\Admin\Resources\Meetings\MeetingResource;
use App\Filament\Admin\Widgets\Dashboard\Concerns\DashboardQueries;
use App\Models\Agenda;
use App\Models\Meeting;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Agenda rapat 7 hari ke depan berdampingan dengan status pemakaian ruangan hari ini.
 */
class AgendaRoomsWidget extends Widget
{
    use DashboardQueries;

    /** Rentang jam yang ditampilkan pada timeline ruangan. */
    private const DAY_START = 7;

    private const DAY_END = 19;

    protected string $view = 'filament.widgets.dashboard.agenda-rooms';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 20;

    protected static bool $isDiscovered = false;

    public static function canView(): bool
    {
        return Auth::check();
    }

    protected function getPollingInterval(): ?string
    {
        return '60s';
    }

    protected function getViewData(): array
    {
        $user = $this->currentUser();

        $range = [today(), today()->addDays(7)->endOfDay()];

        $meetings = $this->meetings()
            ->with(['meetingLocation:id,name'])
            ->withCount('participants')
            ->where('status', '!=', 'cancelled')
            ->whereBetween('date_time', $range)
            ->orderBy('date_time')
            ->limit(12)
            ->get()
            ->map(fn (Meeting $meeting) => [
                'kind' => 'rapat',
                'title' => $meeting->title,
                'start' => $meeting->date_time,
                'time' => $meeting->date_time->format('H:i').'–'.$meeting->effectiveEndTime()->format('H:i'),
                'location' => $meeting->meetingLocation?->name ?: ($meeting->location ?: 'Lokasi belum ditentukan'),
                'meta' => $meeting->participants_count.' peserta',
                'state' => $this->bookingState($meeting),
                'url' => match (true) {
                    ! $this->opens(MeetingResource::class) => null,
                    $user->can('update', $meeting) => route('filament.admin.resources.meetings.edit', $meeting),
                    default => route('filament.admin.resources.meetings.view', $meeting),
                },
            ]);

        // Kegiatan unit (pelatihan, sosialisasi, dsb.) tampil bersama rapat di agenda.
        $activities = $this->agendas()
            ->with(['meetingLocation:id,name', 'unit:id,name'])
            ->where('status', '!=', 'cancelled')
            ->where('date_time', '<=', $range[1])
            ->where('end_time', '>=', $range[0])
            ->orderBy('date_time')
            ->limit(12)
            ->get()
            ->map(fn (Agenda $activity) => [
                'kind' => 'kegiatan',
                'title' => $activity->title,
                'start' => $activity->date_time->lt($range[0]) ? $range[0]->copy() : $activity->date_time,
                'time' => $activity->date_time->format('H:i').'–'.$activity->effectiveEndTime()->format('H:i'),
                'location' => $activity->placeName(),
                'meta' => $activity->unit?->name ?? 'Kegiatan unit',
                'state' => $this->bookingState($activity),
                'url' => $this->opens(AgendaResource::class) ? AgendaResource::getUrl('view', ['record' => $activity]) : null,
            ]);

        $agenda = $meetings->concat($activities)
            ->sortBy('start')
            ->take(14)
            ->groupBy(fn (array $item) => $this->dayLabel($item['start']->copy()->startOfDay()));

        $span = (self::DAY_END - self::DAY_START) * 60;
        $dayStart = today()->setTime(self::DAY_START, 0);

        $rooms = $this->roomStatuses()->map(function (array $room) use ($span, $dayStart) {
            $blocks = $room['today']
                ->map(function (Meeting|Agenda $m) use ($span, $dayStart) {
                    $start = max(0, $dayStart->diffInMinutes($m->date_time, false));
                    $end = min($span, $dayStart->diffInMinutes($m->effectiveEndTime(), false));

                    if ($end <= 0 || $start >= $span) {
                        return null;
                    }

                    return [
                        'left' => round($start / $span * 100, 2),
                        'width' => max(1.5, round(($end - $start) / $span * 100, 2)),
                        'label' => $m->date_time->format('H:i').'–'.$m->effectiveEndTime()->format('H:i').' · '.$m->title,
                        'past' => $m->effectiveEndTime()->lt(now()),
                    ];
                })
                ->filter()
                ->values();

            return [
                'name' => $room['location']->name,
                'capacity' => $room['location']->capacity,
                'address' => $room['location']->address,
                'inUse' => $room['current'] !== null,
                'status' => match (true) {
                    $room['current'] !== null => 'Dipakai: '.$room['current']->title.' s/d '.$room['current']->effectiveEndTime()->format('H:i'),
                    $room['next'] !== null && $room['next']->date_time->isToday() => 'Berikutnya '.$room['next']->date_time->format('H:i').' · '.$room['next']->title,
                    default => 'Kosong sepanjang sisa hari ini',
                },
                'todayCount' => $room['todayCount'],
                'blocks' => $blocks,
            ];
        });

        $nowMinutes = $dayStart->diffInMinutes(now(), false);

        return [
            'agenda' => $agenda,
            'rooms' => $rooms,
            'nowPosition' => $nowMinutes >= 0 && $nowMinutes <= $span ? round($nowMinutes / $span * 100, 2) : null,
            'hourMarks' => range(self::DAY_START, self::DAY_END, 3),
            'hourSpan' => self::DAY_END - self::DAY_START,
            'dayStartHour' => self::DAY_START,
            'roomsUrl' => $this->opens(MeetingLocationResource::class) ? MeetingLocationResource::getUrl() : null,
            'activitiesUrl' => $this->opens(AgendaResource::class) ? AgendaResource::getUrl() : null,
            'meetingsUrl' => $this->opens(MeetingResource::class) ? MeetingResource::getUrl() : null,
        ];
    }

    private function bookingState(Meeting|Agenda $booking): string
    {
        return match (true) {
            $booking->status === 'scheduled' && $booking->date_time->lte(now()) && $booking->effectiveEndTime()->gt(now()) => 'ongoing',
            $booking->status === 'completed' || $booking->effectiveEndTime()->lt(now()) => 'done',
            default => 'scheduled',
        };
    }

    private function dayLabel(Carbon $day): string
    {
        return match (true) {
            $day->isToday() => 'Hari ini',
            $day->isTomorrow() => 'Besok',
            default => $day->locale('id')->isoFormat('dddd, D MMM'),
        };
    }
}
