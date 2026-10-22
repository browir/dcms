<?php

use App\Support\RoleAccess;
use Illuminate\Database\Migrations\Migration;

/**
 * Lengkapi izin dasar role pegawai/kabid/direktur (sebelumnya Kabid tidak bisa membuka
 * daftar Dokumen untuk ACC), tambahkan izin widget Beranda, dan bersihkan izin widget lama.
 * Hanya menambah izin — pengaturan admin di Peran & Hak Akses tidak dicabut.
 */
return new class extends Migration
{
    public function up(): void
    {
        RoleAccess::sync();
    }

    public function down(): void
    {
        // Tidak dicabut: izin bisa sudah diubah admin lewat Peran & Hak Akses.
    }
};
