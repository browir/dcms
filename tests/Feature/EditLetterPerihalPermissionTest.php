<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\LetterNumber;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EditLetterPerihalPermissionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;
    private LetterNumber $letter;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        Role::firstOrCreate(['name' => 'super_admin']);
        $stafRole = Role::firstOrCreate(['name' => 'staf']);

        Permission::firstOrCreate(['name' => 'edit_letter_perihal', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'ViewAny:Document', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'View:Document', 'guard_name' => 'web']);

        $company = Company::create([
            'name' => 'RSU Syifa Medika Banjarbaru',
            'code' => 'RSUSMBJB',
            'kode_surat' => 'RSUSM',
            'is_active' => true,
        ]);

        $department = Department::create([
            'company_id' => $company->id,
            'name' => 'Pelayanan Medis',
            'code' => 'YANMED',
        ]);

        $unit = Unit::create([
            'department_id' => $department->id,
            'name' => 'Instalasi Rawat Inap',
            'prefix' => '1',
        ]);

        $this->admin = User::create([
            'name' => 'Admin User',
            'username' => 'admin_user',
            'email' => 'admin@example.test',
            'password' => 'password',
            'company_id' => $company->id,
            'department_id' => $department->id,
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);
        $this->admin->assignRole('super_admin');

        $this->staff = User::create([
            'name' => 'Staff User',
            'username' => 'staff_user',
            'email' => 'staff@example.test',
            'password' => 'password',
            'company_id' => $company->id,
            'department_id' => $department->id,
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);
        $this->staff->assignRole($stafRole);

        $this->letter = LetterNumber::create([
            'nomor' => '001/D.1/RSUSM/VI/2026',
            'nomor_urut' => 1,
            'jenis_surat' => 'D',
            'company_id' => $company->id,
            'department_id' => $department->id,
            'unit_id' => $unit->id,
            'tanggal_surat' => '2026-06-21',
            'judul_surat' => 'Surat Edaran Jam Pelayanan',
            'perihal' => 'Perihal Awal',
            'created_by' => $this->staff->id,
        ]);
    }

    public function test_custom_permission_is_registered_in_config(): void
    {
        $customPermissions = config('filament-shield.custom_permissions');

        $this->assertArrayHasKey('edit_letter_perihal', $customPermissions);
        $this->assertEquals('Ubah perihal surat setelah terbit', $customPermissions['edit_letter_perihal']);
    }

    public function test_super_admin_can_update_perihal_via_policy(): void
    {
        $this->assertTrue($this->admin->can('updatePerihal', $this->letter));
    }

    public function test_user_without_permission_cannot_update_perihal(): void
    {
        $this->assertFalse($this->staff->can('updatePerihal', $this->letter));
    }

    public function test_user_with_permission_can_update_perihal(): void
    {
        $this->staff->givePermissionTo('edit_letter_perihal');

        $this->assertTrue($this->staff->can('updatePerihal', $this->letter));

        $this->letter->update(['perihal' => 'Perihal Baru yang Diubah']);
        $this->assertEquals('Perihal Baru yang Diubah', $this->letter->fresh()->perihal);
    }
}

