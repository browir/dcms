<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membuat tabel nomor_surat_counter.
 *
 * Counter ini menyimpan nomor urut terakhir per (perusahaan, tahun surat).
 * Scope counter: HANYA per perusahaan dan tahun — tidak per unit, tidak per jenis.
 * Reset terjadi otomatis saat tahun berganti (baris baru, nomor_terakhir mulai dari 0).
 *
 * Algoritma pengambilan nomor (harus dalam satu transaksi, lihat NomorSuratService):
 *   1. INSERT IGNORE baris baru dengan nomor_terakhir = 0 (jika belum ada)
 *   2. SELECT ... FOR UPDATE untuk lock eksklusif
 *   3. UPDATE nomor_terakhir = nomor_terakhir + 1
 *   4. Gunakan nilai baru sebagai urutan nomor surat
 *
 * PRIMARY KEY komposit (perusahaan_id, tahun) — tidak ada kolom id terpisah.
 * Tidak ada timestamps karena tabel ini hanya di-UPDATE, bukan di-INSERT ulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomor_surat_counter', function (Blueprint $table) {
            // Composite PK — tidak pakai $table->id()
            $table->foreignId('perusahaan_id')->constrained('companies')->cascadeOnDelete();
            $table->smallInteger('tahun')->unsigned();
            $table->unsignedInteger('nomor_terakhir')->default(0);

            $table->primary(['perusahaan_id', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomor_surat_counter');
    }
};
