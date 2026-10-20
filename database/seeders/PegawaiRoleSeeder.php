<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Role dasar untuk semua karyawan yang masuk lewat SSO Keycloak
 * (config keycloak-sso.dcms.default_role). Cakupan data tetap dibatasi scope
 * access() di model — pegawai hanya melihat dokumen/rapat unit & undangannya.
 *
 * Jalankan: php artisan db:seed --class=PegawaiRoleSeeder
 */
class PegawaiRoleSeeder extends Seeder
{
    public const PERMISSIONS = [
        // Beranda
        'View:Dashboard',
        'View:EmployeeActivityHubWidget',
        'View:MyCalendarWidget',
        // Dokumen: lihat, ambil nomor & isi dokumen, revisi dokumen sendiri, usulan revisi
        'ViewAny:Document',
        'View:Document',
        'Create:Document',
        'Update:Document',
        'ViewAny:DocumentChangeRequest',
        'View:DocumentChangeRequest',
        'Create:DocumentChangeRequest',
        'View:ComplianceHub',
        'View:MyBookmarks',
        // Rapat: buat & ikuti rapat; notulis perlu Update untuk mengisi notulen
        'ViewAny:Meeting',
        'View:Meeting',
        'Create:Meeting',
        'Update:Meeting',
        // Pengingat pribadi
        'access_reminder_hub',
        'create_own_reminder',
        'create_personal_reminder',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::findOrCreate(config('keycloak-sso.dcms.default_role', 'pegawai'), 'web');

        $role->givePermissionTo(
            collect(self::PERMISSIONS)->map(fn (string $name) => Permission::findOrCreate($name, 'web'))
        );

        $this->command?->info("✅ Role {$role->name}: ".count(self::PERMISSIONS).' permission.');
    }
}
