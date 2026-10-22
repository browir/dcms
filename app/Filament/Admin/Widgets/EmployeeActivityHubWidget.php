<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Meetings\MeetingResource;
use App\Models\Document;
use App\Models\Meeting;
use App\Models\User;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class EmployeeActivityHubWidget extends Widget
{
    // Tampil-tidaknya widget diatur per role di Peran & Hak Akses → Widget (View:<NamaWidget>).
    use HasWidgetShield;

    protected string $view = 'filament.widgets.employee-activity-hub';

    /** Nama widget di Peran & Hak Akses → Widget. */
    public function getHeading(): string
    {
        return 'Beranda: Sapaan & Agenda Terdekat';
    }

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 5;

    protected function getViewData(): array
    {
        /** @var User $user */
        $user = Auth::user();

        $todayMeetingsQuery = Meeting::query()
            ->access()
            ->whereDate('date_time', today())
            ->where('status', '!=', 'cancelled')
            ->orderBy('date_time');

        $todayMeetingsCount = $todayMeetingsQuery->count();

        $upcomingMeetingsQuery = Meeting::query()
            ->access()
            ->where('date_time', '>=', now())
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->orderBy('date_time');

        $nextMeeting = ($meeting = (clone $upcomingMeetingsQuery)->first())
            ? $this->transformMeeting($meeting)
            : null;

        $documentsNeedActionCount = $this->getDocumentsNeedActionQuery($user)->count();

        $unreadNotificationsCount = (int) $user->unreadNotifications()->count();

        $todayLoad = $todayMeetingsCount + $documentsNeedActionCount;
        $subtitle = $todayLoad > 0
            ? "Ada {$todayLoad} dokumen & agenda yang menunggu tindakan Anda hari ini. Mari kita selesaikan!"
            : 'Semua pekerjaan Anda hari ini sudah terkendali dengan baik. Selamat melanjutkan aktivitas!';

        return [
            'greeting' => $this->getGreeting(),
            'userName' => $user->name,
            'subtitle' => $subtitle,
            'organizationPath' => $user->organization_path ?: 'Pegawai aktif',
            'nextMeeting' => $nextMeeting,
            'todayMeetingsCount' => $todayMeetingsCount,
            'documentsNeedActionCount' => $documentsNeedActionCount,
            'unreadNotificationsCount' => $unreadNotificationsCount,
        ];
    }

    private function getGreeting(): string
    {
        $hour = (int) now()->format('H');

        return match (true) {
            $hour < 11 => 'Selamat pagi',
            $hour < 15 => 'Selamat siang',
            $hour < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };
    }

    private function getDocumentsNeedActionQuery(User $user): Builder
    {
        $query = Document::query()
            ->access()
            ->latest();

        if ($user->hasRole('super_admin')) {
            return $query->whereIn('status', [
                Document::STATUS_PENDING_KABID,
                Document::STATUS_PENDING_DIREKTUR,
            ]);
        }

        if ($user->hasRole('kabid')) {
            return $query
                ->where('department_id', $user->department_id)
                ->where('status', Document::STATUS_PENDING_KABID);
        }

        if ($user->hasRole('direktur')) {
            return $query
                ->where('company_id', $user->company_id)
                ->where('status', Document::STATUS_PENDING_DIREKTUR);
        }

        return $query
            ->where('user_id', $user->id)
            ->whereIn('status', [
                Document::STATUS_DRAFT,
                Document::STATUS_REJECTED,
                Document::STATUS_PENDING_KABID,
                Document::STATUS_PENDING_DIREKTUR,
            ]);
    }

    private function transformMeeting(Meeting $meeting): array
    {
        /** @var Carbon $dateTime */
        $dateTime = $meeting->date_time;

        return [
            'title' => $meeting->title,
            // Tanpa akses ke modul Rapat: tampil sebagai info saja (tanpa link ke halaman 403).
            'url' => MeetingResource::canAccess() ? MeetingResource::getUrl('view', ['record' => $meeting]) : null,
            'when' => $this->formatMeetingWhen($dateTime),
            'time' => $dateTime->format('H:i'),
            'location' => $meeting->location ?: 'Lokasi belum diisi',
            'badge' => match ($meeting->status) {
                'scheduled' => 'Terjadwal',
                'completed' => 'Selesai',
                'cancelled' => 'Batal',
                default => ucfirst((string) $meeting->status),
            },
            'badgeColor' => match ($meeting->status) {
                'scheduled' => 'info',
                'completed' => 'gray',
                default => 'gray',
            },
        ];
    }

    private function formatMeetingWhen(Carbon $dateTime): string
    {
        if ($dateTime->isToday()) {
            return 'Hari ini, '.$dateTime->format('H:i');
        }

        if ($dateTime->isTomorrow()) {
            return 'Besok, '.$dateTime->format('H:i');
        }

        return $dateTime->format('d M Y, H:i');
    }
}
