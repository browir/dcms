<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Models\User;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(2)
            ->components([

                /* ─── INFORMASI AKUN ─── */
                Section::make('Informasi Akun')
                    ->icon('heroicon-o-user-circle')
                    ->iconColor('primary')
                    ->description('Data identitas dan kredensial pengguna')
                    ->columnSpan(1)
                    ->extraAttributes(['style' => 'height: 100%; display: flex; flex-direction: column;'])
                    ->schema([
                        ImageEntry::make('avatar_url')
                            ->label('Foto Profil')
                            ->circular()
                            ->disk('public')
                            ->defaultImageUrl(fn (User $record): string => filament()->getUserAvatarUrl($record)),

                        TextEntry::make('name')
                            ->label('Nama Lengkap')
                            ->icon('heroicon-o-user')
                            ->iconColor('primary')
                            ->weight('bold')
                            ->size('lg'),

                        TextEntry::make('username')
                            ->label('Username')
                            ->icon('heroicon-o-at-symbol')
                            ->iconColor('gray')
                            ->copyable()
                            ->copyMessage('Username disalin!')
                            ->copyMessageDuration(1500)
                            ->placeholder('—'),

                        TextEntry::make('email')
                            ->label('Alamat Email')
                            ->icon('heroicon-o-envelope')
                            ->iconColor('primary')
                            ->copyable()
                            ->copyMessage('Email disalin!')
                            ->copyMessageDuration(1500),

                        TextEntry::make('phone')
                            ->label('No. Telepon')
                            ->icon('heroicon-o-phone')
                            ->iconColor('gray')
                            ->copyable()
                            ->copyMessage('Nomor telepon disalin!')
                            ->placeholder('Belum diisi'),
                    ]),

                /* ─── STATUS & PERAN ─── */
                Section::make('Status & Peran')
                    ->icon('heroicon-o-shield-check')
                    ->iconColor('success')
                    ->description('Status akun dan hak akses pengguna')
                    ->columnSpan(1)
                    ->extraAttributes(['style' => 'height: 100%; display: flex; flex-direction: column;'])
                    ->schema([
                        IconEntry::make('is_active')
                            ->label('Status Akun')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),

                        TextEntry::make('roles.name')
                            ->label('Peran / Role')
                            ->icon('heroicon-o-key')
                            ->badge()
                            ->color('primary')
                            ->separator(', ')
                            ->placeholder('Belum memiliki peran'),

                        TextEntry::make('email_verified_at')
                            ->label('Email Terverifikasi')
                            ->icon(fn (User $record): string => $record->email_verified_at
                                ? 'heroicon-o-check-badge'
                                : 'heroicon-o-exclamation-triangle')
                            ->iconColor(fn (User $record): string => $record->email_verified_at
                                ? 'success'
                                : 'warning')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('Belum terverifikasi'),
                    ]),

                /* ─── ORGANISASI ─── */
                Section::make('Organisasi')
                    ->icon('heroicon-o-building-office-2')
                    ->iconColor('indigo')
                    ->description('Struktur organisasi tempat pengguna bernaung')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('company.name')
                            ->label('Perusahaan')
                            ->icon('heroicon-o-building-office')
                            ->iconColor('primary')
                            ->placeholder('Belum ditentukan'),

                        TextEntry::make('department.name')
                            ->label('Departemen')
                            ->icon('heroicon-o-building-office-2')
                            ->iconColor('primary')
                            ->placeholder('Belum ditentukan'),

                        TextEntry::make('unit.name')
                            ->label('Unit')
                            ->icon('heroicon-o-user-group')
                            ->iconColor('gray')
                            ->placeholder('Belum ditentukan'),
                    ])->columns(3),

                /* ─── RIWAYAT ─── */
                Section::make('Riwayat')
                    ->icon('heroicon-o-clock')
                    ->iconColor('gray')
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Dibuat Pada')
                            ->icon('heroicon-o-calendar-days')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('—'),

                        TextEntry::make('updated_at')
                            ->label('Terakhir Diperbarui')
                            ->icon('heroicon-o-arrow-path')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('—'),
                    ]),
            ]);
    }
}
