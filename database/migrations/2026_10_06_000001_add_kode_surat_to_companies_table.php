<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambah kolom kode_surat dan legacy_id ke tabel companies.
 *
 * - kode_surat : kode singkat yang muncul di nomor surat keluar (misal SGG, RSUSM).
 *               Nullable agar perusahaan yang belum punya kode surat tidak wajib diisi.
 *               UNIQUE karena nomor surat harus bisa di-trace balik ke satu perusahaan.
 * - legacy_id  : id dari sistem lama (HRIS/Laravel) untuk keperluan migrasi data.
 *               Nullable dan UNIQUE (null tidak melanggar unique constraint di MySQL).
 *
 * Kolom 'code' yang sudah ada TIDAK disentuh.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('kode_surat', 20)->nullable()->unique()->after('code');
            $table->unsignedBigInteger('legacy_id')->nullable()->unique()->after('kode_surat');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique(['kode_surat']);
            $table->dropUnique(['legacy_id']);
            $table->dropColumn(['kode_surat', 'legacy_id']);
        });
    }
};
