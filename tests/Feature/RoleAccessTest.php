<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\Dashboard;
use App\Filament\Admin\Widgets\Dashboard\FollowUpWidget;
use App\Filament\Admin\Widgets\Dashboard\ModuleOverviewWidget;
use App\Models\Company;
use App\Models\Department;
use App\Models\Unit;
use App\Models\User;
use App\Support\RoleAccess;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/** Role & hak akses: halaman dan widget Beranda mengikuti izin Shield per role. */
class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Department $department;

    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        // Migration sync_role_permissions sudah menjalankan RoleAccess::sync().
        $this->company = Company::create(['name' => 'PT Syifa Global Group', 'code' => 'SYIFA', 'kode_surat' => 'SGG']);
        $this->department = Department::create(['name' => 'Umum', 'code' => 'UMM', 'company_id' => $this->company->id]);
        $this->unit = Unit::create(['name' => 'Kepegawaian', 'prefix' => '2', 'department_id' => $this->department->id]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function user(?string $role): User
    {
        $user = User::create([
            'name' => 'User '.($role ?? 'tanpa role'),
            'username' => 'u_'.($role ?? 'none'),
            'email' => ($role ?? 'none').'@example.test',
            'password' => 'password',
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'unit_id' => $this->unit->id,
        ]);

        if ($role) {
            $user->assignRole($role);
        }

        return $user;
    }

    /** @return array<class-string, string> class → URL index tiap resource & page panel */
    private function panelUrls(): array
    {
        $panel = Filament::getPanel('admin');
        $urls = [];

        foreach ($panel->getResources() as $resource) {
            /** @var class-string<resource> $resource */
            if ($resource::hasPage('index')) {
                $urls[$resource] = $resource::getUrl();
            }
        }

        foreach ($panel->getPages() as $page) {
            /** @var class-string<Page> $page */
            $urls[$page] = $page::getUrl();
        }

        return $urls;
    }

    public function test_role_dasar_tersedia_dengan_izin_yang_cukup(): void
    {
        foreach (['pegawai', 'kabid', 'direktur', 'super_admin'] as $name) {
            $this->assertNotNull(Role::findByName($name, 'web'), "Role {$name} belum ada");
        }

        // Kabid & direktur harus bisa membuka daftar dokumen untuk ACC.
        $this->assertTrue(Role::findByName('kabid')->hasPermissionTo('ViewAny:Document'));
        $this->assertTrue(Role::findByName('direktur')->hasPermissionTo('approve_document_direktur'));
        $this->assertTrue(Role::findByName('super_admin')->hasAllPermissions(RoleAccess::DIREKTUR));
    }

    /** @return array<string, array{0: ?string, 1: array<string, bool>}> */
    public static function roles(): array
    {
        return [
            'pegawai' => ['pegawai', ['DocumentResource' => true, 'MeetingResource' => true, 'LetterNumberResource' => true, 'UserResource' => false, 'RoleResource' => false, 'AuditTrail' => false]],
            'kabid' => ['kabid', ['DocumentResource' => true, 'MeetingResource' => true, 'AuditTrail' => true, 'UserResource' => false, 'AppSettings' => false]],
            'direktur' => ['direktur', ['DocumentResource' => true, 'RecycleBin' => true, 'AppSettings' => false]],
            'super_admin' => ['super_admin', ['DocumentResource' => true, 'UserResource' => true, 'RoleResource' => true, 'AppSettings' => true]],
            'tanpa role' => [null, ['DocumentResource' => false, 'MeetingResource' => false, 'UserResource' => false]],
        ];
    }

    /**
     * Setiap halaman: yang boleh diakses (canAccess) terbuka 200, sisanya ditolak 403 —
     * jadi menu yang disembunyikan memang tidak bisa dibuka lewat URL langsung.
     *
     * @param  array<string, bool>  $expected
     */
    #[DataProvider('roles')]
    public function test_akses_halaman_konsisten_per_role(?string $role, array $expected): void
    {
        $this->actingAs($this->user($role));

        foreach ($this->panelUrls() as $class => $url) {
            $allowed = $class::canAccess();
            $response = $this->get($url);

            $this->assertSame(
                $allowed ? 200 : 403,
                $response->getStatusCode(),
                sprintf('%s: canAccess=%s tapi HTTP %d %s', class_basename($class), var_export($allowed, true), $response->getStatusCode(), $response->exception?->getMessage()),
            );

            if (array_key_exists(class_basename($class), $expected)) {
                $this->assertSame($expected[class_basename($class)], $allowed, class_basename($class));
            }
        }
    }

    public function test_widget_beranda_mengikuti_izin_role(): void
    {
        $this->actingAs($this->user('pegawai'));
        $this->assertContains(ModuleOverviewWidget::class, $this->visibleWidgets());

        // Admin mencabut widget Ringkasan dari role pegawai lewat Peran & Hak Akses.
        Role::findByName('pegawai')->revokePermissionTo('View:ModuleOverviewWidget');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        auth()->user()->unsetRelation('roles')->unsetRelation('permissions');

        $visible = $this->visibleWidgets();
        $this->assertNotContains(ModuleOverviewWidget::class, $visible);
        $this->assertContains(FollowUpWidget::class, $visible);
    }

    public function test_user_tanpa_role_tidak_melihat_widget(): void
    {
        $this->actingAs($this->user(null));

        $this->assertSame([], $this->visibleWidgets());
    }

    /** @return array<int, class-string> */
    private function visibleWidgets(): array
    {
        return collect((new Dashboard)->getVisibleWidgets())
            ->map(fn ($widget) => is_string($widget) ? $widget : $widget->widget)
            ->values()
            ->all();
    }
}
