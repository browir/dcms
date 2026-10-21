<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
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

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['company:id,name', 'department:id,name', 'unit:id,name', 'roles:id,name']))
            ->defaultSort('name')
            ->columns([
                // Mobile card (hidden on desktop via CSS)
                TextColumn::make('name')
                    ->label('Pengguna')
                    ->searchable(['name', 'username', 'nip', 'email'])
                    ->sortable()
                    ->view('filament.tables.columns.user-mobile-card')
                    ->grow(),

                // Desktop-only columns
                TextColumn::make('email')
                    ->label('Email')
                    ->description(fn (User $record): ?string => $record->nip ? 'NIP '.$record->nip : null)
                    ->copyable()
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('unit.name')
                    ->label('Unit / Departemen')
                    ->description(fn (User $record): ?string => collect([$record->department?->name, $record->company?->name])->filter()->implode(' · ') ?: null)
                    ->placeholder('Belum ditempatkan')
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('roles.name')
                    ->label('Peran')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'direktur' => 'warning',
                        'kabid' => 'info',
                        default => 'gray',
                    })
                    ->placeholder('—')
                    ->visibleFrom('md'),
                IconColumn::make('keycloak_sub')
                    ->label('SSO')
                    ->state(fn (User $record): bool => filled($record->keycloak_sub))
                    ->boolean()
                    ->trueIcon('heroicon-o-key')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn (User $record): string => filled($record->keycloak_sub) ? 'Sudah tersambung akun SSO' : 'Belum pernah login lewat SSO')
                    ->alignCenter()
                    ->visibleFrom('lg'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->alignCenter()
                    ->visibleFrom('md'),
                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visibleFrom('md'),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label('Peran')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->native(false),
                SelectFilter::make('company')
                    ->relationship('company', 'name')
                    ->label('Perusahaan')
                    ->searchable()
                    ->preload()
                    ->native(false),
                SelectFilter::make('department')
                    ->relationship('department', 'name')
                    ->label('Departemen')
                    ->searchable()
                    ->preload()
                    ->native(false),
                SelectFilter::make('unit')
                    ->relationship('unit', 'name')
                    ->label('Unit')
                    ->searchable()
                    ->preload()
                    ->native(false),
                TernaryFilter::make('is_active')
                    ->label('Status akun')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif')
                    ->native(false),
                TernaryFilter::make('sso')
                    ->label('Akun SSO')
                    ->trueLabel('Sudah tersambung')
                    ->falseLabel('Belum tersambung')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('keycloak_sub'),
                        false: fn (Builder $query) => $query->whereNull('keycloak_sub'),
                    )
                    ->native(false),
                Filter::make('tanpa_unit')
                    ->label('Belum ditempatkan di unit')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->whereNull('unit_id')),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ])->icon('heroicon-m-ellipsis-vertical')->tooltip('Aksi'),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
