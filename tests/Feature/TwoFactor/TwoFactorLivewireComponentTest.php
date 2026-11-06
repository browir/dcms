<?php

namespace Tests\Feature\TwoFactor;

use App\Filament\Components\TwoFactorSettings;
use App\Models\TwoFactorCode;
use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use App\Notifications\TwoFactorStatusNotification;
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

    public function test_init_enable_sends_notification_and_transitions_to_confirm_step(): void
    {
        Notification::fake();
        $user = $this->makeUser();

        $this->actingAs($user);

        Livewire::test(TwoFactorSettings::class)
            ->assertSet('step', 'idle')
            ->call('initEnable')
            ->assertSet('step', 'confirm_enable')
            ->assertHasNoErrors();

        Notification::assertSentTo($user, TwoFactorCodeNotification::class);
        $this->assertDatabaseHas('two_factor_codes', [
            'user_id' => $user->id,
            'purpose' => 'enable_2fa',
        ]);
    }

    public function test_confirm_enable_with_wrong_code_shows_error(): void
    {
        Notification::fake();
        $user = $this->makeUser();

        $this->actingAs($user);

        Livewire::test(TwoFactorSettings::class)
            ->call('initEnable')
            ->set('code', '000000')
            ->call('confirmEnable')
            ->assertSet('step', 'confirm_enable')
            ->assertSee('Kode tidak valid atau sudah kedaluwarsa.');

        $this->assertFalse($user->fresh()->two_factor_enabled);
    }

    public function test_confirm_enable_with_correct_code_activates_2fa(): void
    {
        Notification::fake();
        $user = $this->makeUser();

        $this->actingAs($user);

        // Mock sendCode by manually dispatching or directly checking code
        $component = Livewire::test(TwoFactorSettings::class)
            ->call('initEnable');

        $latestCode = TwoFactorCode::where('user_id', $user->id)
            ->where('purpose', 'enable_2fa')
            ->first();

        // Let's verify by overriding the code hash with known code
        $latestCode->update(['code_hash' => bcrypt('123456')]);

        $component->set('code', '123456')
            ->call('confirmEnable')
            ->assertSet('step', 'idle')
            ->assertSet('code', '')
            ->assertSee('Verifikasi dua faktor berhasil diaktifkan.');

        $this->assertTrue($user->fresh()->two_factor_enabled);
        Notification::assertSentTo($user, TwoFactorStatusNotification::class);
    }

    public function test_disable_flow(): void
    {
        Notification::fake();
        $user = $this->makeUser(['two_factor_enabled' => true]);

        $this->actingAs($user);

        $component = Livewire::test(TwoFactorSettings::class)
            ->call('initDisable')
            ->assertSet('step', 'confirm_disable');

        $latestCode = TwoFactorCode::where('user_id', $user->id)
            ->where('purpose', 'disable_2fa')
            ->first();

        $latestCode->update(['code_hash' => bcrypt('654321')]);

        $component->set('code', '654321')
            ->call('confirmDisable')
            ->assertSet('step', 'idle')
            ->assertSee('Verifikasi dua faktor berhasil dinonaktifkan.');

        $this->assertFalse($user->fresh()->two_factor_enabled);
    }
}

