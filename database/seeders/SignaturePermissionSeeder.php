<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Signature;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SignaturePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'manage_signature_sketch',
            'guard_name' => 'web',
        ]);

        $this->command->info("✅ Permission created/found: {$permission->name}");

        $roles = Role::whereIn('name', ['super_admin', 'direktur'])->get();
        foreach ($roles as $role) {
            $role->givePermissionTo($permission);
            $this->command->info("   • Assigned to role '{$role->name}'");
        }

        // Buat data template tanda tangan pejabat bawaan (dr. Puga Sharaz Wangi, M.A.R.S.)
        $rsusm = Company::where('code', 'RSUSMBJB')->first() ?? Company::first();
        $userDirektur = User::role('direktur')->first();

        // Siapkan file tanda tangan placeholder transparan jika belum ada
        if (! Storage::disk('public')->exists('signatures/default_director_signature.png')) {
            // 1x1 transparent PNG
            $transparentPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
            Storage::disk('public')->put('signatures/default_director_signature.png', $transparentPng);
        }

        $signature = Signature::firstOrCreate([
            'name' => 'dr. Puga Sharaz Wangi, M.A.R.S.',
            'position' => 'Direktur',
        ], [
            'user_id' => $userDirektur?->id,
            'company_id' => $rsusm?->id,
            'nip' => '01042019232',
            'signature_path' => 'signatures/default_director_signature.png',
            'is_active' => true,
        ]);

        $this->command->info("✅ Template penandatangan default dibuat: {$signature->name} ({$signature->position}) - NIP: {$signature->nip}");
    }
}

