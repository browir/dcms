<?php

namespace App\Filament\Admin\Widgets\Dashboard;

use App\Filament\Admin\Resources\Documents\DocumentResource;
use App\Filament\Admin\Resources\Meetings\MeetingResource;
use App\Filament\Admin\Widgets\Dashboard\Concerns\DashboardQueries;
use App\Models\Document;
use App\Models\LetterNumber;
use App\Models\Meeting;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\Widget;

/**
 * Pekerjaan yang perlu ditindaklanjuti: notulensi rapat yang belum dibuat
 * dan dokumen resmi yang menunggu tindakan / akan kedaluwarsa.
 */
class FollowUpWidget extends Widget
{
    use DashboardQueries;

    // Tampil-tidaknya widget diatur per role di Peran & Hak Akses → Widget (View:<NamaWidget>).
    use HasWidgetShield;

    protected string $view = 'filament.widgets.dashboard.follow-up';

    /** Nama widget di Peran & Hak Akses → Widget. */
    public function getHeading(): string
    {
        return 'Beranda: Notulensi & Dokumen Perlu Tindakan';
    }

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 30;

    protected function getViewData(): array
    {
        $user = $this->currentUser();

        $pendingMinutesQuery = $this->meetingsWithoutMinutes();
        $pendingMinutesCount = (clone $pendingMinutesQuery)->count();
        $pendingMinutes = $pendingMinutesQuery
            ->with('notulis:id,name')
            ->orderByDesc('date_time')
            ->limit(5)
            ->get()
            ->map(fn (Meeting $meeting) => [
                'title' => $meeting->title,
                'meta' => $meeting->date_time->locale('id')->isoFormat('D MMM YYYY')
                    .' · Notulis: '.($meeting->notulis?->name ?? 'belum ditentukan'),
                'age' => $meeting->date_time->locale('id')->diffForHumans(),
                'mine' => $meeting->notulis_id === $user->id,
                'url' => match (true) {
                    ! $this->opens(MeetingResource::class) => null,
                    $user->can('update', $meeting) => route('filament.admin.resources.meetings.edit', $meeting),
                    default => route('filament.admin.resources.meetings.view', $meeting),
                },
            ]);

        $recentMinutes = $this->meetingsWithMinutes()
            ->orderByDesc('date_time')
            ->limit(3)
            ->get()
            ->map(fn (Meeting $meeting) => [
                'title' => $meeting->title,
                'meta' => $meeting->date_time->locale('id')->isoFormat('D MMM YYYY')
                    .($meeting->doc_number ? ' · '.$meeting->doc_number : ''),
                'actions' => count($meeting->action_items ?? []),
                'url' => $this->opens(MeetingResource::class) ? route('filament.admin.resources.meetings.view', $meeting) : null,
            ]);

        $documentsQuery = $this->documentsNeedingAction();
        $documentsCount = (clone $documentsQuery)->count();
        $documents = $documentsQuery
            ->limit(5)
            ->get()
            ->map(fn (Document $document) => [
                'title' => $document->title,
                'meta' => ($document->code_number ?: 'Tanpa nomor').' · '.($document->category?->name ?? 'Tanpa kategori'),
                'status' => $document->status,
                'statusLabel' => match ($document->status) {
                    Document::STATUS_DRAFT => 'Draft',
                    Document::STATUS_REJECTED => 'Perlu revisi',
                    Document::STATUS_PENDING_KABID => 'Menunggu Kabid',
                    Document::STATUS_PENDING_DIREKTUR => 'Menunggu Direktur',
                    default => ucfirst($document->status),
                },
                'url' => $this->opens(DocumentResource::class) ? route('filament.admin.resources.documents.view', $document) : null,
            ]);

        $expiring = Document::query()->access()
            ->where('status', Document::STATUS_APPROVED)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays(30))
            ->orderBy('expires_at')
            ->limit(4)
            ->get()
            ->map(fn (Document $document) => [
                'title' => $document->title,
                'expired' => $document->expires_at->isPast(),
                'when' => $document->expires_at->isPast()
                    ? 'Kedaluwarsa '.$document->expires_at->locale('id')->diffForHumans()
                    : 'Berakhir '.$document->expires_at->locale('id')->isoFormat('D MMM YYYY'),
                'url' => $this->opens(DocumentResource::class) ? route('filament.admin.resources.documents.view', $document) : null,
            ]);

        // Nomor surat yang diambil user ini tapi dokumen resminya belum diisi.
        $unfilledNumbers = LetterNumber::query()->access()->available()
            ->where('created_by', $user->id)
            ->orderBy('tanggal_surat')
            ->limit(4)
            ->get()
            ->map(fn (LetterNumber $letter) => [
                'number' => $letter->nomor,
                'title' => $letter->judul_surat,
                'url' => $this->opens(DocumentResource::class) && DocumentResource::canCreate()
                    ? DocumentResource::getUrl('create', ['nomor_surat' => $letter->id])
                    : null,
            ]);

        return [
            'unfilledNumbers' => $unfilledNumbers,
            'pendingMinutes' => $pendingMinutes,
            'pendingMinutesCount' => $pendingMinutesCount,
            'recentMinutes' => $recentMinutes,
            'documents' => $documents,
            'documentsCount' => $documentsCount,
            'expiring' => $expiring,
        ];
    }
}
