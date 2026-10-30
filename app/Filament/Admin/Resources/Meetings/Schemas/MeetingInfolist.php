<?php

namespace App\Filament\Admin\Resources\Meetings\Schemas;

use App\Models\Meeting;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MeetingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                // Hero ringkas: judul, status, jadwal, lokasi, notulis, peserta.
                ViewEntry::make('hero')
                    ->view('filament.meetings.meeting-view-hero')
                    ->columnSpanFull(),

                // Konten utama (kiri) + panel samping (kanan); menumpuk di layar kecil.
                Grid::make(['default' => 1, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        Group::make([
                            Section::make('Agenda Pembahasan')
                                ->icon('heroicon-o-list-bullet')
                                ->schema([
                                    TextEntry::make('agenda')
                                        ->hiddenLabel()
                                        ->formatStateUsing(fn (?string $state) => nl2br(e((string) $state)))
                                        ->html()
                                        ->placeholder('Tidak ada agenda tertulis.'),
                                ]),

                            Section::make(fn (?Meeting $record) => 'Action Plan & PIC'.(($n = count($record?->action_items ?? [])) ? " ({$n})" : ''))
                                ->icon('heroicon-o-clipboard-document-check')
                                ->description('Tindak lanjut hasil rapat beserta penanggung jawabnya.')
                                ->schema([
                                    ViewEntry::make('action_items')
                                        ->hiddenLabel()
                                        ->view('filament.meetings.meeting-action-items'),
                                ]),

                            Section::make('Catatan Tambahan')
                                ->icon('heroicon-o-document-text')
                                ->collapsible()
                                ->visible(fn (?Meeting $record) => trim(strip_tags((string) $record?->content)) !== '')
                                ->schema([
                                    TextEntry::make('content')
                                        ->hiddenLabel()
                                        ->html()
                                        ->prose(),
                                ]),

                            Section::make(fn (?Meeting $record) => 'Lampiran Dokumentasi ('.count($record?->attachments ?? []).')')
                                ->icon('heroicon-o-photo')
                                ->description('Klik foto untuk memperbesar.')
                                ->collapsible()
                                ->visible(fn (?Meeting $record) => ! empty($record?->attachments))
                                ->schema([
                                    ViewEntry::make('attachments')
                                        ->hiddenLabel()
                                        ->view('filament.meetings.meeting-attachments'),
                                ]),
                        ])->columnSpan(['default' => 1, 'lg' => 2]),

                        Group::make([
                            Section::make('Berkas Notulensi')
                                ->icon('heroicon-o-paper-clip')
                                ->schema([
                                    ViewEntry::make('file_path')
                                        ->hiddenLabel()
                                        ->view('filament.meetings.meeting-minutes-file'),
                                ]),

                            Section::make(fn (?Meeting $record) => 'Peserta ('.($record?->participants->count() ?? 0).')')
                                ->icon('heroicon-o-user-group')
                                ->schema([
                                    ViewEntry::make('participants')
                                        ->hiddenLabel()
                                        ->view('filament.meetings.meeting-participants'),
                                ]),

                            Section::make('Informasi Dokumen')
                                ->icon('heroicon-o-information-circle')
                                ->collapsible()
                                ->schema([
                                    TextEntry::make('doc_number')
                                        ->label('No. Dokumen')
                                        ->copyable()
                                        ->copyMessage('No. dokumen disalin')
                                        ->placeholder('—'),

                                    TextEntry::make('company.name')
                                        ->label('Perusahaan')
                                        ->placeholder('—'),

                                    TextEntry::make('creator.name')
                                        ->label('Dibuat Oleh')
                                        ->placeholder('—'),

                                    TextEntry::make('created_at')
                                        ->label('Dibuat')
                                        ->dateTime('d M Y, H:i'),

                                    TextEntry::make('updated_at')
                                        ->label('Terakhir Diperbarui')
                                        ->since()
                                        ->dateTimeTooltip(),
                                ]),
                        ])->columnSpan(['default' => 1, 'lg' => 1]),
                    ]),
            ]);
    }
}
