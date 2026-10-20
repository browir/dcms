<?php

namespace App\Filament\Admin\Widgets\Dashboard;

use App\Filament\Admin\Resources\MeetingLocationResource;
use App\Filament\Admin\Widgets\Dashboard\Concerns\DashboardQueries;
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

        $agenda = $this->meetings()
            ->with(['meetingLocation:id,name'])
            ->withCount('participants')
            ->where('status', '!=', 'cancelled')
            ->whereBetween('date_time', [today(), today()->addDays(7)->endOfDay()])
            ->orderBy('date_time')
            ->limit(12)
            ->get()
            ->map(fn (Meeting $meeting) => [
                'title' => $meeting->title,
                'time' => $meeting->date_time->format('H:i').'–'.$meeting->effectiveEndTime()->format('H:i'),
                'location' => $meeting->meetingLocation?->name ?: ($meeting->location ?: 'Lokasi belum ditentukan'),
                'participants' => $meeting->participants_count,
                'state' => match (true) {
                    $this->meetingIsOngoing($meeting) => 'ongoing',
                    $meeting->status === 'completed' || $meeting->effectiveEndTime()->lt(now()) => 'done',
                    default => 'scheduled',
                },
                'url' => $user->can('update', $meeting)
                    ? route('filament.admin.resources.meetings.edit', $meeting)
                    : route('filament.admin.resources.meetings.view', $meeting),
                'day' => $meeting->date_time->copy()->startOfDay(),
            ])
            ->groupBy(fn (array $item) => $this->dayLabel($item['day']));

        $span = (self::DAY_END - self::DAY_START) * 60;
        $dayStart = today()->setTime(self::DAY_START, 0);

        $rooms = $this->roomStatuses()->map(function (array $room) use ($span, $dayStart) {
            $blocks = $room['today']
                ->map(function (Meeting $m) use ($span, $dayStart) {
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
            'roomsUrl' => MeetingLocationResource::canViewAny() ? MeetingLocationResource::getUrl() : null,
        ];
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
