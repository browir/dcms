<?php

namespace Tests\Feature\TwoFactor;

use App\Filament\Components\TwoFactorSettings;
use App\Models\User;
use App\Notifications\TwoFactorStatusNotification;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class TwoFactorLivewireComponentTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'two_factor_enabled' => false,
            'default_trust_days' => 30,
        ], $attrs));
    }

    public function test_init_enable_generates_secret_and_transitions_to_setup_step(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user);

        Livewire::test(TwoFactorSettings::class)
            ->assertSet('step', 'idle')
            ->call('initEnable')
            ->assertSet('step', 'setup')
            ->assertHasNoErrors();
    }

    public function test_confirm_enable_with_wrong_code_shows_error(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user);

        Livewire::test(TwoFactorSettings::class)
            ->call('initEnable')
            ->set('code', '000000')
            ->call('confirmEnable')
            ->assertSet('step', 'setup')
            ->assertSee('Kode authenticator tidak valid');

        $this->assertFalse($user->fresh()->two_factor_enabled);
    }

    public function test_confirm_enable_with_correct_code_activates_2fa(): void
    {
        Notification::fake();
        $user = $this->makeUser();

        $this->actingAs($user);

        $totp = app(TotpService::class);

        $component = Livewire::test(TwoFactorSettings::class)
            ->call('initEnable');

        $secret = $component->get('totpSecret');
        $this->assertNotEmpty($secret);

        $validCode = $totp->generateCode($secret);

        $component->set('code', $validCode)
            ->call('confirmEnable')
            ->assertSet('step', 'idle')
            ->assertSet('code', '')
            ->assertSee('Google Authenticator (2FA) berhasil diaktifkan.');

        $this->assertTrue($user->fresh()->two_factor_enabled);
        $this->assertEquals('totp', $user->fresh()->two_factor_type);
        $this->assertEquals($secret, $user->fresh()->two_factor_secret);

        Notification::assertSentTo($user, TwoFactorStatusNotification::class);
    }

    public function test_disable_flow(): void
    {
        Notification::fake();
        $totp = app(TotpService::class);
        $secret = $totp->generateSecret();

        $user = $this->makeUser([
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
            ->assertSee('Verifikasi dua faktor berhasil dinonaktifkan.');

        $this->assertFalse($user->fresh()->two_factor_enabled);
        $this->assertNull($user->fresh()->two_factor_secret);
    }
}
