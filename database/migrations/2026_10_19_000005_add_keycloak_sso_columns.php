<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom untuk login SSO Keycloak (package syifa/keycloak-sso):
 * - users.keycloak_sub : penghubung tetap ke akun Keycloak (diisi saat login SSO pertama).
 * - users.nip          : NIP karyawan, untuk mencocokkan akun lama & ambil data HRIS.
 * - departments/units.keycloak_code : kode unit organisasi resmi dari HRIS
 *   (/publik/unit-organisasi), dicocokkan ke org_unit karyawan saat login.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('keycloak_sub')->nullable()->unique()->after('username');
            $table->string('nip', 50)->nullable()->unique()->after('keycloak_sub');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->string('keycloak_code')->nullable()->unique()->after('code');
        });

        Schema::table('units', function (Blueprint $table) {
            $table->string('keycloak_code')->nullable()->unique()->after('prefix');
        });
    }

    public function down(): void
    {
        Schema::table('units', fn (Blueprint $table) => $table->dropColumn('keycloak_code'));
        Schema::table('departments', fn (Blueprint $table) => $table->dropColumn('keycloak_code'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['keycloak_sub', 'nip']));
    }
};
