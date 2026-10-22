<?php

namespace App\Filament\Admin\Pages;

use App\Models\Document;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class RecycleBin extends Page implements HasTable
{
    // Akses diatur di Peran & Hak Akses → Halaman (View:RecycleBin).
    use HasPageShield;
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static string|\UnitEnum|null $navigationGroup = 'Dokumen';

    protected static ?int $navigationSort = 7;

    protected static ?string $title = 'Recycle Bin';

    protected string $view = 'filament.admin.pages.recycle-bin';

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
                RestoreAction::make()
                    ->label('Pulihkan')
                    ->successNotificationTitle('Dokumen dipulihkan'),
                ForceDeleteAction::make()
                    ->label('Hapus Permanen')
                    ->successNotificationTitle('Dokumen dihapus permanen'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    RestoreBulkAction::make()
                        ->label('Pulihkan')
                        ->successNotificationTitle('Dokumen yang dipilih telah dipulihkan'),
                    ForceDeleteBulkAction::make()
                        ->label('Hapus Permanen')
                        ->successNotificationTitle('Dokumen yang dipilih telah dihapus permanen'),
                ]),
            ])
            ->defaultSort('deleted_at', 'desc');
    }
}
