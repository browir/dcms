<?php

namespace App\Filament\Admin\Resources\Units\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UnitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // Mobile card
                TextColumn::make('name')
                    ->label('Unit')
                    ->searchable()
                    ->sortable()
                    ->view('filament.tables.columns.unit-mobile-card')
                    ->grow(),

                // Desktop columns
                TextColumn::make('prefix')
                    ->label('Kode')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->searchable()
                    ->visibleFrom('md'),
                TextColumn::make('department.name')
                    ->label('Departemen')
                    ->searchable()
                    ->visibleFrom('md'),
                TextColumn::make('keycloak_code')
                    ->label('Kode HRIS')
                    ->fontFamily('mono')
                    ->placeholder('Belum diisi')
                    ->searchable()
                    ->visibleFrom('lg'),
                TextColumn::make('users_count')
                    ->label('Pegawai')
                    ->counts('users')
                    ->alignCenter()
                    ->sortable()
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
                SelectFilter::make('company_id')
                    ->label('Perusahaan')
                    ->relationship('department.company', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('department_id')
                    ->label('Departemen')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('tanpa_kode_hris')
                    ->label('Kode HRIS belum diisi')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->whereNull('keycloak_code')),
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
