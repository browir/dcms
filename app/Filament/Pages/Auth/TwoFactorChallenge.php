<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.simple')]
#[Title('Verifikasi Dua Faktor')]
class TwoFactorChallenge extends Component
{
    public string $code        = '';
    public bool   $trustDevice = true;
    public int    $trustDays   = 30;
    public string $errorMsg    = '';
    public int    $cooldown    = 0;

    protected TwoFactorService $twoFactor;

    public function boot(TwoFactorService $twoFactor): void
    {
        $this->twoFactor = $twoFactor;
    }

    public function mount(): void
    {
        // Pastikan ada pending session
        if (! session()->has('2fa_pending_user_id')) {
            $this->redirect(route('filament.admin.auth.login'));
            return;
        }

        $user = User::find(session('2fa_pending_user_id'));
        if (! $user) {
            $this->redirect(route('filament.admin.auth.login'));
            return;
        }

        // Default trust days dari preferensi pengguna
        $this->trustDays = $user->default_trust_days
            ?? config('two_factor.default_duration_days', 30);

        $this->cooldown = $this->twoFactor->getResendCooldownSeconds($user);
    }

    /** Pilihan durasi kepercayaan perangkat */
    #[Computed]
    public function durationOptions(): array
    {
        $options = [];
        foreach (config('two_factor.trusted_device_durations', [7, 30, 90]) as $days) {
            $options[$days] = "{$days} hari";
        }
        return $options;
    }

    #[Computed]
    public function pendingUser(): ?User
    {
        return User::find(session('2fa_pending_user_id'));
    }

    public function verify(): void
    {
        $this->errorMsg = '';
        $user = $this->pendingUser;

        if (! $user) {
            $this->redirect(route('filament.admin.auth.login'));
            return;
        }

        $code = $this->twoFactor->verifyCode($user, 'login', trim($this->code), request());

        if (! $code) {
            $this->errorMsg = 'Kode tidak valid atau sudah kedaluwarsa. Periksa kembali atau minta kode baru.';
            $this->code     = '';
            return;
        }

        // Kode berhasil — tangani perangkat tepercaya
        $remember = session('2fa_remember', false);
        $cookie   = null;

        if ($this->trustDevice && $this->trustDays > 0) {
            $plainToken = $this->twoFactor->trustDevice($user, $this->trustDays, request());
            $cookie     = $this->twoFactor->makeTrustedDeviceCookie($plainToken, $this->trustDays);
        }

        // Login
        Auth::login($user, $remember);
        session()->forget(['2fa_pending_user_id', '2fa_remember']);
        session()->regenerate();

        $response = redirect()->intended(filament()->getUrl());

        if ($cookie) {
            $response->withCookie($cookie);
        }

        $this->redirect($response->getTargetUrl());
    }

    public function resend(): void
    {
        $user = $this->pendingUser;
        if (! $user) {
            return;
        }

        try {
            $this->twoFactor->sendCode($user, 'login', request());
            $this->cooldown = config('two_factor.resend_cooldown_seconds', 60);
            $this->errorMsg = '';
            $this->dispatch('resent');
        } catch (\RuntimeException $e) {
            $this->errorMsg = $e->getMessage();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[2FA] Gagal kirim ulang OTP login challenge: ' . $e->getMessage(), ['exception' => $e]);
            $this->errorMsg = 'Gagal mengirim kode verifikasi ke email. Silakan coba lagi beberapa saat lagi.';
        }
    }

    public function render()
    {
        return view('filament.auth.two-factor-challenge');
    }
}
