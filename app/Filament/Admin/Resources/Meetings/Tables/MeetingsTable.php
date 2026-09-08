<?php

namespace App\Filament\Admin\Resources\Meetings\Tables;

use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MeetingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // Mobile card
                TextColumn::make('title')
                    ->label('Rapat')
                    ->searchable()
                    ->sortable()
                    ->view('filament.tables.columns.meeting-mobile-card')
                    ->grow(),

                // Desktop columns
                TextColumn::make('index')
                    ->label('No.')
                    ->rowIndex()
                    ->visibleFrom('md'),
                TextColumn::make('date_time')->label('Tanggal & Waktu')->dateTime('d M Y H:i')->sortable()->visibleFrom('md'),
                TextColumn::make('location')
                    ->label('Lokasi')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->visibleFrom('md'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'scheduled' => 'Terjadwal',
                        'completed' => 'Selesai',
                        'cancelled' => 'Batal',
                        default => ucfirst($state),
                    })
                    ->color(fn ($state) => match ($state) {
                        'scheduled' => 'info',
                        'completed' => 'gray',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn ($state) => match ($state) {
                        'scheduled' => 'heroicon-o-calendar',
                        'completed' => 'heroicon-o-check-circle',
                        'cancelled' => 'heroicon-o-x-circle',
                        default => null,
                    })
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visibleFrom('md'),
                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visibleFrom('md'),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true)->visibleFrom('md'),
            ])
            ->defaultSort('date_time', 'desc')
            ->filters([
                Filter::make('is_invited')
                    ->label('Rapat Undangan Saya')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->whereHas('participants', fn ($q) => $q->where('users.id', auth()->id()))),
                SelectFilter::make('status')->options([
                    'scheduled' => 'Terjadwal',
                    'completed' => 'Selesai',
                    'cancelled' => 'Batal',
                ]),
                SelectFilter::make('company_id')
                    ->label('Perusahaan')
                    ->relationship('company', 'name')
                    ->visible(fn () => auth()->user()->hasRole('super_admin')),
                SelectFilter::make('month')
                    ->label('Bulan')
                    ->options([
                        '1' => 'Januari',
                        '2' => 'Februari',
                        '3' => 'Maret',
                        '4' => 'April',
                        '5' => 'Mei',
                        '6' => 'Juni',
                        '7' => 'Juli',
                        '8' => 'Agustus',
                        '9' => 'September',
                        '10' => 'Oktober',
                        '11' => 'November',
                        '12' => 'Desember',
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'], fn ($q, $month) => $q->whereMonth('date_time', $month))
                    ),
                Filter::make('date_filter')
                    ->form([
                        DatePicker::make('date_from')->label('Dari Tanggal'),
                        DatePicker::make('date_until')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date_time', '>=', $date),
                            )
                            ->when(
                                $data['date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date_time', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['date_from'] ?? null) {
                            $indicators[] = 'Mulai: '.Carbon::parse($data['date_from'])->translatedFormat('d M Y');
                        }
                        if ($data['date_until'] ?? null) {
                            $indicators[] = 'Sampai: '.Carbon::parse($data['date_until'])->translatedFormat('d M Y');
                        }

                        return $indicators;
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('viewNotulen')
                    ->label('Hasil Notulen')
                    ->icon('heroicon-o-document')
                    ->visible(fn ($record) => ! empty($record->file_path) &&
                        (auth()->user()->hasRole(['super_admin', 'Sekretaris']) ||
                         $record->created_by === auth()->id() ||
                         $record->participants()->where('users.id', auth()->id())->exists())
                    )
                    ->url(fn ($record) => route('notulen.view', $record))
                    ->openUrlInNewTab(),
                Action::make('cancel')
                    ->label('Batal')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Batalkan Rapat')
                    ->modalDescription('Apakah Anda yakin ingin membatalkan rapat ini? Tindakan ini tidak dapat dibatalkan.')
                    ->modalSubmitActionLabel('Ya, Batalkan')
                    ->modalCancelActionLabel('Tidak')
                    ->visible(fn ($record) => in_array($record->status, ['terjadwal', 'scheduled']) &&
                        (auth()->user()->hasRole(['super_admin', 'Sekretaris']) || $record->created_by === auth()->id())
                    )
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'cancelled',
                        ]);

                        Notification::make()
                            ->title('Meeting berhasil dibatalkan')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
