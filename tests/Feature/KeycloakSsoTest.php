<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Models\Company;
use App\Models\Department;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\PegawaiRoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Livewire\Livewire;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Login SSO Keycloak (package syifa/keycloak-sso) — Keycloak & API HRIS disimulasikan.
 */
class KeycloakSsoTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'keycloak-sso.client_id' => 'dcms',
            'keycloak-sso.client_secret' => 'secret',
            'keycloak-sso.base_url' => 'https://sso.test',
            'keycloak-sso.realm' => 'sgg',
            'keycloak-sso.hris_directory.base_url' => 'https://hris.test',
            'keycloak-sso.hris_directory.token' => 'token',
            // Package menyalin ini dari keycloak-sso.* saat boot; di test config diubah setelah boot.
            'services.keycloak' => [
                'client_id' => 'dcms', 'client_secret' => 'secret', 'redirect' => 'http://localhost/auth/keycloak/callback',
                'base_url' => 'https://sso.test', 'realms' => 'sgg',
            ],
        ]);

        $this->seed(PegawaiRoleSeeder::class);
        Role::findOrCreate('kabid', 'web');
        Role::findOrCreate('super_admin', 'web');

        $company = Company::create(['name' => 'RSU Syifa Medika', 'code' => 'RSUSM']);
        $this->department = Department::create([
            'name' => 'Keperawatan', 'code' => 'KEP', 'keycloak_code' => 'rsu-bjb-keperawatan', 'company_id' => $company->id,
        ]);
        $this->unit = Unit::create([
            'name' => 'ICU', 'prefix' => 'ICU', 'keycloak_code' => 'rsu-bjb-keperawatan-icu', 'department_id' => $this->department->id,
        ]);
    }

    /** Claim yang dikembalikan Keycloak (/userinfo) pada callback berikutnya. */
    private array $keycloakClaims = [];

    private bool $socialiteMocked = false;

    private function keycloakReturns(array $claims): void
    {
        $this->keycloakClaims = $claims;

        // Mock cukup dipasang sekali; login berikutnya dalam test yang sama memakai claim baru.
        if ($this->socialiteMocked) {
            return;
        }
        $this->socialiteMocked = true;

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturnUsing(fn () => (new SocialiteUser)
            ->setRaw($this->keycloakClaims)
            ->map([
                'id' => $this->keycloakClaims['sub'],
                'name' => $this->keycloakClaims['name'] ?? null,
                'email' => $this->keycloakClaims['email'] ?? null,
            ]));
        Socialite::shouldReceive('driver')->with('keycloak')->andReturn($provider);
    }

    private function hrisReturns(?array $employee): void
    {
        Http::fake([
            'hris.test/api/integrasi/karyawan/*' => $employee
                ? Http::response(['data' => $employee])
                : Http::response(['message' => 'Not found'], 404),
        ]);
    }

    private function claims(array $overrides = []): array
    {
        return [
            'sub' => 'kc-uuid-1',
            'preferred_username' => '19900101',
            'name' => 'Nama Dari Token',
            'email' => 'perawat@syifa.test',
            ...$overrides,
        ];
    }

    public function test_karyawan_baru_otomatis_dibuat_dengan_data_hris_dan_role_pegawai(): void
    {
        $this->keycloakReturns($this->claims());
        $this->hrisReturns([
            'nip' => '19900101',
            'name' => 'Siti Perawat',
            'email' => 'siti@syifa.test',
            'is_active' => true,
            'company' => 'RSUSM',
            'org_unit' => ['department' => 'rsu-bjb-keperawatan', 'division' => 'rsu-bjb-keperawatan-icu'],
        ]);

        $this->get('/auth/keycloak/callback')->assertRedirect('/admin');

        $user = User::query()->where('nip', '19900101')->sole();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('Siti Perawat', $user->name);
        $this->assertSame('siti@syifa.test', $user->email);
        $this->assertSame('19900101', $user->username);
        $this->assertSame('kc-uuid-1', $user->keycloak_sub);
        $this->assertSame($this->department->company_id, $user->company_id);
        $this->assertSame($this->department->id, $user->department_id);
        $this->assertSame($this->unit->id, $user->unit_id);
        $this->assertTrue($user->hasRole('pegawai'));
    }

    public function test_akun_lama_dicocokkan_lewat_email_tanpa_membuat_akun_baru(): void
    {
        $existing = User::create([
            'name' => 'Akun Lama', 'username' => 'lama', 'email' => 'perawat@syifa.test', 'password' => 'x',
        ]);
        $existing->assignRole('kabid');

        $this->keycloakReturns($this->claims());
        $this->hrisReturns(null);

        $this->get('/auth/keycloak/callback')->assertRedirect('/admin');

        $this->assertSame(1, User::query()->count());
        $existing->refresh();
        $this->assertAuthenticatedAs($existing);
        $this->assertSame('kc-uuid-1', $existing->keycloak_sub);
        $this->assertSame('19900101', $existing->nip);
        // Keycloak tidak mengirim data role → role jabatan yang sudah ada tidak dicabut.
        $this->assertTrue($existing->hasRole('kabid'));
        $this->assertFalse($existing->hasRole('pegawai'));
    }

    public function test_role_jabatan_disinkronkan_dari_client_role_keycloak(): void
    {
        $this->hrisReturns(null);

        $this->keycloakReturns($this->claims(['resource_access' => ['dcms' => ['roles' => ['dcms-kabid']]]]));
        $this->get('/auth/keycloak/callback');
        $user = User::query()->sole();
        $this->assertTrue($user->hasRole('kabid'));

        // Login berikutnya tanpa role kabid di Keycloak → dicabut, role dasar tetap ada.
        auth()->logout();
        $this->keycloakReturns($this->claims(['resource_access' => ['dcms' => ['roles' => []]]]));
        $this->get('/auth/keycloak/callback');
        $user->refresh()->unsetRelation('roles');
        $this->assertFalse($user->hasRole('kabid'));
        $this->assertTrue($user->hasRole('pegawai'));
    }

    public function test_pindah_unit_di_hris_ikut_tersinkron_saat_login(): void
    {
        $newDept = Department::create([
            'name' => 'Farmasi', 'code' => 'FAR', 'keycloak_code' => 'rsu-bjb-farmasi', 'company_id' => $this->department->company_id,
        ]);
        $user = User::create([
            'name' => 'Siti', 'username' => '19900101', 'nip' => '19900101', 'email' => 'siti@syifa.test', 'password' => 'x',
            'company_id' => $this->department->company_id, 'department_id' => $this->department->id, 'unit_id' => $this->unit->id,
        ]);

        $this->keycloakReturns($this->claims());
        $this->hrisReturns(['nip' => '19900101', 'name' => 'Siti', 'email' => 'siti@syifa.test', 'org_unit' => ['department' => 'rsu-bjb-farmasi']]);

        $this->get('/auth/keycloak/callback');

        $user->refresh();
        $this->assertSame($newDept->id, $user->department_id);
        // Unit lama milik departemen lain dilepas.
        $this->assertNull($user->unit_id);
    }

    public function test_akun_nonaktif_ditolak(): void
    {
        User::create([
            'name' => 'Nonaktif', 'username' => '19900101', 'nip' => '19900101', 'email' => 'x@syifa.test', 'password' => 'x', 'is_active' => false,
        ]);

        $this->keycloakReturns($this->claims());
        $this->hrisReturns(null);

        $this->get('/auth/keycloak/callback')->assertRedirect('/')->assertSessionHasErrors('keycloak');
        $this->assertGuest();

        // Pesan error diteruskan sampai halaman login.
        $this->get('/')->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_halaman_login_menampilkan_tombol_sso(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(Login::class)
            ->assertSee('Masuk dengan Akun SSO')
            ->assertSee(route('keycloak-sso.redirect'), escape: false);
    }

    public function test_logout_sesi_sso_juga_logout_dari_keycloak(): void
    {
        $user = User::create(['name' => 'Siti', 'username' => 'siti', 'email' => 'siti@syifa.test', 'password' => 'x']);

        $response = $this->actingAs($user)
            ->withSession(['keycloak.sso' => true])
            ->post(route('filament.admin.auth.logout'));

        $this->assertGuest();
        $this->assertStringStartsWith('https://sso.test/realms/sgg/protocol/openid-connect/logout', $response->headers->get('Location'));
        $this->assertStringContainsString(urlencode(url('/admin/login')), $response->headers->get('Location'));
    }

    public function test_logout_akun_lokal_tetap_ke_halaman_login(): void
    {
        $user = User::create(['name' => 'Admin', 'username' => 'admin', 'email' => 'admin@dcms.test', 'password' => 'x']);

        $this->actingAs($user)->post(route('filament.admin.auth.logout'))
            ->assertRedirect(Filament::getPanel('admin')->getLoginUrl());
    }
}
