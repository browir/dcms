<?php

namespace App\Filament\Admin\Resources\Documents\Pages;

use App\Filament\Admin\Resources\Documents\DocumentResource;
use App\Filament\Admin\Resources\Documents\Widgets\MyWidget;
use App\Models\Document;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListDocuments extends ListRecords
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('guide')
                ->label('Panduan Manajemen Dokumen')
                ->icon('heroicon-o-information-circle')
                ->color('info')
                ->modalHeading('Panduan Manajemen Dokumen')
                ->modalIcon('heroicon-o-document-text')
                ->modalWidth('3xl')
                ->modalContent(view('filament.admin.resources.documents.partials.document-management-guide-modal'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup'),
            CreateAction::make()
                ->label('Tambah Dokumen')
                ->icon('heroicon-o-plus-circle')
                ->color('primary'),
        ];
    }

    /**
     * Ringkasan angka saja; daftar dokumen yang hampir kedaluwarsa ada di Beranda dan
     * lewat filter "Masa Berlaku" — supaya tabel langsung terlihat tanpa menggulir.
     */
    protected function getHeaderWidgets(): array
    {
        return [
            MyWidget::class,
        ];
    }

    public function getTabs(): array
    {
        /** @var User $user */
        $user = Auth::user();
        $count = fn (callable $scope): ?int => ($n = $scope(Document::query()->access())->count()) > 0 ? $n : null;

        return [
            'perlu_tindakan' => Tab::make('Perlu Tindakan Saya')
                ->icon('heroicon-m-bolt')
                ->modifyQueryUsing(fn (Builder $query) => $query->needsActionBy($user))
                ->badge(fn () => $count(fn (Builder $q) => $q->needsActionBy($user)))
                ->badgeColor('warning'),
            'milik_saya' => Tab::make('Milik Saya')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('user_id', $user->id)),
            'direview' => Tab::make('Sedang Direview')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [Document::STATUS_PENDING_KABID, Document::STATUS_PENDING_DIREKTUR]))
                ->badge(fn () => $count(fn (Builder $q) => $q->whereIn('status', [Document::STATUS_PENDING_KABID, Document::STATUS_PENDING_DIREKTUR]))),
            'disetujui' => Tab::make('Disetujui')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Document::STATUS_APPROVED)),
            'wajib_baca' => Tab::make('Wajib Baca')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_mandatory_read', true)),
            'semua' => Tab::make('Semua'),
        ];
    }

    /** Buka langsung di tab yang paling berguna: ada pekerjaan → "Perlu Tindakan", kalau tidak → "Semua". */
    public function getDefaultActiveTab(): string|int|null
    {
        /** @var User $user */
        $user = Auth::user();

        return Document::query()->access()->needsActionBy($user)->exists() ? 'perlu_tindakan' : 'semua';
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}
