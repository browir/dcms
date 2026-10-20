<?php

use App\Support\Sso\KeycloakUserSync;

/*
 * Login SSO Keycloak — package syifa/keycloak-sso (https://github.com/ahyaghifari/sgg-sso).
 * Isian .env lihat README bagian "Login SSO (Keycloak)".
 *
 * Catatan: callable ditulis sebagai [Kelas::class, 'method'] (bukan closure) supaya
 * `php artisan config:cache` tetap bisa dipakai di server.
 */
return [

    /*
    | Kredensial client Keycloak khusus DCMS (Standard Flow + Client Authentication).
    | SSO dianggap aktif kalau KEYCLOAK_CLIENT_ID terisi — tombol "Masuk dengan SSO"
    | muncul di halaman login dan pendaftaran akun mandiri dimatikan.
    */
    'client_id' => env('KEYCLOAK_CLIENT_ID'),
    'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),
    'redirect_uri' => env('KEYCLOAK_REDIRECT_URI', env('APP_URL').'/auth/keycloak/callback'),
    'base_url' => env('KEYCLOAK_BASE_URL'),
    'realm' => env('KEYCLOAK_REALM'),

    'routes' => [
        'prefix' => 'auth/keycloak',
        'middleware' => ['web'],
        'redirect_after_login' => '/admin',
        // Harus terdaftar di Keycloak → client → "Valid post logout redirect URIs".
        'redirect_after_logout' => '/admin/login',
    ],

    /*
    | User lokal: dicocokkan berurutan via keycloak_sub → NIP (claim nip / preferred_username)
    | → email. Belum ada = dibuat otomatis (provision) dengan data resmi dari HRIS.
    */
    'user' => [
        'model' => \App\Models\User::class,
        'sub_column' => 'keycloak_sub',
        'match_by' => ['keycloak_sub', 'nip', 'email'],
        'provision' => env('KEYCLOAK_PROVISION', true),
        'fill' => [KeycloakUserSync::class, 'fill'],
        'active_check' => [KeycloakUserSync::class, 'isActive'],
    ],

    /*
    | API direktori karyawan HRIS (nama/email/unit organisasi resmi per NIP).
    | Token dari panel HRIS /keycloak → Aplikasi Terhubung.
    */
    'hris_directory' => [
        'base_url' => env('HRIS_API_BASE_URL'),
        'token' => env('HRIS_API_TOKEN'),
    ],

    /*
    | Unit organisasi HRIS → struktur DCMS. Isi "Kode HRIS" di Data Master → Departemen
    | dan Unit sesuai kode di HRIS /publik/unit-organisasi.
    |   department (HRIS) → Departemen DCMS,  division (HRIS) → Unit DCMS.
    */
    'org_levels' => [
        ['model' => \App\Models\Department::class, 'code_column' => 'keycloak_code', 'level' => 'department'],
        ['model' => \App\Models\Unit::class, 'code_column' => 'keycloak_code', 'level' => 'division'],
    ],

    /*
    | Client role Keycloak (resource_access.<client>.roles), mapper "Client Roles"
    | dengan "Add to userinfo" aktif.
    */
    'roles' => [
        'enabled' => true,
        'client_id' => null,
    ],

    /*
    | Khusus DCMS (dipakai KeycloakUserSync):
    | - default_role : role yang diberikan ke user SSO yang belum punya role apa pun.
    | - role_map     : client role Keycloak => role DCMS. Disinkronkan tiap login (ditambah
    |                  / dicabut). Role DCMS di luar peta ini diatur manual di DCMS.
    */
    'dcms' => [
        'default_role' => env('KEYCLOAK_DEFAULT_ROLE', 'pegawai'),
        'role_map' => [
            'dcms-admin' => 'super_admin',
            'dcms-direktur' => 'direktur',
            'dcms-kabid' => 'kabid',
        ],
    ],

];
