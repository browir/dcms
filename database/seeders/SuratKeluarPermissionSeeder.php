<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seeder untuk dua permission fitur Surat Keluar.
 *
 * Format mengikuti konvensi Shield di proyek ini: "{Action}:{Resource}" (pascal, separator ':').
 *   ViewAny:SuratKeluar  — melihat daftar/riwayat surat keluar
 *   Create:SuratKeluar   — menggunakan tombol "Buat Nomor Surat" (modal)
 *
 * Permission diberikan ke role super_admin secara default.
 * Untuk memberikan ke role lain, gunakan: /admin/shield/roles
 *
 * Seeder ini idempoten — aman dijalankan berulang kali.
 *
 * Jalankan:
 *   php artisan db:seed --class=SuratKeluarPermissionSeeder
 */
class SuratKeluarPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Buat permission ────────────────────────────────────────────────
        $viewAny = Permission::firstOrCreate([
            'name'       => 'ViewAny:SuratKeluar',
            'guard_name' => 'web',
        ]);

        $create = Permission::firstOrCreate([
            'name'       => 'Create:SuratKeluar',
            'guard_name' => 'web',
        ]);

        $this->command->info('✅ Permission dibuat/ditemukan:');
        $this->command->line("   • {$viewAny->name} (id={$viewAny->id})");
        $this->command->line("   • {$create->name} (id={$create->id})");

        // ── 2. Assign ke super_admin ──────────────────────────────────────────
        $superAdmin = Role::where('name', 'super_admin')->first();

        if ($superAdmin) {
            $superAdmin->givePermissionTo([$viewAny, $create]);
            $this->command->newLine();
            $this->command->info("✅ Kedua permission diberikan ke role 'super_admin' (id={$superAdmin->id}).");
        } else {
            $this->command->newLine();
            $this->command->warn("⚠️ Role 'super_admin' tidak ditemukan. Permission dibuat tapi belum di-assign ke role mana pun.");
        }

        // ── 3. Panduan ────────────────────────────────────────────────────────
        $this->command->newLine();
        $this->command->comment('Untuk memberikan permission ke role lain:');
        $this->command->comment('  Buka /admin/shield/roles → edit role → centang permission di tab "Custom Permissions".');
    }
}
