<?php

namespace App\Filament\Admin\Resources\MeetingLocationResource\Pages;

use App\Filament\Admin\Resources\MeetingLocationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMeetingLocations extends ListRecords
{
    protected static string $resource = MeetingLocationResource::class;

    public function getTitle(): string
    {
        return 'Ruangan Rapat';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Ruangan')
                ->icon('heroicon-m-plus')
                ->modalHeading('Tambah Ruangan Rapat')
                ->createAnother(false),
        ];
    }
}
