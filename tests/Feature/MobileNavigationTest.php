<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\MobileNavigation;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Navigasi mobile hanya menampilkan halaman yang boleh diakses user. */
class MobileNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('kabid', 'web');
        Role::findOrCreate('super_admin', 'web')->givePermissionTo(collect(['Document', 'Meeting', 'User'])
            ->flatMap(fn ($model) => ["ViewAny:{$model}", "View:{$model}", "Create:{$model}"])
            ->map(fn ($name) => Permission::findOrCreate($name, 'web')));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function user(string $username, ?string $role = null): User
    {
        $user = User::create([
            'name' => ucfirst($username),
            'username' => $username,
            'email' => "{$username}@example.test",
            'password' => 'password',
        ]);

        if ($role) {
            $user->assignRole($role);
        }

        return $user;
    }

    /** @return array<int, string> */
    private function menuLabels(): array
    {
        return collect(MobileNavigation::menuGroups())->flatMap(fn (array $group) => array_column($group['items'], 'label'))->all();
    }

    public function test_user_tanpa_izin_tidak_melihat_menu_yang_tidak_bisa_diakses(): void
    {
        $this->actingAs($this->user('staf'));

        $this->assertSame(['Beranda', 'Kegiatan'], array_column(MobileNavigation::bottomBar(), 'label'));

        $labels = $this->menuLabels();
        $this->assertNotContains('Dokumen', $labels);
        $this->assertNotContains('Tambah Dokumen', $labels);
        $this->assertNotContains('Pengguna', $labels);
        $this->assertNotContains('Recycle Bin', $labels);
    }

    public function test_super_admin_melihat_semua_dan_menu_tidak_mengulang_bar_bawah(): void
    {
        $this->actingAs($this->user('admin', 'super_admin'));

        $bar = MobileNavigation::bottomBar();
        $this->assertSame(['Beranda', 'Dokumen', 'Rapat', 'Kegiatan'], array_column($bar, 'label'));

        $labels = collect(MobileNavigation::menuGroups(array_column($bar, 'url')))
            ->flatMap(fn (array $group) => array_column($group['items'], 'label'))
            ->all();

        $this->assertContains('Tambah Dokumen', $labels);
        $this->assertContains('Nomor Dokumen', $labels);
        $this->assertContains('Pengguna', $labels);
        $this->assertNotContains('Beranda', $labels);
        $this->assertNotContains('Rapat', $labels);
    }

    public function test_halaman_tetap_tampil_untuk_user_tanpa_izin(): void
    {
        $this->actingAs($this->user('staf'))
            ->get('/admin')
            ->assertOk()
            ->assertDontSee('/admin/documents/create')
            ->assertDontSee('href="'.url('/admin/documents').'"', false);
    }
}
