<?php

namespace App\Console\Commands;

use App\Models\TrustedDevice;
use App\Models\TwoFactorCode;
use Illuminate\Console\Command;

class CleanupTwoFactorData extends Command
{
    protected $signature = 'two-factor:cleanup
                            {--dry-run : Hanya tampilkan jumlah baris yang akan dihapus tanpa benar-benar menghapus}';

    protected $description = 'Hapus kode OTP yang sudah kedaluwarsa/terpakai dan perangkat tepercaya yang sudah habis masa berlakunya.';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // ── Kode OTP ─────────────────────────────────────────────────────────
        $expiredCodes = TwoFactorCode::where(function ($q) {
            $q->where('expires_at', '<', now())     // sudah kedaluwarsa
              ->orWhereNotNull('used_at');           // atau sudah dipakai
        });

        $codesCount = $expiredCodes->count();

        if ($dryRun) {
            $this->info("[Dry-run] Kode OTP yang akan dihapus : {$codesCount}");
        } else {
            $expiredCodes->delete();
            $this->info("Kode OTP dihapus : {$codesCount}");
        }

        // ── Perangkat Tepercaya ───────────────────────────────────────────────
        $expiredDevices = TrustedDevice::where('expires_at', '<', now());
        $devicesCount   = $expiredDevices->count();

        if ($dryRun) {
            $this->info("[Dry-run] Perangkat tepercaya yang akan dihapus : {$devicesCount}");
        } else {
            $expiredDevices->delete();
            $this->info("Perangkat tepercaya dihapus : {$devicesCount}");
        }

        $this->newLine();
        $this->line(now()->toDateTimeString() . ' — Pembersihan data 2FA selesai.');

        return self::SUCCESS;
    }
}

