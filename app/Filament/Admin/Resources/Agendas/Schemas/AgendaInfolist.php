<?php

namespace App\Filament\Admin\Resources\Agendas\Schemas;

use App\Models\Agenda;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AgendaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make('Kegiatan')
                ->icon('heroicon-o-megaphone')
                ->iconColor('primary')
                ->columnSpan(2)
                ->columns(2)
                ->schema([
                    TextEntry::make('title')->label('Nama kegiatan')->weight('bold')->size('lg')->columnSpanFull(),
                    TextEntry::make('schedule')->label('Waktu')->icon('heroicon-o-clock')
                        ->state(fn (Agenda $record): string => $record->scheduleLabel()),
                    TextEntry::make('place')->label('Tempat')->icon('heroicon-o-map-pin')
                        ->state(fn (Agenda $record): string => $record->placeName()),
                    TextEntry::make('kategori')->label('Jenis kegiatan')
                        ->formatStateUsing(fn (?string $state): string => Agenda::KATEGORI[$state] ?? '—')->placeholder('—'),
                    TextEntry::make('organizer')->label('Penyelenggara')
                        ->state(fn (Agenda $record): string => collect([$record->unit?->name, $record->company?->name])->filter()->implode(' · ')),
                    TextEntry::make('description')->label('Keterangan')->placeholder('—')->columnSpanFull(),
                ]),

            Section::make('Pengumuman')
                ->icon('heroicon-o-bell-alert')
                ->iconColor('warning')
                ->columnSpan(1)
                ->schema([
                    TextEntry::make('status')->label('Status')->badge()
                        ->formatStateUsing(fn (string $state): string => ['scheduled' => 'Terjadwal', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'][$state] ?? $state)
                        ->color(fn (string $state): string => ['scheduled' => 'info', 'completed' => 'gray', 'cancelled' => 'danger'][$state] ?? 'gray'),
                    TextEntry::make('audience')->label('Jangkauan')
                        ->formatStateUsing(fn (string $state): string => Agenda::audienceOptions()[$state] ?? $state),
                    TextEntry::make('letterNumber.nomor')->label('Surat pemberitahuan')
                        ->fontFamily('mono')->copyable()->placeholder('Belum dibuat'),
                    TextEntry::make('announced_at')->label('Diumumkan di aplikasi')
                        ->dateTime('d M Y H:i')->placeholder('Belum'),
                    TextEntry::make('whatsapp_sent_at')->label('Terkirim ke grup WhatsApp')
                        ->dateTime('d M Y H:i')->placeholder('Belum'),
                    TextEntry::make('creator.name')->label('Dicatat oleh')->placeholder('—'),
                ]),
        ]);
    }
}
