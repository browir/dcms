<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Meeting;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class MeetingCardsWidget extends Widget
{

    protected string $view = 'filament.widgets.meeting-cards-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    protected function getPollingInterval(): ?string
    {
        return '60s';
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $today = Carbon::today();

        // ── Jadwal Rapat Hari Ini ───────────────────────────────────────────
        $todayMeetings = Meeting::query()
            ->access()
            ->whereDate('date_time', $today)
            ->where('status', '!=', 'cancelled')
            ->orderBy('date_time', 'asc')
            ->get();

        // ── Undangan Rapat Mendatang ────────────────────────────────────────
        $invitedMeetings = Meeting::query()
            ->where(function ($query) use ($user) {
                $query->whereHas('participants', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                })->orWhere('created_by', $user->id);
            })
            ->where('date_time', '>=', now())
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->orderBy('date_time', 'asc')
            ->get();

        return [
            'todayMeetings'   => $todayMeetings,
            'invitedMeetings' => $invitedMeetings,
        ];
    }
}
