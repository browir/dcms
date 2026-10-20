<?php

namespace App\Filament\Admin\Resources\LetterNumbers\Pages;

use App\Filament\Admin\Resources\Documents\DocumentResource;
use App\Filament\Admin\Resources\LetterNumbers\LetterNumberResource;
use App\Filament\Admin\Resources\LetterNumbers\Schemas\LetterNumberForm;
use App\Models\Document;
use App\Models\LetterNumber;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListLetterNumbers extends ListRecords
{
    protected static string $resource = LetterNumberResource::class;

    public function getSubheading(): ?string
    {
        return 'Ambil nomor surat dulu, lalu isi dokumen resminya ke nomor tersebut. Nomor yang sudah terbit tidak bisa diubah atau dibatalkan.';
    }

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua'),
            'belum' => Tab::make('Belum diisi')
                ->modifyQueryUsing(fn (Builder $query) => $query->available())
                ->badge(fn () => LetterNumber::query()->access()->available()->count() ?: null)
                ->badgeColor('warning'),
            'saya' => Tab::make('Diambil saya')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('created_by', auth()->id())),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Ambil Nomor Surat')
                ->icon('heroicon-o-hashtag')
                ->modalHeading('Ambil Nomor Surat')
                ->modalDescription('Nomor diterbitkan saat disimpan dan langsung tercatat. Setelah itu isi dokumen resminya ke nomor ini.')
                ->modalSubmitActionLabel('Terbitkan Nomor')
                ->modalWidth('lg')
                ->createAnother(false)
                ->schema(LetterNumberForm::components())
                ->using(fn (array $data): LetterNumber => LetterNumberForm::issue($data))
                ->successNotification(null)
                ->after(function (LetterNumber $record): void {
                    Notification::make()
                        ->success()
                        ->title('Nomor surat terbit')
                        ->body($record->nomor)
                        ->persistent()
                        ->actions(array_filter([
                            auth()->user()?->can('create', Document::class)
                                ? Action::make('isi')
                                    ->label('Isi Dokumen Sekarang')
                                    ->button()
                                    ->url(DocumentResource::getUrl('create', ['nomor_surat' => $record->id]))
                                : null,
                        ]))
                        ->send();
                }),
        ];
    }
}
