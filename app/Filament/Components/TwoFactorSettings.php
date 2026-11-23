<?php

namespace App\Filament\Components;

use App\Models\TrustedDevice;
use App\Services\TotpService;
use App\Services\TwoFactorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Komponen Livewire untuk manajemen 2FA (Google Authenticator) di halaman profil.
 *
 * State machine:
 *   idle            → tampilan status 2FA saat ini
 *   setup           → scan QR code & konfirmasi aktivasi Google Authenticator
 *   confirm_disable → input kode authenticator untuk menonaktifkan 2FA
 */
class TwoFactorSettings extends Component
{
    // ── State ────────────────────────────────────────────────────────────────
    public string $step         = 'idle'; // idle | setup | confirm_disable
    public string $code         = '';
    public string $errorMsg     = '';
    public string $successMsg   = '';
    public int    $defaultTrustDays;

    // TOTP setup state
    public string $totpSecret   = '';
    public string $totpQrUrl    = '';

    protected TwoFactorService $twoFactor;
    protected TotpService      $totp;

    public function boot(TwoFactorService $twoFactor, TotpService $totp): void
    {
        $this->twoFactor = $twoFactor;
        $this->totp      = $totp;
    }

    public function mount(): void
    {
        $this->defaultTrustDays = Auth::user()->default_trust_days
            ?? config('two_factor.default_duration_days', 30);
    }

    // ── Computed ─────────────────────────────────────────────────────────────

    #[Computed]
    public function user()
    {
        return Auth::user()->fresh();
    }

    #[Computed]
    public function trustedDevices()
    {
        return Auth::user()->activeTrustedDevices()->get();
    }

    #[Computed]
    public function durationOptions(): array
    {
        $options = [];
        foreach (config('two_factor.trusted_device_durations', [7, 30, 90]) as $days) {
            $options[$days] = "{$days} hari";
        }
        return $options;
    }

    // ── Aktifkan 2FA (Google Authenticator) ───────────────────────────────────

    public function initEnable(): void
    {
        $this->reset(['errorMsg', 'successMsg', 'code']);

        $user = Auth::user();
        $this->totpSecret = $this->totp->generateSecret();
        $this->totpQrUrl  = $this->totp->getQrCodeUrl(
            $user->email ?? $user->username ?? 'user',
            $this->totpSecret,
            config('app.name', 'DCMS Syifa')
        );
        $this->step = 'setup';
    }

    public function initEnableTotp(): void
    {
        $this->initEnable();
    }

    public function confirmEnable(): void
    {
        $this->reset('errorMsg');
        $cleanCode = preg_replace('/\s+/', '', (string) $this->code);

        if (! $this->totp->verifyCode($this->totpSecret, $cleanCode)) {
            $this->errorMsg = 'Kode authenticator tidak valid. Pastikan jam pada perangkat Anda akurat (sinkron) dan masukkan 6 digit kode terbaru.';
            $this->code     = '';
            return;
        }

        try {
            $this->twoFactor->enable(Auth::user(), $this->totpSecret, request());
            $this->step       = 'idle';
            $this->code       = '';
            $this->totpSecret = '';
            $this->totpQrUrl  = '';
            $this->successMsg = 'Google Authenticator (2FA) berhasil diaktifkan.';
            $this->dispatch('2fa-status-changed');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[2FA] Gagal mengaktifkan Google Authenticator: ' . $e->getMessage(), ['exception' => $e]);
            $this->errorMsg = 'Gagal mengaktifkan Google Authenticator. Silakan coba lagi.';
        }
    }

    public function confirmTotp(): void
    {
        $this->confirmEnable();
    }

    // ── Nonaktifkan 2FA ───────────────────────────────────────────────────────

    public function initDisable(): void
    {
        $this->reset(['errorMsg', 'successMsg', 'code']);
        $this->step = 'confirm_disable';
    }

    public function confirmDisable(): void
    {
        $this->reset('errorMsg');
        $user = Auth::user();
        $cleanCode = preg_replace('/\s+/', '', (string) $this->code);

        $isValid = $this->totp->verifyCode((string) $user->two_factor_secret, $cleanCode);
        if (! $isValid) {
            $this->errorMsg = 'Kode authenticator tidak valid. Masukkan 6 digit kode yang tampil di aplikasi ponsel Anda.';
            $this->code     = '';
            return;
        }

        try {
            $this->twoFactor->disable(Auth::user(), request());
            $this->step       = 'idle';
            $this->code       = '';
            $this->successMsg = 'Verifikasi dua faktor berhasil dinonaktifkan.';

            // Hapus cookie perangkat tepercaya milik user ini
            Cookie::queue($this->twoFactor->forgetTrustedDeviceCookie());
            $this->dispatch('2fa-status-changed');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[2FA] Gagal menonaktifkan 2FA: ' . $e->getMessage(), ['exception' => $e]);
            $this->errorMsg = 'Gagal menonaktifkan 2FA. Silakan coba lagi.';
        }
    }

    // ── Default Trust Days ────────────────────────────────────────────────────

    public function saveDefaultTrustDays(): void
    {
        $allowed = config('two_factor.trusted_device_durations', [7, 30, 90]);
        if (! in_array($this->defaultTrustDays, $allowed, true)) {
            $this->errorMsg = 'Pilihan durasi tidak valid.';
            return;
        }

        Auth::user()->update(['default_trust_days' => $this->defaultTrustDays]);
        $this->successMsg = 'Pengaturan durasi kepercayaan perangkat disimpan.';
    }

    // ── Perangkat Tepercaya ───────────────────────────────────────────────────

    public function revokeDevice(int $deviceId): void
    {
        $device = TrustedDevice::where('user_id', Auth::id())
            ->where('id', $deviceId)
            ->first();

        if (! $device) {
            return;
        }

        $this->twoFactor->revokeDevice($device, request());
        $this->successMsg = "Perangkat \"{$device->display_name}\" berhasil dicabut.";
    }

    public function revokeAllDevices(): void
    {
        $this->twoFactor->revokeAllTrustedDevices(Auth::user(), request());

        // Hapus cookie perangkat tepercaya pada browser saat ini
        Cookie::queue($this->twoFactor->forgetTrustedDeviceCookie());
        $this->successMsg = 'Semua perangkat tepercaya berhasil dicabut.';
    }

    public function dismissError(): void
    {
        $this->errorMsg = '';
    }

    public function dismissSuccess(): void
    {
        $this->successMsg = '';
    }

    // ── Cancel ───────────────────────────────────────────────────────────────

    public function cancel(): void
    {
        $this->step       = 'idle';
        $this->code       = '';
        $this->totpSecret = '';
        $this->totpQrUrl  = '';
        $this->errorMsg   = '';
    }

    // ── Render ───────────────────────────────────────────────────────────────

    public function render()
    {
        return view('filament.components.two-factor-settings');
    }
}
