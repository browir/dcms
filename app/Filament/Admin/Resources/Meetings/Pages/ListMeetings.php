<?php

namespace App\Filament\Admin\Resources\Meetings\Pages;

use App\Filament\Admin\Resources\Meetings\MeetingResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

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
        \App\Models\Meeting::query()
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
        return [
            null => Tab::make('Semua'),
            'terjadwal' => Tab::make('Terjadwal')->query(fn ($query) => $query->where('status', 'scheduled')),
            'berakhir' => Tab::make('Selesai')->query(fn ($query) => $query->where('status', 'completed')),
            'batal' => Tab::make('Batal')->query(fn ($query) => $query->where('status', 'cancelled')),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'terjadwal';
    }
}
