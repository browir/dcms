<?php

namespace App\Filament\Admin\Resources\SuratKeluar\Tables;

use App\Enums\JenisSurat;
use App\Models\Company;
use App\Models\Surat;
use App\Models\Unit;
use App\Services\NomorSuratManualService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

/**
 * Definisi tabel Surat Keluar.
 *
 * Menampilkan daftar surat keluar (otomatis & manual).
 * Fitur:
 *   - Kolom pencarian & filter (perusahaan, unit, jenis, metode penomoran).
 *   - Aksi baris: "Lihat" (detail modal) dan "Ubah" (edit untuk surat manual / pergantian mode).
 *   - Pagination: 10 per halaman.
 */
class SuratKeluarTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['unit.company'])
                ->where('tipe_surat', 'keluar')
                ->orderBy('tanggal', 'desc')
                ->orderBy('id', 'desc')
            )
            ->columns([
                TextColumn::make('nomor')
                    ->label('Nomor Surat')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Nomor surat disalin!')
                    ->copyMessageDuration(2000)
                    ->weight('bold')
                    ->fontFamily('mono')
                    ->wrap(),

                TextColumn::make('numbering_type')
                    ->label('Metode')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === Surat::NUMBERING_MANUAL ? 'Manual' : 'Otomatis')
                    ->color(fn ($state) => $state === Surat::NUMBERING_MANUAL ? 'warning' : 'gray'),

                TextColumn::make('jenis')
                    ->label('Jenis')
                    ->formatStateUsing(fn ($state) => $state instanceof JenisSurat
                        ? "{$state->value} — {$state->label()}"
                        : (string) $state
                    )
                    ->badge()
                    ->color('info')
                    ->wrap(),

                TextColumn::make('unit.company.kode_surat')
                    ->label('Perusahaan')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('unit.name')
                    ->label('Unit'),

                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('judul_surat')
                    ->label('Judul Surat')
                    ->searchable()
                    ->limit(60)
                    ->tooltip(fn (TextColumn $column): ?string => strlen((string) $column->getState()) > 60
                        ? $column->getState()
                        : null
                    ),

                TextColumn::make('dibuat_oleh_nama')
                    ->label('Pembuat')
                    ->searchable(),

                // Kolom perihal: tersembunyi dari tampilan tapi tetap ikut global search.
                TextColumn::make('perihal')
                    ->label('Perihal')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('numbering_type')
                    ->label('Metode Penomoran')
                    ->options([
                        Surat::NUMBERING_AUTO   => 'Otomatis',
                        Surat::NUMBERING_MANUAL => 'Manual',
                    ]),

                SelectFilter::make('perusahaan')
                    ->label('Perusahaan')
                    ->options(fn (): array => Company::query()
                        ->whereNotNull('kode_surat')
                        ->where('kode_surat', '<>', '')
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value']),
                        fn (Builder $q) => $q->whereHas(
                            'unit',
                            fn (Builder $u) => $u->where('company_id', $data['value'])
                        )
                    )),

                SelectFilter::make('unit_id')
                    ->label('Unit')
                    ->relationship('unit', 'name'),

                SelectFilter::make('jenis')
                    ->label('Jenis Surat')
                    ->options(JenisSurat::options()),
            ])
            ->recordActions([
                Action::make('lihat')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn (Surat $record): string => "Detail Surat: {$record->nomor}")
                    ->modalWidth('lg')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->form(fn (Surat $record): array => [
                        TextInput::make('nomor')
                            ->label('Nomor Surat')
                            ->default($record->nomor)
                            ->disabled(),

                        TextInput::make('metode')
                            ->label('Metode Penomoran')
                            ->default($record->isManual() ? 'Manual' : 'Otomatis')
                            ->disabled(),

                        TextInput::make('jenis')
                            ->label('Jenis Surat')
                            ->default($record->jenis instanceof JenisSurat
                                ? "{$record->jenis->value} — {$record->jenis->label()}"
                                : (string) $record->jenis
                            )
                            ->disabled(),

                        TextInput::make('perusahaan')
                            ->label('Perusahaan')
                            ->default($record->unit?->company?->name ?? '—')
                            ->disabled(),

                        TextInput::make('unit')
                            ->label('Unit')
                            ->default($record->unit?->name ?? '—')
                            ->disabled(),

                        TextInput::make('tanggal')
                            ->label('Tanggal Surat')
                            ->default($record->tanggal ? Carbon::parse($record->tanggal)->translatedFormat('d F Y') : '—')
                            ->disabled(),

                        TextInput::make('judul_surat')
                            ->label('Judul Surat')
                            ->default($record->judul_surat)
                            ->disabled(),

                        TextInput::make('perihal')
                            ->label('Perihal')
                            ->default($record->perihal ?? '—')
                            ->disabled(),

                        Textarea::make('deskripsi')
                            ->label('Deskripsi')
                            ->default($record->deskripsi ?? '—')
                            ->rows(3)
                            ->disabled(),

                        TextInput::make('dibuat_oleh_nama')
                            ->label('Dibuat Oleh')
                            ->default($record->dibuat_oleh_nama ?? '—')
                            ->disabled(),

                        TextInput::make('created_at')
                            ->label('Waktu Diterbitkan')
                            ->default($record->created_at ? $record->created_at->translatedFormat('d F Y H:i') : '—')
                            ->disabled(),
                    ]),

                Action::make('ubah')
                    ->label('Ubah')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->visible(fn (Surat $record): bool => NomorSuratManualService::canEditManual(auth()->user(), $record))
                    ->modalHeading(fn (Surat $record): string => "Ubah Surat: {$record->nomor}")
                    ->modalWidth('xl')
                    ->modalSubmitActionLabel('Simpan Perubahan')
                    ->form(fn (Surat $record): array => [
                        Radio::make('numbering_type')
                            ->label('Mode Penomoran')
                            ->options($record->isManual()
                                ? [
                                    Surat::NUMBERING_MANUAL => 'Manual (Kustom)',
                                    Surat::NUMBERING_AUTO   => 'Ganti ke Otomatis (Generate Nomor Baru)',
                                ]
                                : [
                                    Surat::NUMBERING_AUTO   => 'Otomatis (Tetap)',
                                    Surat::NUMBERING_MANUAL => 'Ganti ke Manual (Nomor Otomatis Lama Hangus)',
                                ]
                            )
                            ->default($record->numbering_type ?? Surat::NUMBERING_AUTO)
                            ->inline()
                            ->live(),

                        TextInput::make('nomor')
                            ->label('Nomor Surat')
                            ->default($record->nomor)
                            ->required(fn ($get) => ($get('numbering_type') ?? $record->numbering_type) === Surat::NUMBERING_MANUAL)
                            ->visible(fn ($get) => ($get('numbering_type') ?? $record->numbering_type) === Surat::NUMBERING_MANUAL)
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->helperText(function (?string $state): ?HtmlString {
                                if (blank($state)) {
                                    return new HtmlString('<span class="text-xs text-gray-500">Format nomor bebas. Standar sistem: <code>{urut}/{jenis}.{unit}/{perusahaan}/{bulan_romawi}/{tahun}</code></span>');
                                }
                                $warning = NomorSuratManualService::getPatternWarning($state);
                                if ($warning) {
                                    return new HtmlString('<span class="text-xs text-amber-600 font-medium">⚠️ ' . e($warning) . '</span>');
                                }
                                return new HtmlString('<span class="text-xs text-success-600 font-medium">✓ Format sesuai pola standar</span>');
                            }),

                        DatePicker::make('tanggal')
                            ->label('Tanggal Surat')
                            ->default($record->tanggal)
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        TextInput::make('judul_surat')
                            ->label('Judul Surat')
                            ->default($record->judul_surat)
                            ->required()
                            ->maxLength(255),

                        TextInput::make('perihal')
                            ->label('Perihal')
                            ->default($record->perihal)
                            ->maxLength(255),

                        Textarea::make('deskripsi')
                            ->label('Deskripsi')
                            ->default($record->deskripsi)
                            ->rows(3),
                    ])
                    ->action(function (Surat $record, array $data, Action $action): void {
                        if (! NomorSuratManualService::canEditManual(auth()->user(), $record)) {
                            Notification::make()
                                ->title('Akses Ditolak')
                                ->body('Anda tidak memiliki izin untuk mengubah surat ini.')
                                ->danger()
                                ->send();

                            $action->halt();

                            return;
                        }

                        try {
                            $updated = NomorSuratManualService::update($record, $data, auth()->user());

                            Notification::make()
                                ->title('Surat Berhasil Diperbarui')
                                ->body("Nomor sekarang: **{$updated->nomor}**")
                                ->success()
                                ->send();
                        } catch (\Illuminate\Validation\ValidationException $e) {
                            $firstError = collect($e->errors())->flatten()->first() ?? 'Nomor surat tidak valid.';
                            Notification::make()
                                ->title('Gagal Memperbarui Surat')
                                ->body($firstError)
                                ->danger()
                                ->send();

                            $action->halt();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Gagal Memperbarui Surat')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            $action->halt();
                        }
                    }),
            ])
            ->bulkActions([])
            ->paginationPageOptions([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->emptyStateIcon('heroicon-o-envelope')
            ->emptyStateHeading('Belum ada surat keluar')
            ->emptyStateDescription('Gunakan tombol "Buat Nomor Surat" di atas untuk membuat surat keluar baru.');
    }
}
