<?php

namespace App\Filament\Admin\Pages;

use App\Models\Document;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class RecycleBin extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static string|\UnitEnum|null $navigationGroup = 'Manajemen Dokumen';

    protected static ?int $navigationSort = 11;

    protected static ?string $title = 'Recycle Bin';

    protected string $view = 'filament.admin.pages.recycle-bin';

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = \Illuminate\Support\Facades\Auth::user();

        return $user?->hasRole(['super_admin', 'direktur', 'kabid']) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Document::onlyTrashed()->access())
            ->columns([
                // Mobile card
                TextColumn::make('title')
                    ->label('Dokumen')
                    ->searchable()
                    ->sortable()
                    ->view('filament.tables.columns.recycle-bin-mobile-card')
                    ->grow(),

                // Desktop columns
                TextColumn::make('department.name')
                    ->label('Departemen')
                    ->searchable()
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('deleted_at')
                    ->label('Dihapus Pada')
                    ->dateTime('d M Y H:i:s')
                    ->sortable()
                    ->visibleFrom('md'),
            ])
            ->actions([
                \Filament\Actions\RestoreAction::make()
                    ->label('Pulihkan')
                    ->successNotificationTitle('Dokumen dipulihkan'),
                \Filament\Actions\ForceDeleteAction::make()
                    ->label('Hapus Permanen')
                    ->successNotificationTitle('Dokumen dihapus permanen'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\RestoreBulkAction::make()
                        ->label('Pulihkan')
                        ->successNotificationTitle('Dokumen yang dipilih telah dipulihkan'),
                    \Filament\Actions\ForceDeleteBulkAction::make()
                        ->label('Hapus Permanen')
                        ->successNotificationTitle('Dokumen yang dipilih telah dihapus permanen'),
                ]),
            ])
            ->defaultSort('deleted_at', 'desc');
    }
}
