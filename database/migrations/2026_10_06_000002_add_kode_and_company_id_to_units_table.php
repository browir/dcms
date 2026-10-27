<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambah kolom kode dan company_id ke tabel units.
 *
 * - kode       : kode unit yang muncul di nomor surat keluar (misal 3C, HRD).
 *               Nullable di DB agar unit lama bisa diisi bertahap.
 *               Maksimal 20 karakter sesuai spesifikasi. TIDAK unik (banyak unit
 *               lintas perusahaan bisa punya kode sama).
 *               CATATAN: NomorSuratService akan melempar exception jika kode NULL
 *               saat generate nomor surat — validasi di layer service, bukan DB.
 *
 * - company_id : FK langsung ke companies. Ini adalah shortcut untuk keperluan
 *               surat keluar (surat → unit → company). Nullable agar unit lama
 *               yang belum di-assign tetap valid.
 *               PERHATIAN KONSISTENSI: unit sudah punya relasi ke companies VIA
 *               department (unit.department_id → departments.company_id). Kolom
 *               company_id ini adalah shortcut INDEPENDEN. Pastikan saat entry data
 *               unit.company_id = unit.department.company_id agar tidak inkonsisten.
 *               Constraint ini dijaga di layer aplikasi/UI, bukan DB.
 *
 * legacy_id    : id unit dari sistem lama, nullable, unique per perusahaan.
 *               UNIQUE(perusahaan_id, legacy_id) sesuai spesifikasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->string('kode', 20)->nullable()->after('name');
            $table->foreignId('company_id')
                ->nullable()
                ->after('kode')
                ->constrained('companies')
                ->nullOnDelete();
            $table->unsignedBigInteger('legacy_id')->nullable()->after('company_id');

            // UNIQUE(company_id, legacy_id) — null tidak melanggar unique di MySQL
            $table->unique(['company_id', 'legacy_id'], 'units_company_id_legacy_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropUnique('units_company_id_legacy_id_unique');
            $table->dropForeign(['company_id']);
            $table->dropColumn(['kode', 'company_id', 'legacy_id']);
        });
    }
};
