<?php

namespace App\Filament\Admin\Resources\Meetings;

use App\Filament\Admin\Resources\Meetings\Pages\CreateMeeting;
use App\Filament\Admin\Resources\Meetings\Pages\EditMeeting;
use App\Filament\Admin\Resources\Meetings\Pages\ListMeetings;
use App\Filament\Admin\Resources\Meetings\Pages\ViewMeeting;
use App\Filament\Admin\Resources\Meetings\Schemas\MeetingForm;
use App\Filament\Admin\Resources\Meetings\Schemas\MeetingInfolist;
use App\Filament\Admin\Resources\Meetings\Tables\MeetingsTable;
use App\Models\Meeting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class MeetingResource extends Resource
{
    protected static ?string $model = Meeting::class;

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static ?string $navigationLabel = 'Rapat';

    protected static string|UnitEnum|null $navigationGroup = 'Rapat';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Rapat';

    protected static ?string $pluralModelLabel = 'Rapat';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return MeetingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MeetingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MeetingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMeetings::route('/'),
            'create' => CreateMeeting::route('/create'),
            'view' => ViewMeeting::route('/{record}'),
            'edit' => EditMeeting::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {

        return parent::getEloquentQuery()->access();
    }
}
