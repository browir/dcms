<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom kode unit (prefix) dipakai form Unit dan penomoran dokumen
 * (Document::resolveUnitCode), tapi belum pernah dibuat oleh migrasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('units', 'prefix')) {
            return;
        }

        Schema::table('units', function (Blueprint $table) {
            $table->string('prefix', 20)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        // Tidak di-drop: di server kolom ini mungkin sudah ada sebelum migrasi ini.
    }
};
