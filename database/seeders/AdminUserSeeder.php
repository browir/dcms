<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Seeder untuk membuat akun admin (role super_admin).
 *
 * Kredensial diambil dari .env: ADMIN_NAME, ADMIN_USERNAME, ADMIN_EMAIL, ADMIN_PASSWORD
 *
 * Jalankan: php artisan db:seed --class=AdminUserSeeder
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (blank($email) || blank($password)) {
            $this->command->error('❌ ADMIN_EMAIL dan ADMIN_PASSWORD wajib diisi di .env.');

            return;
        }

        $admin = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Administrator'),
                'username' => env('ADMIN_USERNAME', 'admin'),
                'password' => $password,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $role = Role::firstOrCreate([
            'name' => config('filament-shield.super_admin.name', 'super_admin'),
            'guard_name' => 'web',
        ]);

        if (! $admin->hasRole($role)) {
            $admin->assignRole($role);
        }

        $this->command->info("✅ Akun admin siap: {$admin->email} (username: {$admin->username})");
    }
}
