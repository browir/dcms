<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Services\TwoFactorService;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    protected string $view = 'filament.custom-login';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getLoginFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent()->default(true),
            ])
            ->statePath('data');
    }

    protected function getLoginFormComponent(): Component
    {
        return TextInput::make('login')
            ->label('Nama pengguna / Email')
            ->placeholder('Masukkan nama pengguna atau email')
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Kata sandi')
            ->placeholder('••••••••')
            ->password()
            ->revealable()
            ->autocomplete('current-password')
            ->required()
            ->extraInputAttributes(['tabindex' => 2]);
    }

    public function ssoEnabled(): bool
    {
        return \App\Support\Sso\Sso::enabled() && Route::has('keycloak-sso.redirect');
    }

    public function ssoError(): ?string
    {
        return session('errors')?->first('keycloak') ?: null;
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        $loginType = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        return [
            $loginType => $data['login'],
            'password' => $data['password'],
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.login' => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
    }

    /**
     * Override authenticate: cek apakah user perlu 2FA sebelum masuk penuh.
     */
    public function authenticate(): ?\Filament\Auth\Http\Responses\Contracts\LoginResponse
    {
        $data        = $this->form->getState();
        $credentials = $this->getCredentialsFromFormData($data);

        // Coba autentikasi tanpa login penuh
        if (! \Illuminate\Support\Facades\Auth::validate($credentials)) {
            $this->throwFailureValidationException();
        }

        /** @var \App\Models\User $user */
        $user = \App\Models\User::where(array_key_first($credentials), array_values($credentials)[0])->first();

        if (! $user) {
            $this->throwFailureValidationException();
        }

        $twoFactor = app(TwoFactorService::class);

        // Cek apakah 2FA perlu (aktif DAN perangkat belum tepercaya)
        if ($user->two_factor_enabled && ! $twoFactor->isDeviceTrusted($user, request())) {
            // Simpan user_id di session untuk halaman TwoFactorChallenge
            session([
                '2fa_pending_user_id' => $user->id,
                '2fa_remember'        => $data['remember'] ?? false,
            ]);

            // Kirim kode OTP
            try {
                $twoFactor->sendCode($user, 'login', request());
            } catch (\RuntimeException $e) {
                throw \Illuminate\Validation\ValidationException::withMessages(['data.login' => $e->getMessage()]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('[2FA] Gagal kirim OTP saat login: ' . $e->getMessage(), ['exception' => $e]);
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'data.login' => 'Gagal mengirim kode verifikasi ke email. Silakan hubungi administrator.',
                ]);
            }

            $this->redirect(route('filament.admin.auth.two-factor-challenge'));
            return null;
        }

        // Tidak perlu 2FA — login langsung
        \Illuminate\Support\Facades\Auth::login($user, $data['remember'] ?? false);
        session()->regenerate();

        return app(\Filament\Auth\Http\Responses\Contracts\LoginResponse::class);
    }
}
