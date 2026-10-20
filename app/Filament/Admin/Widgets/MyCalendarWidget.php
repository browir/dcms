<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Meeting;
use Filament\Notifications\Notification;
use Guava\Calendar\Enums\CalendarViewType;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Guava\Calendar\ValueObjects\DateClickInfo;
use Guava\Calendar\ValueObjects\EventClickInfo;
use Guava\Calendar\ValueObjects\EventDropInfo;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class MyCalendarWidget extends CalendarWidget
{
    protected \Illuminate\Support\HtmlString|string|bool|null $heading = 'Kalender Rapat';

    protected static ?int $sort = 50;

    protected int|string|array $columnSpan = 'full';

    protected CalendarViewType $calendarView = CalendarViewType::DayGridMonth;

    protected bool $eventClickEnabled = true;

    // protected bool $eventDragEnabled = true;
    protected bool $dateClickEnabled = true;

    protected ?string $locale = 'id';

    public function getOptions(): array
    {
        return array_merge(parent::getOptions(), [
            'buttonText' => [
                'today' => 'Hari Ini',
            ],
            'eventTimeFormat' => ['hour' => '2-digit', 'minute' => '2-digit', 'hour12' => false],
        ]);
    }

    protected function getEvents(FetchInfo $info): Collection|array
    {
        return Meeting::query()
            ->access()
            ->whereNotIn('status', ['cancelled', 'completed']) // exclude cancelled & selesai
            ->get()
            ->map(function ($meeting) {
                // START = kolom datetime
                $start = $meeting->date_time;

                // END optional: kalau tidak ada, pakai +1 jam dari start
                $end = $meeting->date_time;

                return CalendarEvent::make()
                    ->model(Meeting::class) // ⬅ hanya kirim nama class!
                    ->key($meeting->id)     // ⬅ primary key supaya bisa di-resolve
                    ->title($meeting->title)
                    ->start($start)
                    ->end($end);
            });
    }

    protected function onEventClick(EventClickInfo $info, Model $event, ?string $action = null): void
    {
        $meeting = Meeting::find($event->getKey());

        if (! $meeting) {
            return;
        }

        $user = auth()->user();

        // Check if user has permission to view the meeting (Participant, Creator, or Super Admin)
        if (! $user->can('view', $meeting)) {
            Notification::make()
                ->title('Anda bukan partisipan rapat ini')
                ->danger()
                ->send();

            return;
        }

        // Redirect based on update permission
        if ($user->can('update', $meeting)) {
            $this->redirect(route('filament.admin.resources.meetings.edit', $meeting));
        } else {
            $this->redirect(route('filament.admin.resources.meetings.view', $meeting));
        }
    }

    protected function onDateClick(DateClickInfo $info): void
    {
        // Handle date click event - redirect to create meeting form
        $this->redirect(route('filament.admin.resources.meetings.create', [
            'date_time' => $info->date->toDateTimeString(),
        ]));
    }

    protected function onEventDrop(EventDropInfo $info, Model $event): bool
    {
        // Ambil model aslinya dari key
        $meeting = Meeting::find($event->getKey());

        if (! $meeting) {
            return false;
        }
        // Guava sudah menyediakan date baru di $info->start
        $newDate = $info->event->getStart();
        // dd($newDate);

        // Update model
        $meeting->update([
            'date_time' => $newDate,
        ]);

        return true;
    }
}
