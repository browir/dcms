<?php

namespace App\Filament\Admin\Resources\Meetings\Pages;

use App\Filament\Admin\Resources\Meetings\MeetingResource;
use App\Models\Meeting;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListMeetings extends ListRecords
{
    protected static string $resource = MeetingResource::class;

    public function getTitle(): string
    {
        return 'Daftar Rapat';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('tutorial')
                ->label('Panduan')
                ->icon('heroicon-o-information-circle')
                ->color('info')
                ->modalHeading('Petunjuk Penggunaan Modul Rapat')
                ->modalWidth('4xl')
                ->modalContent(view('filament.tutorial-modal', ['image' => 'rapat1.jpg']))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup'),
            CreateAction::make()
                ->label('Tambah Rapat')
                ->createAnother(false),
        ];
    }

    public function mount(): void
    {
        parent::mount();

        // Auto-update status rapat yang sudah melewati jam berakhir menjadi 'completed' (Berakhir)
        Meeting::query()
            ->where('status', 'scheduled')
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereNotNull('end_time')
                        ->where('end_time', '<=', now());
                })->orWhere(function ($q) {
                    $q->whereNull('end_time')
                        ->where('date_time', '<=', now());
                });
            })
            ->update(['status' => 'completed']);
    }

    public function getTabs(): array
    {
        $userId = (int) auth()->id();
        $count = fn (callable $scope): ?int => ($n = $scope(Meeting::query()->access())->count()) > 0 ? $n : null;

        return [
            'hari_ini' => Tab::make('Hari Ini')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('date_time', today())->where('status', '!=', 'cancelled'))
                ->badge(fn () => $count(fn (Builder $q) => $q->whereDate('date_time', today())->where('status', '!=', 'cancelled')))
                ->badgeColor('success'),
            'terjadwal' => Tab::make('Mendatang')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'scheduled')->reorder('date_time')),
            'undangan' => Tab::make('Melibatkan Saya')
                ->modifyQueryUsing(fn (Builder $query) => $query->involving($userId)->where('status', 'scheduled')->reorder('date_time'))
                ->badge(fn () => $count(fn (Builder $q) => $q->involving($userId)->where('status', 'scheduled'))),
            'perlu_notulen' => Tab::make('Perlu Notulen')
                ->modifyQueryUsing(fn (Builder $query) => $query->missingMinutes())
                ->badge(fn () => $count(fn (Builder $q) => $q->missingMinutes()))
                ->badgeColor('warning'),
            'berakhir' => Tab::make('Selesai')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed')),
            'batal' => Tab::make('Batal')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled')),
            'semua' => Tab::make('Semua'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'terjadwal';
    }
}
