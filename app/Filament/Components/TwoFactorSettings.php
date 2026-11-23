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
 * Komponen Livewire untuk manajemen 2FA di halaman profil.
 * Ditampilkan via @livewire('two-factor-settings') di view profil.
 *
 * State machine:
 *   idle            → tampilan status 2FA saat ini
 *   setup_totp      → scan QR code & input kode Google Authenticator
 *   confirm_enable  → input kode email untuk mengaktifkan 2FA
 *   confirm_disable → input kode (TOTP / email) untuk menonaktifkan 2FA
 */
class TwoFactorSettings extends Component
{
    // ── State ────────────────────────────────────────────────────────────────
    public string $step         = 'idle'; // idle | setup_totp | confirm_enable | confirm_disable
    public string $code         = '';
    public string $errorMsg     = '';
    public string $successMsg   = '';
    public int    $cooldown     = 0;
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

    // ── Aktifkan 2FA via Google Authenticator (TOTP) ─────────────────────────

    public function initEnableTotp(): void
    {
        $this->reset(['errorMsg', 'successMsg', 'code']);

        $user = Auth::user();
        $this->totpSecret = $this->totp->generateSecret();
        $this->totpQrUrl  = $this->totp->getQrCodeUrl(
            $user->email ?? $user->username ?? 'user',
            $this->totpSecret,
            config('app.name', 'DCMS Syifa')
        );
        $this->step = 'setup_totp';
    }

    public function confirmTotp(): void
    {
        $this->reset('errorMsg');
        $cleanCode = preg_replace('/\s+/', '', (string) $this->code);

        if (! $this->totp->verifyCode($this->totpSecret, $cleanCode)) {
            $this->errorMsg = 'Kode authenticator tidak valid. Pastikan jam pada perangkat Anda akurat (sinkron) dan masukkan 6 digit kode terbaru.';
            $this->code     = '';
            return;
        }

        try {
            $this->twoFactor->enableTotp(Auth::user(), $this->totpSecret, request());
            $this->step       = 'idle';
            $this->code       = '';
            $this->totpSecret = '';
            $this->totpQrUrl  = '';
            $this->successMsg = 'Google Authenticator (2FA) berhasil diaktifkan!';
            $this->dispatch('2fa-status-changed');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[2FA] Gagal mengaktifkan TOTP 2FA: ' . $e->getMessage(), ['exception' => $e]);
            $this->errorMsg = 'Gagal mengaktifkan Google Authenticator. Silakan coba lagi.';
        }
    }

    // ── Aktifkan 2FA via Email ───────────────────────────────────────────────

    /**
     * Langkah 1: kirim kode konfirmasi ke email, masuk ke step confirm_enable
     */
    public function initEnable(): void
    {
        $this->reset(['errorMsg', 'successMsg', 'code']);

        try {
            $this->twoFactor->sendCode(Auth::user(), 'enable_2fa', request());
            $this->step     = 'confirm_enable';
            $this->cooldown = config('two_factor.resend_cooldown_seconds', 60);
        } catch (\RuntimeException $e) {
            $this->errorMsg = $e->getMessage();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[2FA] Gagal kirim OTP enable_2fa: ' . $e->getMessage(), ['exception' => $e]);
            $this->errorMsg = 'Gagal mengirim kode verifikasi ke email. Silakan coba lagi beberapa saat lagi.';
        }
    }

    /**
     * Langkah 2: verifikasi kode → aktifkan 2FA Email
     */
    public function confirmEnable(): void
    {
        $this->reset('errorMsg');

        $result = $this->twoFactor->verifyCode(Auth::user(), 'enable_2fa', trim($this->code), request());

        if (! $result) {
            $this->errorMsg = 'Kode tidak valid atau sudah kedaluwarsa.';
            $this->code     = '';
            return;
        }

        try {
            $this->twoFactor->enable(Auth::user(), request());
            $this->step       = 'idle';
            $this->code       = '';
            $this->successMsg = 'Verifikasi dua faktor berhasil diaktifkan.';
            $this->dispatch('2fa-status-changed');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[2FA] Gagal mengaktifkan 2FA: ' . $e->getMessage(), ['exception' => $e]);
            $this->errorMsg = 'Gagal mengaktifkan 2FA. Silakan coba lagi.';
        }
    }

    // ── Nonaktifkan 2FA ───────────────────────────────────────────────────────

    public function initDisable(): void
    {
        $this->reset(['errorMsg', 'successMsg', 'code']);
        $user = Auth::user();

        // Jika TOTP, tidak perlu kirim email OTP
        if ($user->two_factor_type === 'totp' || filled($user->two_factor_secret)) {
            $this->step     = 'confirm_disable';
            $this->cooldown = 0;
            return;
        }

        try {
            $this->twoFactor->sendCode($user, 'disable_2fa', request());
            $this->step     = 'confirm_disable';
            $this->cooldown = config('two_factor.resend_cooldown_seconds', 60);
        } catch (\RuntimeException $e) {
            $this->errorMsg = $e->getMessage();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[2FA] Gagal kirim OTP disable_2fa: ' . $e->getMessage(), ['exception' => $e]);
            $this->errorMsg = 'Gagal mengirim kode verifikasi ke email. Silakan coba lagi beberapa saat lagi.';
        }
    }

    public function confirmDisable(): void
    {
        $this->reset('errorMsg');
        $user = Auth::user();
        $cleanCode = preg_replace('/\s+/', '', (string) $this->code);

        if ($user->two_factor_type === 'totp' || filled($user->two_factor_secret)) {
            $isValid = $this->totp->verifyCode((string) $user->two_factor_secret, $cleanCode);
            if (! $isValid) {
                $this->errorMsg = 'Kode authenticator tidak valid. Masukkan 6 digit kode dari aplikasi ponsel Anda.';
                $this->code     = '';
                return;
            }
        } else {
            $result = $this->twoFactor->verifyCode($user, 'disable_2fa', $cleanCode, request());
            if (! $result) {
                $this->errorMsg = 'Kode tidak valid atau sudah kedaluwarsa.';
                $this->code     = '';
                return;
            }
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

    // ── Kirim Ulang Kode (Email) ─────────────────────────────────────────────

    public function resendCode(): void
    {
        $user = Auth::user();
        if ($user->two_factor_type === 'totp' || filled($user->two_factor_secret)) {
            return;
        }

        $purpose = $this->step === 'confirm_enable' ? 'enable_2fa' : 'disable_2fa';

        try {
            $this->twoFactor->sendCode($user, $purpose, request());
            $this->cooldown = config('two_factor.resend_cooldown_seconds', 60);
            $this->errorMsg = '';
            $this->dispatch('code-resent');
        } catch (\RuntimeException $e) {
            $this->errorMsg = $e->getMessage();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[2FA] Gagal resend OTP: ' . $e->getMessage(), ['exception' => $e]);
            $this->errorMsg = 'Gagal mengirim ulang kode verifikasi. Silakan coba lagi beberapa saat lagi.';
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
