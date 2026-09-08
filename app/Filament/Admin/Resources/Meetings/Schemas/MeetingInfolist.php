<?php

namespace App\Filament\Admin\Resources\Meetings\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MeetingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                // WRAPPER UTAMA (Sekarang mencakup Informasi Utama, Lokasi, Konteks, dan Sistem)
                Section::make()
                    ->extraAttributes([
                        'class' => 'flex justify-center gap-6 flex-wrap mb-6 bg-transparent',
                    ])
                    ->schema([

                        // 1. Informasi Utama
                        Section::make('Informasi Utama')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                TextEntry::make('title')
                                    ->label('Judul Rapat')
                                    ->size('2xl')
                                    ->weight('bold')
                                    ->columnSpanFull(),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->size('md')
                                    ->formatStateUsing(fn ($state) => match ($state) {
                                        'scheduled' => 'Terjadwal',
                                        'completed' => 'Selesai',
                                        'cancelled' => 'Batal',
                                        default => ucfirst($state),
                                    })
                                    ->color(fn ($state) => match ($state) {
                                        'draft' => 'gray',
                                        'scheduled' => 'blue',
                                        'ongoing' => 'warning',
                                        'completed' => 'gray',
                                        'cancelled' => 'danger',
                                        default => 'gray',
                                    }),
                            ])
                            ->extraAttributes([
                                'class' => 'bg-purple-50 p-5 rounded-xl shadow-md border-l-4 border-l-purple-400 w-80 flex flex-col gap-4',
                            ]),

                        // 2. Lokasi & Waktu
                        Section::make('Lokasi & Waktu')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                TextEntry::make('date_time')
                                    ->label('Jadwal')
                                    ->icon('heroicon-o-calendar')
                                    ->dateTime('d F Y, H:i'),

                                TextEntry::make('end_time')
                                    ->label('Berakhir')
                                    ->icon('heroicon-o-clock')
                                    ->dateTime('d F Y, H:i')
                                    ->placeholder('Belum ditentukan'),

                                TextEntry::make('location')
                                    ->label('Tempat')
                                    ->icon('heroicon-o-map-pin')
                                    ->placeholder('Belum ditentukan'),

                                TextEntry::make('creator.name')
                                    ->label('Dibuat Oleh')
                                    ->icon('heroicon-o-user-circle'),
                            ])
                            ->extraAttributes([
                                'class' => 'bg-indigo-50 p-4 rounded-xl shadow-sm w-80 flex flex-col gap-3',
                            ]),

                        // 3. Konteks Rapat
                        Section::make('Konteks Rapat')
                            ->icon('heroicon-o-clipboard')
                            ->schema([
                                TextEntry::make('agenda')
                                    ->label('Agenda Pembahasan')
                                    ->prose()
                                    ->columnSpanFull(),

                                TextEntry::make('participants.name')
                                    ->label('Peserta Terlibat')
                                    ->badge()
                                    ->separator(', ')
                                    ->columnSpanFull(),
                            ])
                            ->extraAttributes([
                                'class' => 'bg-emerald-50 p-4 rounded-xl shadow-sm w-80 flex flex-col gap-3',
                            ]),

                        // 4. Informasi Sistem (Sekarang di dalam Container yang sama)
                        Section::make('Informasi Sistem')
                            ->icon('heroicon-o-cog')
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Dibuat')
                                    ->dateTime()
                                    ->size('xs')
                                    ->color('gray'),

                                TextEntry::make('updated_at')
                                    ->label('Terakhir Diperbarui')
                                    ->dateTime()
                                    ->size('xs')
                                    ->color('gray'),
                            ])
                            ->extraAttributes([
                                'class' => 'bg-yellow-50 p-4 rounded-xl shadow-sm w-80 flex flex-col gap-3',
                            ]),
                    ]),

                // MASTER SECTION UNTUK HASIL RAPAT (Terpisah di bawah)
                Section::make('Hasil & Dokumentasi Rapat')
                    ->icon('heroicon-o-document-check')
                    ->schema([

                        Section::make('Notulensi Rapat')
                            ->icon('heroicon-o-pencil')
                            ->description('Catatan hasil rapat dan poin-poin keputusan.')
                            ->schema([
                                TextEntry::make('content')
                                    ->label('Konten')
                                    ->html()
                                    ->prose()
                                    ->placeholder('Notulensi belum tersedia...')
                                    ->columnSpanFull(),
                            ])
                            ->extraAttributes([
                                'class' => 'bg-pink-50 border-dashed border-2 min-h-[400px] p-5 rounded-xl',
                            ]),

                        Section::make('Lampiran Dokumentasi')
                            ->icon('heroicon-o-camera')
                            ->description('Foto atau dokumen pendukung rapat.')
                            ->schema([
                                ImageEntry::make('attachments')
                                    ->label('')
                                    ->disk('public')
                                    ->columnSpanFull(),
                            ])
                            ->visible(fn ($record) => ! empty($record->attachments))
                            ->extraAttributes([
                                'class' => 'bg-blue-50 border-dashed border-2 p-5 rounded-xl mt-4',
                            ]),
                    ])
                    ->extraAttributes([
                        'class' => 'bg-white p-6 rounded-2xl shadow-lg border border-gray-100',
                    ]),
            ]);
    }
}
