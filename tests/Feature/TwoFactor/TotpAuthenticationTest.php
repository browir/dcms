<?php

namespace Tests\Feature\TwoFactor;

use App\Filament\Components\TwoFactorSettings;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\TwoFactorChallenge;
use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class TotpAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_totp_redirects_to_challenge_without_sending_email(): void
    {
        Notification::fake();

        $totp = app(TotpService::class);
        $secret = $totp->generateSecret();

        $user = User::factory()->create([
            'email' => 'totp_user@syifaglobalgroup.com',
            'username' => 'totp_user',
            'password' => Hash::make('password123'),
            'two_factor_enabled' => true,
            'two_factor_enabled_at' => now(),
            'two_factor_type' => 'totp',
            'two_factor_secret' => $secret,
        ]);

        // Submit login form
        Livewire::test(Login::class)
            ->set('data.login', 'totp_user')
            ->set('data.password', 'password123')
            ->call('authenticate')
            ->assertRedirect(route('filament.admin.auth.two-factor-challenge'));

        $this->assertFalse(Auth::check(), 'User should not be logged in before 2FA verification');
        $this->assertEquals($user->id, session('2fa_pending_user_id'));

        // Pastikan TIDAK mengirim email OTP sama sekali karena menggunakan TOTP
        Notification::assertNothingSent();
    }

    public function test_challenge_verifies_valid_totp_code_and_logs_in(): void
    {
        $totp = app(TotpService::class);
        $secret = $totp->generateSecret();

        $user = User::factory()->create([
            'two_factor_enabled' => true,
            'two_factor_enabled_at' => now(),
            'two_factor_type' => 'totp',
            'two_factor_secret' => $secret,
        ]);

        session([
            '2fa_pending_user_id' => $user->id,
            '2fa_remember' => false,
        ]);

        // Generate valid 6-digit TOTP code
        $validCode = $totp->generateCode($secret);

        Livewire::test(TwoFactorChallenge::class)
            ->assertSee('Google Authenticator')
            ->set('code', $validCode)
            ->set('trustDevice', true)
            ->set('trustDays', 30)
            ->call('verify')
            ->assertRedirect(filament()->getUrl());

        $this->assertTrue(Auth::check());
        $this->assertEquals($user->id, Auth::id());
        $this->assertNull(session('2fa_pending_user_id'));

        $cookieName = config('two_factor.cookie_name', 'dcms_trusted_device');
        $this->assertTrue(Cookie::hasQueued($cookieName));
    }

    public function test_challenge_rejects_invalid_totp_code(): void
    {
        $totp = app(TotpService::class);
        $secret = $totp->generateSecret();

        $user = User::factory()->create([
            'two_factor_enabled' => true,
            'two_factor_enabled_at' => now(),
            'two_factor_type' => 'totp',
            'two_factor_secret' => $secret,
        ]);

        session([
            '2fa_pending_user_id' => $user->id,
            '2fa_remember' => false,
        ]);

        Livewire::test(TwoFactorChallenge::class)
            ->set('code', '000000')
            ->call('verify')
            ->assertSet('code', '')
            ->assertSee('Kode authenticator tidak valid');

        $this->assertFalse(Auth::check());
    }

    public function test_user_can_enable_totp_in_settings(): void
    {
        $user = User::factory()->create([
            'two_factor_enabled' => false,
            'two_factor_enabled_at' => null,
            'two_factor_type' => 'email',
            'two_factor_secret' => null,
        ]);

        $this->actingAs($user);

        $totp = app(TotpService::class);

        $test = Livewire::test(TwoFactorSettings::class)
            ->call('initEnableTotp')
            ->assertSet('step', 'setup_totp');

        $generatedSecret = $test->get('totpSecret');
        $this->assertNotEmpty($generatedSecret);

        $validCode = $totp->generateCode($generatedSecret);

        $test->set('code', $validCode)
            ->call('confirmTotp')
            ->assertSet('step', 'idle')
            ->assertSee('Google Authenticator (2FA) berhasil diaktifkan');

        $user->refresh();
        $this->assertTrue($user->hasTwoFactorEnabled());
        $this->assertEquals('totp', $user->two_factor_type);
        $this->assertEquals($generatedSecret, $user->two_factor_secret);
    }

    public function test_user_can_disable_totp_in_settings_using_authenticator_code(): void
    {
        $totp = app(TotpService::class);
        $secret = $totp->generateSecret();

        $user = User::factory()->create([
            'two_factor_enabled' => true,
            'two_factor_enabled_at' => now(),
            'two_factor_type' => 'totp',
            'two_factor_secret' => $secret,
        ]);

        $this->actingAs($user);

        $validCode = $totp->generateCode($secret);

        Livewire::test(TwoFactorSettings::class)
            ->call('initDisable')
            ->assertSet('step', 'confirm_disable')
            ->set('code', $validCode)
            ->call('confirmDisable')
            ->assertSet('step', 'idle')
            ->assertSee('Verifikasi dua faktor berhasil dinonaktifkan');

        $user->refresh();
        $this->assertFalse($user->hasTwoFactorEnabled());
        $this->assertNull($user->two_factor_secret);
    }
}
