<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Reset user yang sebelumnya aktif 2FA via email tanpa secret TOTP agar tidak terkunci
        DB::table('users')
            ->where('two_factor_enabled', true)
            ->whereNull('two_factor_secret')
            ->update([
                'two_factor_enabled'    => false,
                'two_factor_enabled_at' => null,
                'two_factor_type'       => 'totp',
                'two_factor_secret'     => null,
            ]);

        // 2. Set default two_factor_type menjadi 'totp'
        if (Schema::hasColumn('users', 'two_factor_type')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('two_factor_type', 20)->default('totp')->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'two_factor_type')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('two_factor_type', 20)->nullable()->default('email')->change();
            });
        }
    }
};

