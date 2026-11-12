<?php

namespace Tests\Feature\TwoFactor;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\TwoFactorChallenge;
use App\Models\TwoFactorCode;
use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class TwoFactorChallengeLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_login_to_2fa_challenge_flow(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'test2fa@syifaglobalgroup.com',
            'username' => 'test2fa',
            'password' => Hash::make('password123'),
            'two_factor_enabled' => true,
        ]);

        // 1. Submit login form
        Livewire::test(Login::class)
            ->set('data.login', 'test2fa')
            ->set('data.password', 'password123')
            ->set('data.remember', true)
            ->call('authenticate')
            ->assertRedirect(route('filament.admin.auth.two-factor-challenge'));

        $this->assertFalse(Auth::check(), 'User should not be logged in before 2FA verification');
        $this->assertEquals($user->id, session('2fa_pending_user_id'));
        $this->assertTrue(session('2fa_remember'));

        // Notification OTP terkirim
        Notification::assertSentTo($user, TwoFactorCodeNotification::class);

        // Ambil kode yang baru saja di-generate
        $codeRecord = TwoFactorCode::where('user_id', $user->id)
            ->where('purpose', 'login')
            ->latest('id')
            ->first();

        $this->assertNotNull($codeRecord);

        // Timpa hash dengan kode test yang diketahui
        $codeRecord->update(['code_hash' => Hash::make('654321')]);

        // 2. Submit kode di TwoFactorChallenge
        Livewire::test(TwoFactorChallenge::class)
            ->set('code', '654321')
            ->set('trustDevice', true)
            ->set('trustDays', 30)
            ->call('verify')
            ->assertRedirect(filament()->getUrl());

        $this->assertTrue(Auth::check(), 'User should be fully authenticated now');
        $this->assertEquals($user->id, Auth::id());
        $this->assertNull(session('2fa_pending_user_id'));

        $cookieName = config('two_factor.cookie_name', 'dcms_trusted_device');
        $this->assertTrue(Cookie::hasQueued($cookieName));
    }

    public function test_verify_with_correct_code_logs_in_and_redirects(): void
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
        ]);

        session([
            '2fa_pending_user_id' => $user->id,
            '2fa_remember' => false,
        ]);

        TwoFactorCode::create([
            'user_id' => $user->id,
            'code_hash' => Hash::make('123456'),
            'purpose' => 'login',
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        Livewire::test(TwoFactorChallenge::class)
            ->set('code', '123456')
            ->set('trustDevice', true)
            ->set('trustDays', 30)
            ->call('verify')
            ->assertRedirect(filament()->getUrl());

        $this->assertTrue(Auth::check());
        $this->assertEquals($user->id, Auth::id());
        $this->assertDatabaseHas('trusted_devices', [
            'user_id' => $user->id,
        ]);

        $cookieName = config('two_factor.cookie_name', 'dcms_trusted_device');
        $this->assertTrue(Cookie::hasQueued($cookieName));
    }

    public function test_verify_with_wrong_code_shows_error(): void
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
        ]);

        session([
            '2fa_pending_user_id' => $user->id,
            '2fa_remember' => false,
        ]);

        TwoFactorCode::create([
            'user_id' => $user->id,
            'code_hash' => Hash::make('123456'),
            'purpose' => 'login',
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        Livewire::test(TwoFactorChallenge::class)
            ->set('code', '999999')
            ->call('verify')
            ->assertSet('code', '')
            ->assertSee('Kode tidak valid atau sudah kedaluwarsa.');

        $this->assertFalse(Auth::check());
    }

    public function test_verify_without_pending_session_redirects_to_login(): void
    {
        Livewire::test(TwoFactorChallenge::class)
            ->assertRedirect(route('filament.admin.auth.login'));
    }
}
