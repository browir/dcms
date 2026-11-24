<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MergeDuplicateRsusmUnitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_unit_takes_official_code_and_numbered_duplicate_is_removed(): void
    {
        $company = Company::create(['name' => 'RSU Syifa Medika Banjarbaru', 'code' => 'RSUSMBJB', 'kode_surat' => 'RSUSM']);
        $adum = Department::create(['name' => 'Administrasi dan Umum Banjarbaru', 'code' => 'ADUMBJB', 'company_id' => $company->id]);

        // Unit lama: tanpa company_id, terhubung lewat departemen, dipakai pegawai.
        $legacy = Unit::create(['name' => 'Logistik Banjarbaru', 'prefix' => 'LOGISTIK-BJB', 'department_id' => $adum->id, 'is_active' => true]);
        $user = User::create([
            'name' => 'Staf Logistik', 'username' => 'logistik', 'email' => 'logistik@example.test',
            'password' => 'password', 'company_id' => $company->id, 'unit_id' => $legacy->id,
        ]);

        // Unit resmi bernomor hasil seed, sempat dipakai satu pegawai.
        $numbered = Unit::create(['name' => 'Unit Logistik', 'kode' => '12', 'prefix' => '12', 'company_id' => $company->id, 'department_id' => $adum->id, 'is_active' => true]);
        $other = User::create([
            'name' => 'Staf Baru', 'username' => 'baru', 'email' => 'baru@example.test',
            'password' => 'password', 'company_id' => $company->id, 'unit_id' => $numbered->id,
        ]);

        // Unit tanpa pasangan tidak boleh tersentuh.
        $untouched = Unit::create(['name' => 'Sekretaris Banjarbaru', 'prefix' => 'SEKRE-BJB', 'department_id' => $adum->id, 'is_active' => true]);

        (require database_path('migrations/2026_11_24_120000_merge_duplicate_rsusm_bjb_units.php'))->up();

        $this->assertDatabaseMissing('units', ['id' => $numbered->id]);
        $this->assertDatabaseHas('units', [
            'id' => $legacy->id, 'name' => 'Logistik Banjarbaru',
            'kode' => '12', 'prefix' => '12', 'company_id' => $company->id,
        ]);
        $this->assertSame($legacy->id, $user->fresh()->unit_id);
        $this->assertSame($legacy->id, $other->fresh()->unit_id);
        $this->assertDatabaseHas('units', ['id' => $untouched->id, 'kode' => null, 'prefix' => 'SEKRE-BJB']);
    }
}
