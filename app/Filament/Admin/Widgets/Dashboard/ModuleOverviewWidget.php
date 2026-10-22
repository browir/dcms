<?php

namespace App\Filament\Admin\Widgets\Dashboard;

use App\Filament\Admin\Pages\ComplianceHub;
use App\Filament\Admin\Pages\ReminderHub;
use App\Filament\Admin\Resources\Agendas\AgendaResource;
use App\Filament\Admin\Resources\Documents\DocumentResource;
use App\Filament\Admin\Resources\LetterNumbers\LetterNumberResource;
use App\Filament\Admin\Resources\Meetings\MeetingResource;
use App\Filament\Admin\Widgets\Dashboard\Concerns\DashboardQueries;
use App\Models\Document;
use App\Models\LetterNumber;
use App\Models\Meeting;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

/**
 * Kartu ringkasan lima modul DCMS: penjadwalan rapat, notulensi,
 * dokumen resmi, penomoran surat, dan ruangan rapat.
 */
class ModuleOverviewWidget extends Widget
{
    use DashboardQueries;

    protected string $view = 'filament.widgets.dashboard.module-overview';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 10;

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

        $weekMeetings = $this->meetings()
            ->where('status', '!=', 'cancelled')
            ->whereBetween('date_time', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();
        $todayMeetings = $this->meetings()
            ->where('status', '!=', 'cancelled')
            ->whereDate('date_time', today())
            ->count();
        $weekActivities = $this->agendas()
            ->where('status', '!=', 'cancelled')
            ->where('date_time', '<=', now()->endOfWeek())
            ->where('end_time', '>=', now()->startOfWeek())
            ->count();

        $withoutMinutes = $this->meetingsWithoutMinutes()->count();
        $minutesThisMonth = $this->meetingsWithMinutes()
            ->whereBetween('date_time', [now()->startOfMonth(), now()])
            ->count();

        $activeDocuments = Document::query()->access()->where('status', Document::STATUS_APPROVED)->count();
        $pendingReview = Document::query()->access()
            ->whereIn('status', [Document::STATUS_PENDING_KABID, Document::STATUS_PENDING_DIREKTUR])
            ->count();

        $numbersThisYear = LetterNumber::query()->access()->whereYear('tanggal_surat', now()->year)->count();
        $numbersWithoutDocument = LetterNumber::query()->access()->available()->count();

        $rooms = $this->roomStatuses();
        $roomsInUse = $rooms->whereNotNull('current')->count();

        $modules = [
            [
                'key' => 'meeting',
                'label' => 'Rapat & Kegiatan',
                'value' => $weekMeetings + $weekActivities,
                'unit' => "agenda minggu ini ({$weekActivities} kegiatan)",
                'detail' => $todayMeetings > 0 ? "{$todayMeetings} rapat berlangsung hari ini" : 'Tidak ada rapat hari ini',
                'url' => match (true) {
                    $this->opens(MeetingResource::class) => MeetingResource::getUrl(),
                    $this->opens(AgendaResource::class) => AgendaResource::getUrl(),
                    default => null,
                },
                'icon' => 'heroicon-o-calendar-days',
                'alert' => false,
            ],
            [
                'key' => 'minutes',
                'label' => 'Notulensi Rapat',
                'value' => $withoutMinutes,
                'unit' => 'rapat belum bernotulen',
                'detail' => "{$minutesThisMonth} notulen tercatat bulan ini",
                'url' => '#dsh-followup',
                'icon' => 'heroicon-o-pencil-square',
                'alert' => $withoutMinutes > 0,
            ],
            [
                'key' => 'document',
                'label' => 'Dokumen Resmi',
                'value' => $activeDocuments,
                'unit' => 'dokumen berlaku',
                'detail' => $pendingReview > 0 ? "{$pendingReview} menunggu persetujuan" : 'Tidak ada antrean persetujuan',
                'url' => $this->opens(DocumentResource::class) ? DocumentResource::getUrl() : null,
                'icon' => 'heroicon-o-document-text',
                'alert' => $pendingReview > 0,
            ],
            [
                'key' => 'numbering',
                'label' => 'Penomoran Surat',
                'value' => $numbersThisYear,
                'unit' => 'nomor terbit '.now()->year,
                'detail' => $numbersWithoutDocument > 0 ? "{$numbersWithoutDocument} nomor belum diisi dokumen" : 'Semua nomor sudah berisi dokumen',
                'url' => $this->opens(LetterNumberResource::class) ? LetterNumberResource::getUrl() : null,
                'icon' => 'heroicon-o-hashtag',
                'alert' => $numbersWithoutDocument > 0,
            ],
            [
                'key' => 'room',
                'label' => 'Ruangan Rapat',
                'value' => $rooms->count() - $roomsInUse,
                'unit' => 'dari '.$rooms->count().' ruangan tersedia',
                'detail' => $roomsInUse > 0 ? "{$roomsInUse} sedang dipakai saat ini" : 'Semua ruangan kosong saat ini',
                'url' => '#dsh-rooms',
                'icon' => 'heroicon-o-building-office-2',
                'alert' => false,
            ],
        ];

        $quickActions = array_values(array_filter([
            $this->opens(MeetingResource::class) && $user->can('create', Meeting::class) ? [
                'label' => 'Jadwalkan Rapat',
                'url' => route('filament.admin.resources.meetings.create'),
                'icon' => 'heroicon-m-plus',
                'primary' => true,
            ] : null,
            $this->opens(LetterNumberResource::class) && $user->can('create', LetterNumber::class) ? [
                'label' => 'Ambil Nomor Surat',
                'url' => LetterNumberResource::getUrl(),
                'icon' => 'heroicon-m-hashtag',
                'primary' => false,
            ] : null,
            $this->opens(DocumentResource::class) && $user->can('create', Document::class) ? [
                'label' => 'Isi Dokumen',
                'url' => route('filament.admin.resources.documents.create'),
                'icon' => 'heroicon-m-arrow-up-tray',
                'primary' => false,
            ] : null,
            $this->opens(ReminderHub::class) ? [
                'label' => 'Pengingat',
                'url' => ReminderHub::getUrl(),
                'icon' => 'heroicon-m-bell-alert',
                'primary' => false,
            ] : null,
            $this->opens(ComplianceHub::class) ? [
                'label' => 'Compliance Hub',
                'url' => ComplianceHub::getUrl(),
                'icon' => 'heroicon-m-shield-check',
                'primary' => false,
            ] : null,
        ]));

        return [
            'modules' => $modules,
            'quickActions' => $quickActions,
        ];
    }
}
