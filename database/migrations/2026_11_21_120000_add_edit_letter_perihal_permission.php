<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private string $permissionName = 'edit_letter_perihal';
    private string $guardName = 'web';

    public function up(): void
    {
        $now = now();

        $exists = DB::table('permissions')
            ->where('name', $this->permissionName)
            ->where('guard_name', $this->guardName)
            ->exists();

        if (! $exists) {
            DB::table('permissions')->insert([
                'name' => $this->permissionName,
                'guard_name' => $this->guardName,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Berikan secara default ke super_admin
        $superAdminRole = DB::table('roles')
            ->where('name', 'super_admin')
            ->where('guard_name', $this->guardName)
            ->first();

        if ($superAdminRole) {
            $permission = DB::table('permissions')
                ->where('name', $this->permissionName)
                ->where('guard_name', $this->guardName)
                ->first();

            if ($permission) {
                $alreadyAssigned = DB::table('role_has_permissions')
                    ->where('permission_id', $permission->id)
                    ->where('role_id', $superAdminRole->id)
                    ->exists();

                if (! $alreadyAssigned) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $permission->id,
                        'role_id' => $superAdminRole->id,
                    ]);
                }
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permission = DB::table('permissions')
            ->where('name', $this->permissionName)
            ->where('guard_name', $this->guardName)
            ->first();

        if ($permission) {
            DB::table('role_has_permissions')->where('permission_id', $permission->id)->delete();
            DB::table('permissions')->where('id', $permission->id)->delete();
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
