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
        // Idempoten: kode_surat bisa sudah dibuat oleh 2026_10_19_000003_add_letter_numbering.
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'kode_surat')) {
                $table->string('kode_surat', 20)->nullable()->after('code');
            }
            if (! Schema::hasColumn('companies', 'legacy_id')) {
                $table->unsignedBigInteger('legacy_id')->nullable()->after('kode_surat');
            }
        });

        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasIndex('companies', 'companies_kode_surat_unique')) {
                $table->unique('kode_surat');
            }
            if (! Schema::hasIndex('companies', 'companies_legacy_id_unique')) {
                $table->unique('legacy_id');
            }
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
