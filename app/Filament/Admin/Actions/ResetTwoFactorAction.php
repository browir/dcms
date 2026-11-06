<?php

namespace App\Filament\Admin\Actions;

use App\Models\User;
use App\Services\TwoFactorService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ResetTwoFactorAction
{
    /**
     * Aksi Filament untuk mereset 2FA pengguna.
     * Dapat digunakan di mana saja: Table action, Header action, dsb.
     *
     * Contoh penggunaan di tabel:
     *   Tables\Actions\Action::make('reset_2fa')
     *       ->label('Reset 2FA')
     *       ...
     *
     * Atau lewat static helper:
     *   ResetTwoFactorAction::make()
     */
    public static function makeTableAction(): \Filament\Tables\Actions\Action
    {
        return \Filament\Tables\Actions\Action::make('reset_2fa')
            ->label('Reset 2FA')
            ->icon('heroicon-o-shield-exclamation')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Reset Verifikasi Dua Faktor')
            ->modalDescription(fn (User $record) =>
                "Tindakan ini akan menonaktifkan 2FA dan mencabut semua perangkat tepercaya milik {$record->name}. "
                . "Gunakan hanya jika pengguna tidak dapat mengakses email-nya."
            )
            ->modalSubmitActionLabel('Ya, Reset 2FA')
            ->visible(fn (User $record) =>
                auth()->user()->can('resetTwoFactor', $record)
                && $record->hasTwoFactorEnabled()
            )
            ->action(function (User $record): void {
                /** @var TwoFactorService $svc */
                $svc = app(TwoFactorService::class);
                $svc->adminReset($record, auth()->user(), request());

                Notification::make()
                    ->title('2FA direset')
                    ->body("Verifikasi dua faktor {$record->name} telah dinonaktifkan dan semua perangkat tepercaya dicabut.")
                    ->success()
                    ->send();
            });
    }
}
