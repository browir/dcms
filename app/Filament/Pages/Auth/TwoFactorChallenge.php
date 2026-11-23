<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Services\TotpService;
use App\Services\TwoFactorService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.simple')]
#[Title('Verifikasi Google Authenticator')]
class TwoFactorChallenge extends Component
{
    public string $code        = '';
    public bool   $trustDevice = true;
    public int    $trustDays   = 30;
    public string $errorMsg    = '';

    protected TwoFactorService $twoFactor;
    protected TotpService      $totp;

    public function boot(TwoFactorService $twoFactor, TotpService $totp): void
    {
        $this->twoFactor = $twoFactor;
        $this->totp      = $totp;
    }

    public function mount(): void
    {
        // Pastikan ada pending session
        if (! session()->has('2fa_pending_user_id')) {
            $this->redirect(route('filament.admin.auth.login'));
            return;
        }

        $user = User::find(session('2fa_pending_user_id'));
        if (! $user || empty($user->two_factor_secret)) {
            $this->redirect(route('filament.admin.auth.login'));
            return;
        }

        // Default trust days dari preferensi pengguna
        $this->trustDays = $user->default_trust_days
            ?? config('two_factor.default_duration_days', 30);
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

        if (! $user || empty($user->two_factor_secret)) {
            $this->redirect(route('filament.admin.auth.login'));
            return;
        }

        $cleanCode = preg_replace('/\s+/', '', (string) $this->code);
        $isValid = $this->totp->verifyCode((string) $user->two_factor_secret, $cleanCode);

        if (! $isValid) {
            $this->errorMsg = 'Kode authenticator tidak valid. Pastikan waktu di perangkat Anda sesuai dan masukkan 6 digit kode terbaru.';
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

        if ($cookie) {
            \Illuminate\Support\Facades\Cookie::queue($cookie);
        }

        $this->redirectIntended(default: filament()->getUrl());
    }

    public function render()
    {
        return view('filament.auth.two-factor-challenge');
    }
}
