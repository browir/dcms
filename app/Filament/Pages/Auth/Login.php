<?php

namespace App\Filament\Pages\Auth;

use App\Support\Sso\Sso;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    // Pastikan tidak menggunakan static
    protected string $view = 'filament.custom-login';

    /**
     * Override method form menggunakan Schema sesuai permintaan parent class test2 test3
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getLoginFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
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

    /** Tombol "Masuk dengan SSO" tampil kalau Keycloak sudah dikonfigurasi. */
    public function ssoEnabled(): bool
    {
        return Sso::enabled();
    }

    /** Pesan gagal dari callback SSO (package mengirimnya lewat error bag key 'keycloak'). */
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
}
