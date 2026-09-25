<?php

namespace App\Filament\Admin\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class AuditTrail extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static string|\UnitEnum|null $navigationGroup = 'Manajemen Dokumen';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Audit Trail';

    protected string $view = 'filament.admin.pages.audit-trail';

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = \Illuminate\Support\Facades\Auth::user();

        return $user?->hasRole(['super_admin', 'direktur', 'kabid']) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Activity::query()->latest())
            ->columns([
                TextColumn::make('causer.name')
                    ->label('Aktor')
                    ->view('filament.tables.columns.activity-mobile-card')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Sistem'),
                TextColumn::make('event')
                    ->label('Tindakan')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'created' => 'Dibuat',
                        'updated' => 'Diperbarui',
                        'deleted' => 'Dihapus',
                        'restored' => 'Dipulihkan',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'restored' => 'info',
                        default => 'gray',
                    })
                    ->visibleFrom('md'),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->searchable()
                    ->wrap()
                    ->visibleFrom('md'),
                TextColumn::make('subject_type')
                    ->label('Modul')
                    ->formatStateUsing(function ($state) {
                        return class_basename($state);
                    })
                    ->searchable()
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i:s')
                    ->sortable()
                    ->visibleFrom('md'),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->label('Tindakan')
                    ->options([
                        'created' => 'Dibuat',
                        'updated' => 'Diperbarui',
                        'deleted' => 'Dihapus',
                        'restored' => 'Dipulihkan',
                    ]),
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')->label('Dari Tanggal'),
                        DatePicker::make('created_until')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
