<?php

namespace App\Filament\Admin\Resources\DocumentCategories\Tables;

use App\Enum\JenisSurat;
use App\Filament\Admin\Resources\LetterNumbers\LetterNumberResource;
use App\Models\DocumentCategory;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class DocumentCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('code')
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->formatStateUsing(fn (?string $state): HtmlString|string => ($jenis = JenisSurat::tryFrom((string) $state)) ? LetterNumberResource::jenisBadge($jenis) : '—')
                    ->html()
                    ->sortable()
                    ->visibleFrom('md'),

                // Mobile card
                TextColumn::make('name')
                    ->label('Kategori')
                    ->searchable(['name', 'code'])
                    ->sortable()
                    ->view('filament.tables.columns.document-category-mobile-card')
                    ->grow(),

                TextColumn::make('kelompok')
                    ->label('Menu')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => DocumentCategory::KELOMPOK[$state] ?? '—')
                    ->color(fn (?string $state): string => $state === DocumentCategory::KELOMPOK_SURAT_KELUAR ? 'info' : 'gray')
                    ->visibleFrom('md'),
                TextColumn::make('company.name')
                    ->label('Perusahaan')
                    ->placeholder('Semua perusahaan')
                    ->visibleFrom('lg'),
                TextColumn::make('documents_count')
                    ->label('Dokumen')
                    ->numeric()
                    ->sortable()
                    ->visibleFrom('md'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->visibleFrom('md'),
            ])
            ->filters([
                SelectFilter::make('kelompok')
                    ->label('Menu')
                    ->options(DocumentCategory::KELOMPOK),
                SelectFilter::make('company_id')
                    ->label('Perusahaan')
                    ->relationship('company', 'name'),
                TernaryFilter::make('umum')
                    ->label('Berlaku')
                    ->placeholder('Semua')
                    ->trueLabel('Semua perusahaan')
                    ->falseLabel('Khusus perusahaan')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNull('company_id'),
                        false: fn (Builder $q) => $q->whereNotNull('company_id'),
                        blank: fn (Builder $q) => $q,
                    ),
                TernaryFilter::make('is_active')->label('Aktif'),
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
