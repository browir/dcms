<?php

namespace App\Filament\Admin\Pages;

use App\Models\Document;
use App\Models\DocumentReadAcknowledgment;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ComplianceHub extends Page
{
    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Dokumen';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Compliance Hub';

    protected static ?string $title = 'Compliance Hub';

    public function getView(): string
    {
        return 'filament.admin.pages.compliance-hub';
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('guide')
                ->label('Panduan Compliance Hub')
                ->icon('heroicon-o-information-circle')
                ->color('info')
                ->modalHeading('Panduan Penggunaan Compliance Hub')
                ->modalIcon('heroicon-o-shield-check')
                ->modalWidth('3xl')
                ->modalContent(view('filament.admin.pages.compliance-hub-guide-modal'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (! $user) {
            return null;
        }

        $unread = \Illuminate\Support\Facades\Cache::remember(
            'nav_badge_compliance_unread_'.$user->id,
            30,
            function () {
                $docs = self::getMandatoryDocuments();
                $readIds = self::getReadDocumentIds()->toArray();

                return $docs->whereNotIn('id', $readIds)->count();
            }
        );

        return $unread > 0 ? (string) $unread : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Dokumen wajib baca yang belum dibaca';
    }

    public static function getMandatoryDocuments()
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        return Document::query()
            ->mandatoryForUser($user)
            ->latest()
            ->get();
    }

    public static function getReadDocumentIds()
    {
        return DocumentReadAcknowledgment::where('user_id', Auth::id())
            ->pluck('document_id');
    }

    public array $openedDocuments = [];

    public function trackOpenDocument(int $documentId): void
    {
        $this->openedDocuments[$documentId] = time();
    }

    public function acknowledge(int $documentId): void
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (! $user) {
            return;
        }

        // Validasi ketersediaan dan relevansi dokumen
        $doc = Document::query()->mandatoryForUser($user)->find($documentId);
        if (! $doc) {
            \Filament\Notifications\Notification::make()
                ->title('Dokumen Tidak Valid')
                ->body('Dokumen ini tidak ditemukan atau tidak wajib dibaca untuk Anda.')
                ->danger()
                ->send();

            return;
        }

        DocumentReadAcknowledgment::firstOrCreate([
            'document_id' => $documentId,
            'user_id' => $user->id,
        ], [
            'read_at' => now(),
        ]);

        // Hapus cache badge & widget
        \Illuminate\Support\Facades\Cache::forget('nav_badge_compliance_unread_'.$user->id);
        \Illuminate\Support\Facades\Cache::forget('widget_unread_compliance_'.$user->id);

        \Filament\Notifications\Notification::make()
            ->title('Konfirmasi Kepatuhan Berhasil')
            ->body('Terima kasih. Pernyataan bahwa Anda telah membaca dokumen "'.$doc->title.'" telah berhasil dicatat.')
            ->success()
            ->send();

        $this->dispatch('$refresh');
    }

    public function getViewData(): array
    {
        $mandatoryDocs = self::getMandatoryDocuments();
        $readIds = self::getReadDocumentIds()->toArray();
        $totalCount = $mandatoryDocs->count();
        $readCount = $mandatoryDocs->whereIn('id', $readIds)->count();
        $percentage = $totalCount > 0 ? round(($readCount / $totalCount) * 100) : 100;

        $unreadDocs = $mandatoryDocs->whereNotIn('id', $readIds);
        $readDocs = $mandatoryDocs->whereIn('id', $readIds);

        return [
            'totalCount' => $totalCount,
            'readCount' => $readCount,
            'percentage' => $percentage,
            'unreadDocs' => $unreadDocs,
            'readDocs' => $readDocs,
            'openedDocuments' => $this->openedDocuments,
        ];
    }
}
