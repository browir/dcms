<?php

namespace App\Filament\Admin\Resources\Agendas\Pages;

use App\Filament\Admin\Resources\Agendas\AgendaActions;
use App\Filament\Admin\Resources\Agendas\AgendaResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAgenda extends ViewRecord
{
    protected static string $resource = AgendaResource::class;

    public function getTitle(): string
    {
        return $this->getRecord()->title;
    }

    protected function getHeaderActions(): array
    {
        return [
            AgendaActions::announce(),
            AgendaActions::issueNotice(),
            AgendaActions::printNotice(),
            EditAction::make()->color('gray'),
        ];
    }
}
