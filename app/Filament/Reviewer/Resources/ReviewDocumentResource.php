<?php

namespace App\Filament\Reviewer\Resources;

use App\Filament\Admin\Resources\Documents\Schemas\DocumentInfolist;
use App\Filament\Reviewer\Resources\ReviewDocumentResource\Pages;
use App\Models\Document;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ReviewDocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Review Dokumen';

    protected static ?string $pluralModelLabel = 'Review Dokumen';

    protected static string|UnitEnum|null $navigationGroup = 'Dokumen';

    protected static ?string $slug = 'review-documents';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return DocumentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        /** @var User $user */
        $user = Auth::user();

        return $table
            ->columns([
                TextColumn::make('index')
                    ->label('No.')
                    ->rowIndex()
                    ->visibleFrom('md'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending_kabid' => 'warning',
                        'pending_direktur' => 'info',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): ?string => match ($state) {
                        'pending_kabid', 'pending_direktur' => 'heroicon-m-clock',
                        'approved' => 'heroicon-m-check-circle',
                        'rejected' => 'heroicon-m-x-circle',
                        'archived' => 'heroicon-m-archive-box',
                        default => null,
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Pending',
                        'pending_kabid' => 'Menunggu Kabid',
                        'pending_direktur' => 'Menunggu Direktur',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        'archived' => 'Diarsipkan',
                        default => $state,
                    })
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('code_number')
                    ->label('Nomor Kode')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    ->visibleFrom('md'),

                TextColumn::make('title')
                    ->label('Judul Dokumen')
                    ->view('filament.tables.columns.document-mobile-card')
                    ->grow()
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->wrap(),

                TextColumn::make('document_type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'file' => 'success',
                        'form' => 'primary',
                        'hybrid' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'file' => 'Berkas',
                        'form' => 'Formulir',
                        'hybrid' => 'Keduanya',
                        default => $state,
                    })
                    ->visibleFrom('md'),

                TextColumn::make('department.name')
                    ->label('Departemen')
                    ->sortable()
                    ->toggleable()
                    ->visibleFrom('lg'),

                TextColumn::make('unit.name')
                    ->label('Unit')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—')
                    ->visibleFrom('lg'),

                TextColumn::make('version')
                    ->label('Versi')
                    ->badge()
                    ->color('warning')
                    ->toggleable()
                    ->placeholder('—')
                    ->visibleFrom('md'),

                TextColumn::make('user.name')
                    ->label('Diajukan Oleh')
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('created_at')
                    ->label('Tanggal Submit')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->visibleFrom('md'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(Document::getStatuses()),

                SelectFilter::make('department')
                    ->label('Departemen')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->button()
                    ->outlined()
                    ->size('xs')
                    ->color('gray'),

                ActionGroup::make([
                    // Approve action for Kabid
                    Action::make('approve_kabid')
                        ->label('Setujui Dokumen')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->authorize('review')
                        ->visible(function (Document $record) {
                            /** @var User $user */
                            $user = Auth::user();

                            return $record->status === Document::STATUS_PENDING_KABID
                                && ($user->hasRole('kabid') || $record->reviewer_id === $user->id);
                        })
                        ->modalDescription('Dokumen ini akan diteruskan ke Direktur untuk keputusan akhir.')
                        ->modalSubmitActionLabel('Ya, Setujui')
                        ->modalCancelActionLabel('Batal')
                        ->form([
                            Textarea::make('kabid_notes')
                                ->label('Catatan (opsional)')
                                ->placeholder('Tambahkan catatan untuk Direktur...')
                                ->rows(3),
                        ])
                        ->action(function (Document $record, array $data) {
                            /** @var User $user */
                            $user = Auth::user();
                            $record->approveByKabid($user, $data['kabid_notes'] ?? null);

                            \Filament\Notifications\Notification::make()
                                ->title('Dokumen disetujui')
                                ->success()
                                ->send();
                        }),

                    // Reject action for Kabid
                    Action::make('reject_kabid')
                        ->label('Tolak Dokumen')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->authorize('review')
                        ->visible(function (Document $record) {
                            /** @var User $user */
                            $user = Auth::user();

                            return $record->status === Document::STATUS_PENDING_KABID
                                && ($user->hasRole('kabid') || $record->reviewer_id === $user->id);
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Tolak Dokumen')
                        ->modalDescription('Dokumen akan ditolak dan staff akan diberitahu.')
                        ->modalSubmitActionLabel('Ya, Tolak')
                        ->modalCancelActionLabel('Batal')
                        ->form([
                            Textarea::make('kabid_notes')
                                ->label('Alasan Penolakan')
                                ->placeholder('Jelaskan alasan penolakan...')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function (Document $record, array $data) {
                            /** @var User $user */
                            $user = Auth::user();
                            $record->rejectByKabid($user, $data['kabid_notes']);

                            \Filament\Notifications\Notification::make()
                                ->title('Dokumen ditolak')
                                ->danger()
                                ->send();
                        }),

                    // Approve action for Direktur
                    Action::make('approve_direktur')
                        ->label('Setujui Dokumen (Final)')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->authorize('review')
                        ->visible(function (Document $record) {
                            /** @var User $user */
                            $user = Auth::user();

                            return $record->status === Document::STATUS_PENDING_DIREKTUR
                                && ($user->hasRole('direktur') || $record->reviewer_id === $user->id);
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Setujui Dokumen — Keputusan Akhir')
                        ->modalDescription('Dokumen akan disetujui dan status menjadi APPROVED.')
                        ->modalSubmitActionLabel('Ya, Setujui')
                        ->modalCancelActionLabel('Batal')
                        ->form([
                            Textarea::make('direktur_notes')
                                ->label('Catatan (opsional)')
                                ->placeholder('Tambahkan catatan...')
                                ->rows(3),
                        ])
                        ->action(function (Document $record, array $data) {
                            /** @var User $user */
                            $user = Auth::user();
                            $record->approveByDirektur($user, $data['direktur_notes'] ?? null);

                            \Filament\Notifications\Notification::make()
                                ->title('Dokumen disetujui final')
                                ->success()
                                ->send();
                        }),

                    // Reject action for Direktur
                    Action::make('reject_direktur')
                        ->label('Tolak Dokumen (Final)')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->authorize('review')
                        ->visible(function (Document $record) {
                            /** @var User $user */
                            $user = Auth::user();

                            return $record->status === Document::STATUS_PENDING_DIREKTUR
                                && ($user->hasRole('direktur') || $record->reviewer_id === $user->id);
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Tolak Dokumen — Keputusan Akhir')
                        ->modalDescription('Dokumen akan ditolak dan staff akan diberitahu.')
                        ->modalSubmitActionLabel('Ya, Tolak')
                        ->modalCancelActionLabel('Batal')
                        ->form([
                            Textarea::make('direktur_notes')
                                ->label('Alasan Penolakan')
                                ->placeholder('Jelaskan alasan penolakan...')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function (Document $record, array $data) {
                            /** @var User $user */
                            $user = Auth::user();
                            $record->rejectByDirektur($user, $data['direktur_notes']);

                            \Filament\Notifications\Notification::make()
                                ->title('Dokumen ditolak final')
                                ->danger()
                                ->send();
                        }),
                ])
                    ->label('Review')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->button()
                    ->size('xs')
                    ->color('primary')
                    ->dropdownPlacement('bottom-end')
                    ->dropdownWidth('sm')
                    ->dropdownTeleport(true)
                    ->visible(function (Document $record) {
                        /** @var User $user */
                        $user = Auth::user();

                        return ($record->status === Document::STATUS_PENDING_KABID && $user->hasRole('kabid')) ||
                            ($record->status === Document::STATUS_PENDING_DIREKTUR && $user->hasRole('direktur'));
                    }),

            ])

            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviewDocuments::route('/'),
            'view' => Pages\ViewReviewDocument::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->forReviewer();
    }
}
