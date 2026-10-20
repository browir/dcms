<?php

namespace App\Filament\Admin\Resources\Documents\Schemas;

use App\Enum\JenisSurat;
use App\Models\Document;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DocumentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(2)
            ->components([

                /* ─── STATUS & TIPE ─── */
                Section::make('Status Dokumen')
                    ->icon('heroicon-o-information-circle')
                    ->iconColor('primary')
                    ->description('Status dan tipe dokumen saat ini')
                    ->columnSpan(1)
                    ->extraAttributes(['style' => 'height: 100%; display: flex; flex-direction: column;'])
                    ->schema([
                        TextEntry::make('status')
                            ->label('Status')
                            ->icon(fn (Document $record): string => match ($record->status) {
                                'draft' => 'heroicon-o-pencil',
                                'pending_kabid' => 'heroicon-o-clock',
                                'pending_direktur' => 'heroicon-o-clock',
                                'approved' => 'heroicon-o-check-circle',
                                'rejected' => 'heroicon-o-x-circle',
                                'archived' => 'heroicon-o-archive-box',
                                default => 'heroicon-o-question-mark-circle',
                            })
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending_kabid' => 'warning',
                                'pending_direktur' => 'info',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'archived' => 'gray',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'draft' => 'Pending',
                                'pending_kabid' => 'Menunggu Kabid',
                                'pending_direktur' => 'Menunggu Direktur',
                                'approved' => 'Disetujui',
                                'rejected' => 'Ditolak',
                                'archived' => 'Diarsipkan',
                                default => $state,
                            }),

                        TextEntry::make('document_type')
                            ->label('Tipe Dokumen')
                            ->icon(fn (Document $record): string => match ($record->document_type) {
                                'file' => 'heroicon-o-paper-clip',
                                'form' => 'heroicon-o-pencil-square',
                                'hybrid' => 'heroicon-o-squares-plus',
                                default => 'heroicon-o-document',
                            })
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'file' => 'success',
                                'form' => 'info',
                                'hybrid' => 'warning',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'file' => 'Berkas',
                                'form' => 'Formulir',
                                'hybrid' => 'Gabungan',
                                default => $state,
                            }),

                        TextEntry::make('is_mandatory_read')
                            ->label('Wajib Dibaca')
                            ->badge()
                            ->icon(fn (Document $record): string => $record->is_mandatory_read
                                ? 'heroicon-o-shield-check'
                                : 'heroicon-o-shield-exclamation')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Wajib Dibaca' : 'Tidak Diwajibkan')
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                    ]),

                /* ─── IDENTITAS ─── */
                Section::make('Identitas Dokumen')
                    ->icon('heroicon-o-document-text')
                    ->iconColor('primary')
                    ->description('Informasi utama dokumen')
                    ->columnSpan(1)
                    ->extraAttributes(['style' => 'height: 100%; display: flex; flex-direction: column;'])
                    ->schema([
                        TextEntry::make('title')
                            ->label('Judul Dokumen')
                            ->icon('heroicon-o-document-text')
                            ->iconColor('primary')
                            ->weight('bold')
                            ->size('lg'),

                        TextEntry::make('code_number')
                            ->label('Nomor Kode')
                            ->icon('heroicon-o-hashtag')
                            ->iconColor('primary')
                            ->badge()
                            ->color('primary')
                            ->copyable()
                            ->copyMessage('Nomor kode disalin!')
                            ->copyMessageDuration(1500),

                        TextEntry::make('jenis_surat')
                            ->label('Jenis Surat')
                            ->icon('heroicon-o-tag')
                            ->iconColor('primary')
                            ->formatStateUsing(fn (?JenisSurat $state): ?string => $state ? "{$state->value} — {$state->label()}" : null)
                            ->placeholder('—')
                            ->visible(fn ($record): bool => filled($record?->jenis_surat)),

                        TextEntry::make('tanggal_surat')
                            ->label('Tanggal Surat')
                            ->icon('heroicon-o-calendar')
                            ->iconColor('primary')
                            ->date('d M Y')
                            ->visible(fn ($record): bool => filled($record?->tanggal_surat)),

                        TextEntry::make('version')
                            ->label('Versi')
                            ->icon('heroicon-o-tag')
                            ->iconColor('gray')
                            ->badge()
                            ->color('gray'),

                        TextEntry::make('description')
                            ->label('Deskripsi')
                            ->icon('heroicon-o-chat-bubble-left-ellipsis')
                            ->placeholder('Tidak ada deskripsi'),

                        TextEntry::make('expires_at')
                            ->label('Berlaku Hingga')
                            ->icon(fn (Document $record): ?string => match (true) {
                                $record->is_expired => 'heroicon-o-exclamation-triangle',
                                $record->is_expiring_soon => 'heroicon-o-clock',
                                filled($record->expires_at) => 'heroicon-o-check-circle',
                                default => 'heroicon-o-calendar',
                            })
                            ->badge()
                            ->date('d M Y')
                            ->color(fn (Document $record): string => match (true) {
                                $record->is_expired => 'danger',
                                $record->is_expiring_soon => 'warning',
                                filled($record->expires_at) => 'success',
                                default => 'gray',
                            })
                            ->formatStateUsing(function (Document $record): string {
                                if (! $record->expires_at) {
                                    return 'Tidak ada batas waktu';
                                }
                                if ($record->is_expired) {
                                    return 'Kedaluwarsa: '.$record->expires_at->format('d M Y');
                                }
                                if ($record->is_expiring_soon) {
                                    $days = today()->diffInDays($record->expires_at);

                                    return "{$days} hari lagi · ".$record->expires_at->format('d M Y');
                                }

                                return $record->expires_at->format('d M Y');
                            })
                            ->placeholder('Tidak ada batas waktu'),
                    ]),

                /* ─── BERKAS ─── */
                Section::make('Berkas Dokumen')
                    ->icon('heroicon-o-paper-clip')
                    ->iconColor('success')
                    ->description('File yang diunggah bersama dokumen ini')
                    ->columnSpan(1)
                    ->extraAttributes(['style' => 'height: 100%; display: flex; flex-direction: column;'])
                    ->visible(fn (Document $record): bool => filled($record->file_path))
                    ->schema([
                        ViewEntry::make('file_path')
                            ->hiddenLabel()
                            ->view('filament.infolists.document-file-preview'),
                    ]),

                /* ─── ORGANISASI ─── */
                Section::make('Organisasi')
                    ->icon('heroicon-o-building-office-2')
                    ->iconColor('indigo')
                    ->description('Struktur organisasi yang menaungi dokumen')
                    ->columnSpan(1)
                    ->extraAttributes(['style' => 'height: 100%; display: flex; flex-direction: column;'])
                    ->schema([
                        TextEntry::make('company.name')
                            ->label('Perusahaan')
                            ->icon('heroicon-o-building-office')
                            ->iconColor('primary')
                            ->placeholder('—'),

                        TextEntry::make('department.name')
                            ->label('Departemen')
                            ->icon('heroicon-o-building-office-2')
                            ->iconColor('primary')
                            ->placeholder('—'),

                        TextEntry::make('unit.name')
                            ->label('Unit')
                            ->icon('heroicon-o-user-group')
                            ->iconColor('gray')
                            ->placeholder('—'),

                        TextEntry::make('category.name')
                            ->label('Kategori')
                            ->icon('heroicon-o-tag')
                            ->iconColor('gray')
                            ->badge()
                            ->color('gray')
                            ->placeholder('—'),

                        TextEntry::make('user.name')
                            ->label('Dibuat Oleh')
                            ->icon('heroicon-o-user-circle')
                            ->iconColor('primary')
                            ->placeholder('—'),

                        TextEntry::make('updatedByUser.name')
                            ->label('Terakhir Diedit Oleh')
                            ->icon('heroicon-o-pencil-square')
                            ->iconColor('warning')
                            ->placeholder('Belum pernah diedit')
                            ->visible(fn (Document $record): bool => filled($record->updated_by)),

                        TextEntry::make('allowedUnits.name')
                            ->label('Akses Granular Unit')
                            ->icon('heroicon-o-key')
                            ->badge()
                            ->color('success')
                            ->placeholder('Hanya unit utama'),
                    ]),

                /* ─── REVIEW KABID ─── */
                Section::make('Review Kepala Bidang')
                    ->icon('heroicon-o-check-badge')
                    ->iconColor('warning')
                    ->description('Hasil review dari Kepala Bidang')
                    ->columnSpan(1)
                    ->extraAttributes(['style' => 'height: 100%; display: flex; flex-direction: column;'])
                    ->schema([
                        TextEntry::make('kabidReviewer.name')
                            ->label('Direview oleh')
                            ->icon('heroicon-o-user-circle')
                            ->iconColor('warning')
                            ->placeholder('Belum direview'),

                        TextEntry::make('kabid_reviewed_at')
                            ->label('Tanggal Review')
                            ->icon('heroicon-o-calendar')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('—'),

                        TextEntry::make('kabid_notes')
                            ->label('Catatan')
                            ->icon('heroicon-o-chat-bubble-left')
                            ->placeholder('Tidak ada catatan'),
                    ]),

                /* ─── REVIEW DIREKTUR ─── */
                Section::make('Review Direktur')
                    ->icon('heroicon-o-check-badge')
                    ->iconColor('success')
                    ->description('Hasil review dari Direktur')
                    ->columnSpan(1)
                    ->extraAttributes(['style' => 'height: 100%; display: flex; flex-direction: column;'])
                    ->schema([
                        TextEntry::make('direkturReviewer.name')
                            ->label('Direview oleh')
                            ->icon('heroicon-o-user-circle')
                            ->iconColor('success')
                            ->placeholder('Belum direview'),

                        TextEntry::make('direktur_reviewed_at')
                            ->label('Tanggal Review')
                            ->icon('heroicon-o-calendar')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('—'),

                        TextEntry::make('direktur_notes')
                            ->label('Catatan')
                            ->icon('heroicon-o-chat-bubble-left')
                            ->placeholder('Tidak ada catatan'),
                    ]),

                /* ─── KONTEN ─── */
                Section::make('Konten Dokumen')
                    ->icon('heroicon-o-document')
                    ->iconColor('gray')
                    ->description('Isi dokumen yang dibuat dari formulir')
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull()
                    ->visible(fn (Document $record): bool => filled($record->content))
                    ->schema([
                        TextEntry::make('content')
                            ->label('')
                            ->html()
                            ->placeholder('Tidak ada konten'),
                    ]),

                /* ─── RIWAYAT PENOLAKAN ─── */
                Section::make('Riwayat Penolakan')
                    ->icon('heroicon-o-x-circle')
                    ->iconColor('danger')
                    ->description('Log seluruh penolakan yang pernah terjadi pada dokumen ini')
                    ->columnSpanFull()
                    ->collapsible()
                    ->visible(fn (Document $record): bool => $record->rejections()->exists())
                    ->schema([
                        \Filament\Infolists\Components\RepeatableEntry::make('rejections')
                            ->label('')
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label('Ditolak Oleh')
                                    ->icon('heroicon-o-user-circle')
                                    ->iconColor('danger'),

                                TextEntry::make('role')
                                    ->label('Jabatan')
                                    ->badge()
                                    ->color('danger')
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'kabid' => 'Kepala Bidang',
                                        'direktur' => 'Direktur',
                                        default => ucfirst($state),
                                    }),

                                TextEntry::make('created_at')
                                    ->label('Waktu')
                                    ->dateTime('d M Y, H:i')
                                    ->icon('heroicon-o-clock'),

                                TextEntry::make('notes')
                                    ->label('Alasan Penolakan')
                                    ->icon('heroicon-o-chat-bubble-left')
                                    ->columnSpanFull()
                                    ->placeholder('—'),
                            ])
                            ->columns(3)
                            ->columnSpanFull(),
                    ]),

                /* ─── RIWAYAT ─── */
                Section::make('Riwayat')
                    ->icon('heroicon-o-clock')
                    ->iconColor('gray')
                    ->collapsed()
                    ->collapsible()
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Dibuat')
                            ->icon('heroicon-o-calendar-days')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('—'),

                        TextEntry::make('updated_at')
                            ->label('Terakhir Diperbarui')
                            ->icon('heroicon-o-arrow-path')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('—'),

                        TextEntry::make('deleted_at')
                            ->label('Dihapus Pada')
                            ->icon('heroicon-o-trash')
                            ->iconColor('danger')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('—')
                            ->visible(fn (Document $record): bool => $record->trashed()),
                    ]),

                /* ─── DISKUSI & Q&A ─── */
                Section::make('Diskusi & Q&A')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->iconColor('primary')
                    ->description('Ajukan pertanyaan atau diskusikan dokumen ini')
                    ->columnSpanFull()
                    ->hidden()
                    ->schema([
                        Livewire::make(\App\Livewire\DocumentDiscussionThread::class)
                            ->key(fn (Document $record) => 'discussion-'.$record->id)
                            ->data(fn (Document $record) => ['documentId' => $record->id])
                            ->lazy()
                            ->columnSpanFull(),
                    ]),

            ]);
    }
}
