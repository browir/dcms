<?php

namespace App\Support\Sso;

use App\Models\Department;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Syifa\KeycloakSso\Events\KeycloakLoginSucceeded;

/**
 * Penghubung package syifa/keycloak-sso dengan model DCMS.
 *
 * - fill()     : isi user baru saat login SSO pertama (dipanggil package, config 'user.fill').
 * - isActive() : tolak login akun yang dinonaktifkan di DCMS (config 'user.active_check').
 * - handle()   : setiap login berhasil, sinkronkan unit organisasi dari HRIS dan role
 *                dari client role Keycloak (listener KeycloakLoginSucceeded).
 *
 * Unit organisasi HRIS dipetakan ke struktur DCMS lewat config 'org_levels':
 *   department (HRIS) → departments.keycloak_code, division (HRIS) → units.keycloak_code;
 *   perusahaan mengikuti departemen.
 */
class KeycloakUserSync
{
    /**
     * @param  array<string, mixed>  $claims
     * @param  array<string, Collection<int, Model>>  $orgUnits
     * @return array<string, mixed>
     */
    public static function fill(array $claims, array $orgUnits): array
    {
        $nip = $claims['nip'] ?? $claims['preferred_username'] ?? null;
        $employee = $nip ? hris_employee((string) $nip) : null;
        $username = $nip ?? $claims['preferred_username'] ?? Str::before((string) ($claims['email'] ?? ''), '@');

        return [
            'name' => $employee['name'] ?? $claims['name'] ?? $username,
            // Kolom email wajib & unik; karyawan tanpa email resmi diberi alamat penanda.
            'email' => $employee['email'] ?? $claims['email'] ?? "{$username}@sso.invalid",
            'username' => $username,
            'nip' => $nip,
            // Login lewat SSO; password lokal acak dan tidak pernah dipakai.
            'password' => Str::random(64),
            'email_verified_at' => now(),
            'is_active' => true,
            ...self::organization($orgUnits),
        ];
    }

    public static function isActive(Model $user): bool
    {
        return (bool) ($user->is_active ?? true);
    }

    public function handle(KeycloakLoginSucceeded $event): void
    {
        /** @var User $user */
        $user = $event->user;

        // Sinkron unit organisasi hanya kalau HRIS/Keycloak benar-benar memberi data —
        // kalau HRIS sedang tidak terjangkau, penempatan yang ada tidak dikosongkan.
        $organization = array_filter(self::organization($event->orgUnits));

        // Pindah departemen tapi unit barunya belum terpetakan: jangan biarkan unit lama
        // (milik departemen lain) tetap menempel.
        if (isset($organization['department_id']) && ! isset($organization['unit_id']) && $user->unit_id
            && Unit::query()->whereKey($user->unit_id)->value('department_id') !== $organization['department_id']) {
            $organization['unit_id'] = null;
        }

        if ($organization !== []) {
            $user->forceFill($organization);
        }

        if (blank($user->nip) && filled($nip = $event->claims['nip'] ?? $event->claims['preferred_username'] ?? null)) {
            $user->forceFill(['nip' => $nip]);
        }

        if ($user->isDirty()) {
            $user->save();
        }

        $clientId = config('keycloak-sso.roles.client_id') ?? config('keycloak-sso.client_id');
        $this->syncRoles($user, $event->roles, isset($event->claims['resource_access'][$clientId]));
    }

    /**
     * Role jabatan dari client role Keycloak (config 'dcms.role_map') disinkronkan penuh:
     * ditambah kalau ada, dicabut kalau sudah tidak ada. Role yang tidak ada di peta
     * (diatur manual di DCMS) tidak disentuh. User tanpa role sama sekali diberi role dasar.
     *
     * Kalau Keycloak tidak mengirim data role untuk client ini sama sekali (mapper "Client
     * Roles" belum aktif), role jabatan TIDAK disentuh — supaya role yang sudah ada di DCMS
     * tidak tercabut hanya karena konfigurasi Keycloak belum lengkap.
     *
     * @param  array<int, string>  $keycloakRoles
     */
    private function syncRoles(User $user, array $keycloakRoles, bool $keycloakSentRoles): void
    {
        $map = config('keycloak-sso.dcms.role_map', []);
        $granted = collect($map)->only($keycloakRoles)->values()->unique();
        $managed = $keycloakSentRoles ? collect($map)->values()->unique() : collect();

        foreach ($managed as $role) {
            $exists = Role::query()->where('name', $role)->where('guard_name', 'web')->exists();

            if ($granted->contains($role) && $exists && ! $user->hasRole($role)) {
                $user->assignRole($role);
            } elseif (! $granted->contains($role) && $user->hasRole($role)) {
                $user->removeRole($role);
            }
        }

        $default = config('keycloak-sso.dcms.default_role');

        if ($default && $user->roles()->doesntExist()) {
            $user->assignRole(Role::findOrCreate($default, 'web'));
        }
    }

    /**
     * @param  array<string, Collection<int, Model>>  $orgUnits
     * @return array{company_id: ?int, department_id: ?int, unit_id: ?int}
     */
    private static function organization(array $orgUnits): array
    {
        /** @var Unit|null $unit */
        $unit = ($orgUnits['division'] ?? collect())->first();
        /** @var Department|null $department */
        $department = ($orgUnits['department'] ?? collect())->first() ?? $unit?->department;

        return [
            'company_id' => $department?->company_id,
            'department_id' => $department?->id,
            'unit_id' => $unit?->id,
        ];
    }
}
