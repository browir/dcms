<?php

namespace App\Filament\Admin\Resources\Companies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // Mobile card
                TextColumn::make('name')
                    ->label('Perusahaan')
                    ->searchable()
                    ->sortable()
                    ->view('filament.tables.columns.company-mobile-card')
                    ->grow(),

                // Desktop columns
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->visibleFrom('md'),
                TextColumn::make('kode_surat')
                    ->label('Kode Surat')
                    ->placeholder('= Kode')
                    ->searchable()
                    ->visibleFrom('md'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->visibleFrom('md'),
                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visibleFrom('md'),
                TextColumn::make('updated_at')
                    ->label('Diperbarui Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visibleFrom('md'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->boolean()
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif')
                    ->native(false),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
