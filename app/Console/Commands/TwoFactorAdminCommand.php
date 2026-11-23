<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TotpService;
use App\Services\TwoFactorService;
use Illuminate\Console\Command;

class TwoFactorAdminCommand extends Command
{
    protected $signature = '2fa:manage 
                            {action : status, disable, or enable-totp}
                            {user? : Username, email, or ID of the user}';

    protected $description = 'Kelola 2FA pengguna (status, disable, enable-totp)';

    public function handle(TwoFactorService $twoFactor, TotpService $totp): int
    {
        $action = $this->argument('action');
        $identifier = $this->argument('user');

        if ($action === 'status' && ! $identifier) {
            $users = User::select('id', 'name', 'email', 'two_factor_enabled_at', 'two_factor_type')
                ->whereNotNull('two_factor_enabled_at')
                ->get();

            if ($users->isEmpty()) {
                $this->info('Tidak ada pengguna dengan 2FA aktif saat ini.');
                return Command::SUCCESS;
            }

            $this->table(
                ['ID', 'Nama', 'Email', 'Tipe 2FA', 'Diaktifkan Pada'],
                $users->map(fn($u) => [
                    $u->id,
                    $u->name,
                    $u->email,
                    $u->two_factor_type ?? 'email',
                    $u->two_factor_enabled_at?->format('Y-m-d H:i:s'),
                ])
            );
            return Command::SUCCESS;
        }

        if (! $identifier) {
            $this->error('Parameter user (email, username, atau ID) wajib diisi.');
            return Command::FAILURE;
        }

        $user = User::where('id', $identifier)
            ->orWhere('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        if (! $user) {
            $this->error("Pengguna dengan identitas '{$identifier}' tidak ditemukan.");
            return Command::FAILURE;
        }

        switch ($action) {
            case 'status':
                $this->info("Pengguna: {$user->name} ({$user->email})");
                $this->line("Status 2FA: " . ($user->hasTwoFactorEnabled() ? '<fg=green>AKTIF</>' : '<fg=yellow>NONAKTIF</>'));
                $this->line("Tipe 2FA  : " . ($user->two_factor_type ?? 'email'));
                $this->line("Secret TOTP: " . (filled($user->two_factor_secret) ? '<fg=green>Tersedia</>' : '<fg=gray>Kosong</>'));
                break;

            case 'disable':
                $twoFactor->adminReset($user, 'Console Command: 2fa:manage disable');
                $this->info("2FA untuk {$user->name} ({$user->email}) berhasil dinonaktifkan.");
                break;

            case 'enable-totp':
                $secret = $totp->generateSecret();
                $twoFactor->enableTotp($user, $secret);
                $qrUrl = $totp->getQrCodeUrl($user->email ?? $user->username, $secret, config('app.name', 'DCMS Syifa'));

                $this->info("Google Authenticator berhasil diaktifkan untuk {$user->name} ({$user->email})!");
                $this->line("Secret Key: <fg=yellow;options=bold>{$secret}</>");
                $this->line("QR Code URL: <fg=cyan>{$qrUrl}</>");
                $this->line("Kode saat ini: <fg=green;options=bold>" . $totp->generateCode($secret) . "</>");
                break;

            default:
                $this->error("Action '{$action}' tidak dikenal. Gunakan: status, disable, atau enable-totp.");
                return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
