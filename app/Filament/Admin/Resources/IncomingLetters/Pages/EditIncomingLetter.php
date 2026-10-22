<?php

namespace App\Filament\Admin\Resources\IncomingLetters\Pages;

use App\Filament\Admin\Resources\IncomingLetters\IncomingLetterResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditIncomingLetter extends EditRecord
{
    protected static string $resource = IncomingLetterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->color('gray'),
            DeleteAction::make(),
        ];
    }
}
