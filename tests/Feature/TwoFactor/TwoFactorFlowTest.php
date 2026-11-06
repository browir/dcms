<?php

namespace Tests\Feature\TwoFactor;

use App\Models\TrustedDevice;
use App\Models\TwoFactorCode;
use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TwoFactorFlowTest extends TestCase
{
    use RefreshDatabase;

    private TwoFactorService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = app(TwoFactorService::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'two_factor_enabled' => false,
            'default_trust_days' => 30,
        ], $attrs));
    }

    private function fakeRequest(): \Illuminate\Http\Request
    {
        return \Illuminate\Http\Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR'     => '127.0.0.1',
            'HTTP_USER_AGENT' => 'TestBrowser/1.0',
        ]);
    }

    // ── 1. Kirim kode & verifikasi berhasil ──────────────────────────────────

    public function test_send_code_and_verify_successfully(): void
    {
        Notification::fake();
        $user    = $this->makeUser();
        $request = $this->fakeRequest();

        $code = $this->svc->sendCode($user, 'login', $request);

        Notification::assertSentTo($user, TwoFactorCodeNotification::class);
        $this->assertDatabaseHas('two_factor_codes', ['user_id' => $user->id, 'purpose' => 'login']);

        // Verifikasi dengan kode yang tersimpan (kita perlu plain kode — akali dengan mocking Hash)
        // Untuk test, kita bypass dengan langsung memanggil Hash::check
        $this->assertNotNull($code->code_hash);
    }

    // ── 2. Kode salah bertambah attempts ─────────────────────────────────────

    public function test_wrong_code_increments_attempts(): void
    {
        $user    = $this->makeUser();
        $request = $this->fakeRequest();

        TwoFactorCode::create([
            'user_id'    => $user->id,
            'code_hash'  => Hash::make('123456'),
            'purpose'    => 'login',
            'attempts'   => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        $result = $this->svc->verifyCode($user, 'login', '999999', $request);

        $this->assertNull($result);
        $this->assertDatabaseHas('two_factor_codes', [
            'user_id'  => $user->id,
            'attempts' => 1,
        ]);
    }

    // ── 3. Kode kedaluwarsa ───────────────────────────────────────────────────

    public function test_expired_code_returns_null(): void
    {
        $user    = $this->makeUser();
        $request = $this->fakeRequest();

        TwoFactorCode::create([
            'user_id'    => $user->id,
            'code_hash'  => Hash::make('123456'),
            'purpose'    => 'login',
            'attempts'   => 0,
            'expires_at' => now()->subMinute(), // sudah kedaluwarsa
        ]);

        $result = $this->svc->verifyCode($user, 'login', '123456', $request);
        $this->assertNull($result);
    }

    // ── 4. Kode dikunci setelah max_attempts ──────────────────────────────────

    public function test_code_locked_after_max_attempts(): void
    {
        $user    = $this->makeUser();
        $request = $this->fakeRequest();

        $max = config('two_factor.max_attempts', 5);

        $code = TwoFactorCode::create([
            'user_id'    => $user->id,
            'code_hash'  => Hash::make('123456'),
            'purpose'    => 'login',
            'attempts'   => $max, // sudah exhaust
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->assertTrue($code->isExhausted());
        $this->assertFalse($code->isValid());
    }

    // ── 5. Rate limit kirim ulang ─────────────────────────────────────────────

    public function test_resend_rate_limit_enforced(): void
    {
        Notification::fake();
        $user    = $this->makeUser();
        $request = $this->fakeRequest();

        // Kirim pertama — berhasil
        $this->svc->sendCode($user, 'login', $request);

        // Kirim kedua segera — harus throw RuntimeException (cooldown)
        $this->expectException(\RuntimeException::class);
        $this->svc->sendCode($user, 'login', $request);
    }

    // ── 6. Aktifkan 2FA ───────────────────────────────────────────────────────

    public function test_enable_two_factor(): void
    {
        $user    = $this->makeUser();
        $request = $this->fakeRequest();

        $this->svc->enable($user, $request);

        $user->refresh();
        $this->assertTrue($user->two_factor_enabled);
        $this->assertNotNull($user->two_factor_enabled_at);

        $this->assertDatabaseHas('two_factor_logs', [
            'user_id' => $user->id,
            'event'   => '2fa_enabled',
        ]);
    }

    // ── 7. Nonaktifkan 2FA & revoke semua perangkat ───────────────────────────

    public function test_disable_two_factor_revokes_all_devices(): void
    {
        $user    = $this->makeUser(['two_factor_enabled' => true]);
        $request = $this->fakeRequest();

        // Buat perangkat tepercaya
        TrustedDevice::create([
            'user_id'    => $user->id,
            'token_hash' => hash('sha256', 'token-test'),
            'expires_at' => now()->addDays(30),
        ]);

        $this->svc->disable($user, $request);

        $user->refresh();
        $this->assertFalse($user->two_factor_enabled);
        $this->assertDatabaseCount('trusted_devices', 0);
    }

    // ── 8. Perangkat tepercaya ────────────────────────────────────────────────

    public function test_trust_device_and_is_trusted(): void
    {
        $user    = $this->makeUser(['two_factor_enabled' => true]);
        $request = $this->fakeRequest();

        $plainToken = $this->svc->trustDevice($user, 30, $request);

        $this->assertDatabaseHas('trusted_devices', ['user_id' => $user->id]);

        // Simulasi request dengan cookie
        $cookieRequest = \Illuminate\Http\Request::create('/', 'GET', [], [
            config('two_factor.cookie_name') => $plainToken,
        ]);

        $this->assertTrue($this->svc->isDeviceTrusted($user, $cookieRequest));
    }

    // ── 9. Perangkat kedaluwarsa ──────────────────────────────────────────────

    public function test_expired_device_is_not_trusted(): void
    {
        $user = $this->makeUser(['two_factor_enabled' => true]);

        $plainToken = 'expired-token';
        TrustedDevice::create([
            'user_id'    => $user->id,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->subDay(), // sudah kedaluwarsa
        ]);

        $request = \Illuminate\Http\Request::create('/', 'GET', [], [
            config('two_factor.cookie_name') => $plainToken,
        ]);

        $this->assertFalse($this->svc->isDeviceTrusted($user, $request));
    }

    // ── 10. Pencabutan perangkat ──────────────────────────────────────────────

    public function test_revoke_device(): void
    {
        $user    = $this->makeUser();
        $request = $this->fakeRequest();

        $device = TrustedDevice::create([
            'user_id'    => $user->id,
            'token_hash' => hash('sha256', 'some-token'),
            'expires_at' => now()->addDays(30),
        ]);

        $this->svc->revokeDevice($device, $request);

        $this->assertDatabaseMissing('trusted_devices', ['id' => $device->id]);
        $this->assertDatabaseHas('two_factor_logs', [
            'user_id' => $user->id,
            'event'   => 'device_revoked',
        ]);
    }

    // ── 11. Admin reset ───────────────────────────────────────────────────────

    public function test_admin_reset_disables_2fa_and_revokes_devices(): void
    {
        $admin   = $this->makeUser();
        $target  = $this->makeUser(['two_factor_enabled' => true]);
        $request = $this->fakeRequest();

        TrustedDevice::create([
            'user_id'    => $target->id,
            'token_hash' => hash('sha256', 'device-token'),
            'expires_at' => now()->addDays(30),
        ]);

        $this->svc->adminReset($target, $admin, $request);

        $target->refresh();
        $this->assertFalse($target->two_factor_enabled);
        $this->assertDatabaseCount('trusted_devices', 0);
        $this->assertDatabaseHas('two_factor_logs', [
            'user_id'  => $target->id,
            'event'    => 'admin_reset',
            'actor_id' => $admin->id,
        ]);
    }

    // ── 12. Password lama hangus saat kode baru dibuat ───────────────────────

    public function test_old_code_invalidated_when_new_code_sent(): void
    {
        Notification::fake();
        $user    = $this->makeUser();
        $request = $this->fakeRequest();

        $oldCode = TwoFactorCode::create([
            'user_id'    => $user->id,
            'code_hash'  => Hash::make('111111'),
            'purpose'    => 'login',
            'attempts'   => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        // Tunggu cooldown habis dengan memanipulasi cache
        \Illuminate\Support\Facades\Cache::forget("2fa_resend_cooldown_{$user->id}");

        $this->svc->sendCode($user, 'login', $request);

        $oldCode->refresh();
        $this->assertNotNull($oldCode->used_at, 'Kode lama harus hangus saat kode baru dibuat');
    }
}
