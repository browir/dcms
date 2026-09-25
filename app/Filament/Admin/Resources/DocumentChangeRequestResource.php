<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\DocumentChangeRequestResource\Pages\CreateDocumentChangeRequest;
use App\Filament\Admin\Resources\DocumentChangeRequestResource\Pages\ListDocumentChangeRequests;
use App\Filament\Admin\Resources\DocumentChangeRequestResource\Pages\ViewDocumentChangeRequest;
use App\Models\Document;
use App\Models\DocumentChangeRequest;
use App\Notifications\ChangeRequestStatusUpdatedNotification;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class DocumentChangeRequestResource extends Resource
{
    protected static ?string $model = DocumentChangeRequest::class;

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Dokumen';

    protected static ?int $navigationSort = 11;

    protected static ?string $modelLabel = 'Usulan Revisi';

    protected static ?string $pluralModelLabel = 'Usulan Revisi SOP';

    protected static ?string $navigationLabel = 'Usulan Revisi';

    public static function getNavigationBadge(): ?string
    {
        $pending = \Illuminate\Support\Facades\Cache::remember(
            'nav_badge_change_requests_pending',
            30,
            fn () => \App\Models\DocumentChangeRequest::where('status', 'pending')->count()
        );

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                \Illuminate\Database\Eloquent\SoftDeletingScope::class,
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Detail Usulan Revisi')
                ->icon('heroicon-o-pencil-square')
                ->iconColor('primary')
                ->columnSpanFull()
                ->schema([
                    Select::make('document_id')
                        ->label('Dokumen SOP')
                        ->options(Document::where('status', 'approved')->pluck('title', 'id'))
                        ->searchable()
                        ->required()
                        ->placeholder('Pilih dokumen yang ingin direvisi'),

                    Textarea::make('proposed_change')
                        ->label('Usulan Perubahan')
                        ->placeholder('Jelaskan usulan perubahan yang Anda ajukan secara spesifik...')
                        ->required()
                        ->rows(4),

                    FileUpload::make('attachment_path')
                        ->label('Lampiran (Opsional)')
                        ->disk('public')
                        ->directory('change-requests')
                        ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                        ->maxSize(10240)
                        ->nullable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                /** @var \App\Models\User|null $user */
                $user = Auth::user();
                // Regular users only see their own
                if (! $user?->hasAnyRole(['super_admin', 'kabid', 'direktur'])) {
                    $query->where('user_id', $user?->id);
                }
            })
            ->columns([
                // Mobile card
                TextColumn::make('document.title')
                    ->label('Usulan Revisi')
                    ->searchable()
                    ->sortable()
                    ->view('filament.tables.columns.change-request-mobile-card')
                    ->grow(),

                // Desktop columns
                TextColumn::make('index')
                    ->label('No.')
                    ->rowIndex()
                    ->alignCenter()
                    ->width('48px')
                    ->visibleFrom('md'),

                TextColumn::make('user.name')
                    ->label('Diajukan Oleh')
                    ->icon('heroicon-o-user-circle')
                    ->iconColor('gray')
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->icon(fn (string $state): string => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'approved' => 'heroicon-o-check-circle',
                        'rejected' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default => $state,
                    })
                    ->visibleFrom('md'),

                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar')
                    ->iconColor('gray')
                    ->visibleFrom('md'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ])
                    ->native(false),
                \Filament\Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make()->icon('heroicon-o-eye')->label('Lihat')->button()->outlined()->size('xs')->color('gray'),

                \Filament\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->label('Hapus')
                    ->button()
                    ->outlined()
                    ->size('xs')
                    ->color('danger'),

                \Filament\Actions\RestoreAction::make()
                    ->button()
                    ->outlined()
                    ->size('xs')
                    ->color('success'),

                \Filament\Actions\ForceDeleteAction::make()
                    ->button()
                    ->outlined()
                    ->size('xs')
                    ->color('danger'),

                \Filament\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->button()
                    ->size('xs')
                    ->color('primary')
                    ->visible(function (DocumentChangeRequest $record): bool {
                        /** @var \App\Models\User|null $user */
                        $user = Auth::user();

                        return $record->status === 'pending'
                            && ($user?->hasAnyRole(['super_admin', 'kabid', 'direktur']) ?? false);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Usulan Revisi')
                    ->modalDescription('Dokumen terkait akan dikembalikan ke alur persetujuan (menunggu review Kabid).')
                    ->action(function (DocumentChangeRequest $record) {
                        $record->update([
                            'status' => 'approved',
                            'reviewed_by' => Auth::id(),
                            'reviewed_at' => now(),
                        ]);

                        // Jika dokumen induk masih berstatus rejected, masukkan kembali ke alur approval
                        $document = $record->document;
                        if ($document && $document->status === \App\Models\Document::STATUS_REJECTED) {
                            $document->resubmit();
                        }

                        try {
                            $record->user->notify(new ChangeRequestStatusUpdatedNotification($record));
                        } catch (\Throwable $e) {
                            logger()->error('Failed to send ChangeRequestStatusUpdatedNotification: '.$e->getMessage());
                        }
                        \Filament\Notifications\Notification::make()->title('Usulan disetujui — dokumen dikembalikan ke alur review')->success()->send();
                    }),

                \Filament\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->button()
                    ->size('xs')
                    ->color('danger')
                    ->visible(function (DocumentChangeRequest $record): bool {
                        /** @var \App\Models\User|null $user */
                        $user = Auth::user();

                        return $record->status === 'pending'
                            && ($user?->hasAnyRole(['super_admin', 'kabid', 'direktur']) ?? false);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Usulan Revisi')
                    ->modalDescription('Apakah Anda yakin ingin menolak usulan revisi ini?')
                    ->action(function (DocumentChangeRequest $record) {
                        $record->update([
                            'status' => 'rejected',
                            'reviewed_by' => Auth::id(),
                            'reviewed_at' => now(),
                        ]);
                        try {
                            $record->user->notify(new ChangeRequestStatusUpdatedNotification($record));
                        } catch (\Throwable $e) {
                            logger()->error('Failed to send ChangeRequestStatusUpdatedNotification: '.$e->getMessage());
                        }
                        \Filament\Notifications\Notification::make()->title('Usulan ditolak')->danger()->send();
                    }),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                    \Filament\Actions\RestoreBulkAction::make(),
                    \Filament\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->emptyStateIcon('heroicon-o-pencil-square')
            ->emptyStateHeading('Belum ada usulan revisi')
            ->emptyStateDescription('Ajukan usulan revisi SOP menggunakan tombol di atas.');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Detail Usulan')
                ->icon('heroicon-o-pencil-square')
                ->schema([
                    TextEntry::make('document.title')->label('Dokumen SOP')->icon('heroicon-o-document-text')->iconColor('primary'),
                    TextEntry::make('proposed_change')->label('Usulan Perubahan'),
                    TextEntry::make('attachment_path')
                        ->label('Lampiran')
                        ->icon('heroicon-o-paper-clip')
                        ->formatStateUsing(fn ($state) => $state ? basename($state) : 'Tidak ada lampiran')
                        ->placeholder('Tidak ada lampiran'),
                ]),
            Section::make('Status & Peninjau')
                ->icon('heroicon-o-check-badge')
                ->schema([
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'pending' => 'warning',
                            'approved' => 'success',
                            'rejected' => 'danger',
                            default => 'gray',
                        })
                        ->formatStateUsing(fn (string $state): string => match ($state) {
                            'pending' => 'Menunggu',
                            'approved' => 'Disetujui',
                            'rejected' => 'Ditolak',
                            default => $state,
                        }),
                    TextEntry::make('user.name')->label('Diajukan Oleh')->icon('heroicon-o-user-circle'),
                    TextEntry::make('reviewer.name')->label('Ditinjau Oleh')->icon('heroicon-o-user-circle')->placeholder('Belum ditinjau'),
                    TextEntry::make('reviewed_at')->label('Tanggal Tinjauan')->dateTime('d M Y, H:i')->placeholder('—'),
                    TextEntry::make('created_at')->label('Tanggal Pengajuan')->dateTime('d M Y, H:i'),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentChangeRequests::route('/'),
            'create' => CreateDocumentChangeRequest::route('/create'),
            'view' => ViewDocumentChangeRequest::route('/{record}'),
        ];
    }
}
