<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tambahkan kolom numbering_type ke letter_numbers untuk membedakan
 * nomor yang dihasilkan otomatis (auto) dan yang diisi manual (manual).
 *
 * Migrasi ini backward compatible:
 *  - Semua data lama otomatis mendapat nilai 'auto'.
 *  - Dapat di-rollback dengan aman.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letter_numbers', function (Blueprint $table) {
            // Tambahkan setelah kolom nomor_urut.
            $table->enum('numbering_type', ['auto', 'manual'])
                ->default('auto')
                ->after('nomor_urut');

            // Jadikan nomor_urut nullable untuk nomor manual yang tidak memiliki counter
            $table->unsignedInteger('nomor_urut')->nullable()->change();

            $table->index('numbering_type');
        });

        // Data lama sudah benar (default 'auto'), tapi pastikan secara eksplisit.
        DB::table('letter_numbers')->whereNull('numbering_type')->update(['numbering_type' => 'auto']);
    }

    public function down(): void
    {
        Schema::table('letter_numbers', function (Blueprint $table) {
            $table->dropIndex(['numbering_type']);
            $table->dropColumn('numbering_type');
            $table->unsignedInteger('nomor_urut')->nullable(false)->change();
        });
    }
};

