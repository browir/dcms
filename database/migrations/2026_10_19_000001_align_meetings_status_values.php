<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Samakan nilai status rapat dengan yang dipakai aplikasi (scheduled/completed/cancelled).
 * Migrasi awal membuat enum draft/ongoing/completed/cancelled, sementara form & logika
 * ruangan memakai 'scheduled' — di server, enum ini sebelumnya diubah manual.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE meetings MODIFY COLUMN status ENUM('draft', 'ongoing', 'scheduled', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled'");
            DB::table('meetings')->whereIn('status', ['draft', 'ongoing'])->update(['status' => 'scheduled']);
            DB::statement("ALTER TABLE meetings MODIFY COLUMN status ENUM('scheduled', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled'");

            return;
        }

        Schema::table('meetings', function (Blueprint $table) {
            $table->string('status')->default('scheduled')->change();
        });

        DB::table('meetings')->whereIn('status', ['draft', 'ongoing'])->update(['status' => 'scheduled']);
    }

    public function down(): void
    {
        // Tidak dikembalikan: nilai 'scheduled' tidak punya padanan di enum lama.
    }
};
