<?php

namespace App\Filament\Admin\Resources\Meetings\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
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

                // Detail lengkap
                Section::make('Detail Rapat')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->description('Informasi jadwal, lokasi, dan konteks rapat.')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('date_time')
                            ->label('Tanggal & Waktu Mulai')
                            ->icon('heroicon-o-calendar-days')
                            ->dateTime('l, d F Y • H:i'),

                        TextEntry::make('end_time')
                            ->label('Jam Berakhir')
                            ->icon('heroicon-o-clock')
                            ->dateTime('l, d F Y • H:i')
                            ->placeholder('Belum ditentukan'),

                        TextEntry::make('location')
                            ->label('Lokasi / Ruangan')
                            ->icon('heroicon-o-map-pin')
                            ->placeholder('Belum ditentukan'),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'scheduled' => 'Terjadwal',
                                'completed' => 'Selesai',
                                'cancelled' => 'Batal',
                                default => ucfirst((string) $state),
                            })
                            ->color(fn ($state) => match ($state) {
                                'scheduled' => 'info',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            })
                            ->icon(fn ($state) => match ($state) {
                                'scheduled' => 'heroicon-o-calendar',
                                'completed' => 'heroicon-o-check-circle',
                                'cancelled' => 'heroicon-o-x-circle',
                                default => 'heroicon-o-question-mark-circle',
                            }),

                        TextEntry::make('creator.name')
                            ->label('Dibuat Oleh')
                            ->icon('heroicon-o-user-circle')
                            ->placeholder('—'),

                        TextEntry::make('notulis.name')
                            ->label('Notulis / Pencatat')
                            ->icon('heroicon-o-pencil-square')
                            ->placeholder('Belum ditunjuk'),

                        TextEntry::make('doc_number')
                            ->label('No. Dokumen')
                            ->icon('heroicon-o-hashtag')
                            ->copyable()
                            ->placeholder('—'),

                        TextEntry::make('company.name')
                            ->label('Perusahaan')
                            ->icon('heroicon-o-building-office-2')
                            ->placeholder('—'),

                        TextEntry::make('agenda')
                            ->label('Agenda Pembahasan')
                            ->prose()
                            ->placeholder('Tidak ada agenda tertulis.')
                            ->columnSpanFull(),

                        TextEntry::make('participants.name')
                            ->label('Peserta Terlibat')
                            ->badge()
                            ->color('gray')
                            ->separator(',')
                            ->placeholder('Belum ada peserta.')
                            ->columnSpanFull(),
                    ]),

                // Notulensi
                Section::make('Notulensi Rapat')
                    ->icon('heroicon-o-document-text')
                    ->description('Catatan hasil rapat dan poin-poin keputusan.')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('content')
                            ->hiddenLabel()
                            ->html()
                            ->prose()
                            ->placeholder('Notulensi belum tersedia. Buka "Ubah" untuk mulai mencatat.')
                            ->columnSpanFull(),

                        TextEntry::make('file_path')
                            ->label('Berkas Notulensi')
                            ->icon('heroicon-o-paper-clip')
                            ->formatStateUsing(fn () => 'Lihat berkas notulensi (PDF/Word)')
                            ->url(fn ($record) => route('notulen.view', $record->id))
                            ->openUrlInNewTab()
                            ->color('success')
                            ->weight('semibold')
                            ->visible(fn ($record) => filled($record?->file_path))
                            ->columnSpanFull(),
                    ]),

                // Lampiran
                Section::make('Lampiran Dokumentasi')
                    ->icon('heroicon-o-photo')
                    ->description('Foto atau dokumen pendukung rapat.')
                    ->collapsible()
                    ->visible(fn ($record) => ! empty($record?->attachments))
                    ->schema([
                        ImageEntry::make('attachments')
                            ->hiddenLabel()
                            ->disk('public')
                            ->height(160)
                            ->square()
                            ->columnSpanFull(),
                    ]),

                // Metadata sistem
                Section::make('Informasi Sistem')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->columns(3)
                    ->collapsed()
                    ->collapsible()
                    ->schema([
                        TextEntry::make('id')
                            ->label('ID Rapat')
                            ->badge()
                            ->color('gray'),

                        TextEntry::make('created_at')
                            ->label('Dibuat')
                            ->dateTime('d M Y, H:i')
                            ->color('gray'),

                        TextEntry::make('updated_at')
                            ->label('Terakhir Diperbarui')
                            ->since()
                            ->dateTimeTooltip()
                            ->color('gray'),
                    ]),
            ]);
    }
}
