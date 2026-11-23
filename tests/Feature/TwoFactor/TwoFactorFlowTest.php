<?php

namespace Tests\Feature\TwoFactor;

use App\Models\TrustedDevice;
use App\Models\User;
use App\Services\TotpService;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorFlowTest extends TestCase
{
    use RefreshDatabase;

    private TwoFactorService $svc;
    private TotpService      $totp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc  = app(TwoFactorService::class);
        $this->totp = app(TotpService::class);
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

    // ── 1. Aktifkan 2FA dengan Secret TOTP ────────────────────────────────────

    public function test_enable_two_factor(): void
    {
        $user    = $this->makeUser();
        $request = $this->fakeRequest();
        $secret  = $this->totp->generateSecret();

        $this->svc->enable($user, $secret, $request);

        $user->refresh();
        $this->assertTrue($user->two_factor_enabled);
        $this->assertNotNull($user->two_factor_enabled_at);
        $this->assertEquals('totp', $user->two_factor_type);
        $this->assertEquals($secret, $user->two_factor_secret);

        $this->assertDatabaseHas('two_factor_logs', [
            'user_id' => $user->id,
            'event'   => '2fa_enabled',
        ]);
    }

    // ── 2. Nonaktifkan 2FA & revoke semua perangkat ───────────────────────────

    public function test_disable_two_factor_revokes_all_devices(): void
    {
        $secret = $this->totp->generateSecret();
        $user   = $this->makeUser([
            'two_factor_enabled'    => true,
            'two_factor_enabled_at' => now(),
            'two_factor_type'       => 'totp',
            'two_factor_secret'     => $secret,
        ]);
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
        $this->assertNull($user->two_factor_secret);
        $this->assertDatabaseCount('trusted_devices', 0);
    }

    // ── 3. Perangkat tepercaya ────────────────────────────────────────────────

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

    // ── 4. Perangkat kedaluwarsa ──────────────────────────────────────────────

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

    // ── 5. Pencabutan perangkat ──────────────────────────────────────────────

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

    // ── 6. Admin reset ───────────────────────────────────────────────────────

    public function test_admin_reset_disables_2fa_and_revokes_devices(): void
    {
        $admin   = $this->makeUser();
        $target  = $this->makeUser([
            'two_factor_enabled' => true,
            'two_factor_type'    => 'totp',
            'two_factor_secret'  => $this->totp->generateSecret(),
        ]);
        $request = $this->fakeRequest();

        TrustedDevice::create([
            'user_id'    => $target->id,
            'token_hash' => hash('sha256', 'device-token'),
            'expires_at' => now()->addDays(30),
        ]);

        $this->svc->adminReset($target, $admin, $request);

        $target->refresh();
        $this->assertFalse($target->two_factor_enabled);
        $this->assertNull($target->two_factor_secret);
        $this->assertDatabaseCount('trusted_devices', 0);
        $this->assertDatabaseHas('two_factor_logs', [
            'user_id'  => $target->id,
            'event'    => 'admin_reset',
            'actor_id' => $admin->id,
        ]);
    }
}
