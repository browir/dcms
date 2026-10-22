<?php

namespace Database\Seeders;

use App\Support\RoleAccess;
use Illuminate\Database\Seeder;

/**
 * Role dasar untuk semua karyawan yang masuk lewat SSO Keycloak
 * (config keycloak-sso.dcms.default_role), sekaligus izin dasar kabid & direktur.
 * Daftar izinnya ada di App\Support\RoleAccess. Cakupan data tetap dibatasi scope
 * access() di model — pegawai hanya melihat dokumen/rapat unit & undangannya.
 *
 * Jalankan: php artisan db:seed --class=PegawaiRoleSeeder
 */
class PegawaiRoleSeeder extends Seeder
{
    public function run(): void
    {
        RoleAccess::sync();

        $this->command?->info('✅ Izin role pegawai, kabid, direktur & super_admin disinkronkan.');
    }
}
