<?php

namespace App\Filament\Admin\Resources\Documents\Pages;

use App\Filament\Admin\Resources\Documents\DocumentResource;
use App\Filament\Admin\Resources\Documents\Widgets\MyWidget;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class ListDocuments extends ListRecords
{
    protected static string $resource = DocumentResource::class;

    /** Menu Dokumen Resmi / Surat Keluar = daftar yang sama, dibatasi kelompok kategori. */
    #[Url]
    public ?string $kelompok = null;

    public function getTitle(): string
    {
        return DocumentCategory::KELOMPOK[$this->kelompok] ?? 'Dokumen';
    }

    public function getBreadcrumb(): ?string
    {
        return DocumentCategory::KELOMPOK[$this->kelompok] ?? parent::getBreadcrumb();
    }

    private function inKelompok(Builder $query): Builder
    {
        return $query->when(
            isset(DocumentCategory::KELOMPOK[$this->kelompok]),
            fn (Builder $q) => $q->whereHas('category', fn (Builder $c) => $c->where('kelompok', $this->kelompok)),
        );
    }

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
                ->label($this->kelompok === DocumentCategory::KELOMPOK_SURAT_KELUAR ? 'Buat Surat Keluar' : 'Tambah Dokumen')
                ->url(fn (): string => DocumentResource::getUrl('create', array_filter(['kelompok' => $this->kelompok])))
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
        $count = fn (callable $scope): ?int => ($n = $scope($this->inKelompok(Document::query()->access()))->count()) > 0 ? $n : null;

        return [
            'perlu_tindakan' => Tab::make('Perlu Tindakan Saya')
                ->icon('heroicon-m-bolt')
                ->modifyQueryUsing(fn (Builder $query) => $this->inKelompok($query)->needsActionBy($user))
                ->badge(fn () => $count(fn (Builder $q) => $q->needsActionBy($user)))
                ->badgeColor('warning'),
            'milik_saya' => Tab::make('Milik Saya')
                ->modifyQueryUsing(fn (Builder $query) => $this->inKelompok($query)->where('user_id', $user->id)),
            'direview' => Tab::make('Sedang Direview')
                ->modifyQueryUsing(fn (Builder $query) => $this->inKelompok($query)->whereIn('status', [Document::STATUS_PENDING_KABID, Document::STATUS_PENDING_DIREKTUR]))
                ->badge(fn () => $count(fn (Builder $q) => $q->whereIn('status', [Document::STATUS_PENDING_KABID, Document::STATUS_PENDING_DIREKTUR]))),
            'disetujui' => Tab::make('Disetujui')
                ->modifyQueryUsing(fn (Builder $query) => $this->inKelompok($query)->where('status', Document::STATUS_APPROVED)),
            'wajib_baca' => Tab::make('Wajib Baca')
                ->modifyQueryUsing(fn (Builder $query) => $this->inKelompok($query)->where('is_mandatory_read', true)),
            'semua' => Tab::make('Semua')
                ->modifyQueryUsing(fn (Builder $query) => $this->inKelompok($query)),
        ];
    }

    /** Buka langsung di tab yang paling berguna: ada pekerjaan → "Perlu Tindakan", kalau tidak → "Semua". */
    public function getDefaultActiveTab(): string|int|null
    {
        /** @var User $user */
        $user = Auth::user();

        return $this->inKelompok(Document::query()->access())->needsActionBy($user)->exists() ? 'perlu_tindakan' : 'semua';
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}
