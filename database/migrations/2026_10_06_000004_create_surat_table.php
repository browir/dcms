<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membuat tabel surat untuk surat keluar (dan surat masuk di masa depan).
 *
 * Catatan desain:
 * - Perusahaan TIDAK disimpan langsung di tabel ini; resolve via surat → unit → company.
 * - nomor      : UNIQUE constraint sebagai pengaman kedua (selain lock transaksi).
 * - nomor_urut : hanya diisi untuk surat keluar; NULL untuk surat masuk.
 * - jenis      : kode string dari enum JenisSurat (A, B, C, ..., SPKK, SKK).
 *               Disimpan sebagai string bukan enum DB agar mudah ditambah jenis baru.
 * - unit_id    : wajib (NOT NULL). Untuk mencegah penghapusan unit yang sudah dipakai
 *               surat, gunakan restrictOnDelete().
 * - dibuat_oleh_id   : FK ke users, set NULL jika user dihapus (bukan cascade).
 * - dibuat_oleh_nama : snapshot nama user saat surat dibuat, tahan terhadap rename/delete user.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat', function (Blueprint $table) {
            $table->id();

            // Referensi ke sistem lama, nullable & unique
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();

            // Nomor surat — UNIQUE constraint sebagai pengaman kedua
            $table->string('nomor')->unique();

            // Tipe dan status
            $table->enum('tipe_surat', ['masuk', 'keluar'])->default('keluar');
            $table->enum('status', ['Tersimpan', 'Draft'])->default('Tersimpan');

            // Nomor urut (hanya untuk surat keluar, NULL untuk surat masuk)
            $table->unsignedInteger('nomor_urut')->nullable();

            // Jenis surat — kode dari enum JenisSurat (misal: A, B, SP, MCU, SPKK)
            $table->string('jenis', 10);

            // Organisasi — unit wajib; perusahaan di-resolve via unit → company
            $table->foreignId('unit_id')
                ->constrained('units')
                ->restrictOnDelete(); // unit yang sudah dipakai surat tidak boleh dihapus

            // Tanggal surat (dari mana bulan, tahun, dan counter diambil)
            $table->date('tanggal')->index();

            // Konten
            $table->string('judul_surat');
            $table->text('perihal')->nullable();
            $table->text('deskripsi')->nullable();

            // Pembuat — FK nullable + snapshot nama
            $table->foreignId('dibuat_oleh_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('dibuat_oleh_nama'); // snapshot, tidak berubah meski user di-rename/hapus

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat');
    }
};
