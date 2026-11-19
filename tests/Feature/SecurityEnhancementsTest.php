<?php

namespace Tests\Feature;

use App\Filament\Components\ActiveSessions;
use App\Models\PasswordHistory;
use App\Models\User;
use App\Models\UserLoginHistory;
use App\Notifications\SuspiciousLoginNotification;
use App\Rules\NotPreviousPassword;
use App\Services\LoginSecurityService;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    /** ── 1. Password Policy & History Tests ── */

    public function test_password_policy_requires_minimum_8_chars_mixed_case_and_numbers(): void
    {
        $rule = ['password' => ['required', Password::min(8)->letters()->mixedCase()->numbers()]];

        // Kurang dari 8 karakter
        $v1 = Validator::make(['password' => 'Pass1'], $rule);
        $this->assertTrue($v1->fails());

        // Tidak ada huruf besar
        $v2 = Validator::make(['password' => 'password123'], $rule);
        $this->assertTrue($v2->fails());

        // Tidak ada angka
        $v3 = Validator::make(['password' => 'PasswordBagus'], $rule);
        $this->assertTrue($v3->fails());

        // Valid
        $v4 = Validator::make(['password' => 'Password123'], $rule);
        $this->assertTrue($v4->passes());
    }

    public function test_not_previous_password_rule_blocks_current_and_archived_passwords(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('CurrentPassword123'),
        ]);

        PasswordHistory::create([
            'user_id'    => $user->id,
            'password'   => Hash::make('OldPassword456'),
            'created_at' => now()->subDays(10),
        ]);

        // Coba pakai password saat ini
        $rule = ['password' => [new NotPreviousPassword($user, 3)]];
        $vCurrent = Validator::make(['password' => 'CurrentPassword123'], $rule);
        $this->assertTrue($vCurrent->fails());

        // Coba pakai password lama yang ada di riwayat
        $vOld = Validator::make(['password' => 'OldPassword456'], $rule);
        $this->assertTrue($vOld->fails());

        // Pakai password baru yang belum pernah dipakai
        $vNew = Validator::make(['password' => 'BrandNewPassword789'], $rule);
        $this->assertTrue($vNew->passes());
    }

    public function test_user_password_update_automatically_archives_old_password_and_updates_timestamp(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OriginalPass123'),
        ]);

        $this->assertNull($user->passwordHistories()->first());

        // Update password
        $user->update([
            'password' => Hash::make('UpdatedPass456'),
        ]);

        $this->assertNotNull($user->password_changed_at);
        $history = $user->passwordHistories()->first();
        $this->assertNotNull($history);
        $this->assertTrue(Hash::check('OriginalPass123', $history->password));
    }

    /** ── 2. Suspicious / New Device Login Notification Tests ── */

    public function test_first_login_records_baseline_without_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $service = app(LoginSecurityService::class);

        $request = Request::create('/login', 'POST', [], [], [], [
            'REMOTE_ADDR'     => '192.168.1.100',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
        ]);

        $service->recordLogin($user, $request);

        $this->assertDatabaseHas('user_login_histories', [
            'user_id'    => $user->id,
            'ip_address' => '192.168.1.100',
        ]);

        // Baseline login pertama tidak mengirim email spam
        Notification::assertNothingSent();
    }

    public function test_login_from_unrecognized_ip_and_device_sends_suspicious_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        // Riwayat lama
        UserLoginHistory::create([
            'user_id'        => $user->id,
            'ip_address'     => '192.168.1.100',
            'user_agent'     => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
            'device_summary' => 'Google Chrome di Windows',
            'login_at'       => now()->subDays(5),
        ]);

        $service = app(LoginSecurityService::class);

        // Login dari IP & OS berbeda (misal iPhone / iOS)
        $newRequest = Request::create('/login', 'POST', [], [], [], [
            'REMOTE_ADDR'     => '203.0.113.50',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1',
        ]);

        $service->recordLogin($user, $newRequest);

        Notification::assertSentTo($user, SuspiciousLoginNotification::class, function ($notification) {
            return $notification->ip === '203.0.113.50';
        });
    }

    /** ── 3. Active Sessions Management Tests ── */

    public function test_active_sessions_component_renders_and_lists_sessions(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        DB::table('sessions')->insert([
            'id'            => 'other-session-123',
            'user_id'       => $user->id,
            'ip_address'    => '10.0.0.5',
            'user_agent'    => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) Mobile Safari',
            'payload'       => '',
            'last_activity' => now()->timestamp,
        ]);

        Livewire::test(ActiveSessions::class)
            ->assertSeeHtml('Manajemen Sesi & Perangkat Aktif')
            ->assertSee('10.0.0.5')
            ->assertSee('Keluar dari Semua Perangkat Lain');
    }

    public function test_logout_other_devices_deletes_other_sessions(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Secret12345'),
        ]);
        $this->actingAs($user);

        DB::table('sessions')->insert([
            [
                'id'            => 'other-session-abc',
                'user_id'       => $user->id,
                'ip_address'    => '10.0.0.20',
                'user_agent'    => 'Mozilla/5.0 Chrome',
                'payload'       => '',
                'last_activity' => now()->timestamp,
            ],
            [
                'id'            => 'other-session-def',
                'user_id'       => $user->id,
                'ip_address'    => '10.0.0.21',
                'user_agent'    => 'Mozilla/5.0 Safari',
                'payload'       => '',
                'last_activity' => now()->timestamp,
            ],
        ]);

        // Coba dengan password salah
        Livewire::test(ActiveSessions::class)
            ->set('password', 'WrongPassword')
            ->call('logoutOtherDevices')
            ->assertSee('Kata sandi yang Anda masukkan tidak sesuai.');

        $this->assertEquals(2, DB::table('sessions')->where('user_id', $user->id)->count());

        // Coba dengan password benar
        Livewire::test(ActiveSessions::class)
            ->set('password', 'Secret12345')
            ->call('logoutOtherDevices')
            ->assertSet('successMsg', 'Semua sesi di perangkat lain berhasil dikeluarkan.');

        $this->assertEquals(0, DB::table('sessions')->where('user_id', $user->id)->count());
    }

    /** ── 4. Security Headers Test ── */

    public function test_security_headers_are_present_on_responses(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $this->assertTrue($response->headers->has('Content-Security-Policy'));
    }
}
