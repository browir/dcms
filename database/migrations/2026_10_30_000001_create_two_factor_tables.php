<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Kolom 2FA di tabel users ────────────────────────────────────────
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('two_factor_enabled')->default(false)->after('password');
            $table->timestamp('two_factor_enabled_at')->nullable()->after('two_factor_enabled');
            $table->unsignedTinyInteger('default_trust_days')
                  ->default(30)
                  ->comment('Durasi default perangkat tepercaya (hari)')
                  ->after('two_factor_enabled_at');
        });

        // ── 2. Tabel kode OTP ─────────────────────────────────────────────────
        Schema::create('two_factor_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code_hash', 255)->comment('bcrypt hash dari kode 6 digit');
            $table->string('purpose', 30)->default('login')
                  ->comment('login | enable_2fa | disable_2fa | change_email | change_password');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'purpose']);
            $table->index('expires_at');
        });

        // ── 3. Tabel perangkat tepercaya ──────────────────────────────────────
        Schema::create('trusted_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 255)->comment('SHA-256 hash dari token cookie');
            $table->string('device_name', 255)->nullable()->comment('Nama/label perangkat');
            $table->string('user_agent', 512)->nullable();
            $table->string('ip_address', 45)->nullable()->comment('IP saat pendaftaran');
            $table->string('last_ip', 45)->nullable()->comment('IP terakhir dipakai');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('token_hash');
            $table->index(['user_id', 'expires_at']);
        });

        // ── 4. Tabel log aktivitas 2FA ───────────────────────────────────────
        Schema::create('two_factor_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('event', 60)
                  ->comment('code_sent|verified|failed|locked|device_trusted|device_revoked|2fa_enabled|2fa_disabled|admin_reset');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->json('meta')->nullable()->comment('Data tambahan kontekstual');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete()
                  ->comment('Admin yang melakukan reset (jika ada)');
            $table->timestamp('created_at');

            $table->index(['user_id', 'event']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('two_factor_logs');
        Schema::dropIfExists('trusted_devices');
        Schema::dropIfExists('two_factor_codes');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_enabled', 'two_factor_enabled_at', 'default_trust_days']);
        });
    }
};
