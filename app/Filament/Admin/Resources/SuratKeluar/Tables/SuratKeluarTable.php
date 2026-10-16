<?php

namespace App\Filament\Admin\Resources\SuratKeluar\Tables;

use App\Enums\JenisSurat;
use App\Models\Company;
use App\Models\Unit;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Definisi tabel Surat Keluar.
 *
 * Tabel ini hanya-baca:
 *   - Tidak ada recordActions (edit/hapus).
 *   - Tidak ada bulkActions.
 *   - Default sort: tanggal terbaru lalu id terbaru.
 *   - Pagination: 10 per halaman.
 *
 * Kolom pencarian: nomor, judul_surat, perihal (tersembunyi), dibuat_oleh_nama.
 * Filter: perusahaan, unit, jenis surat.
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
            ->recordActions([])
            ->bulkActions([])
            ->paginationPageOptions([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->emptyStateIcon('heroicon-o-envelope')
            ->emptyStateHeading('Belum ada surat keluar')
            ->emptyStateDescription('Gunakan tombol "Buat Nomor Surat" di atas untuk membuat surat keluar baru.');
    }
}
