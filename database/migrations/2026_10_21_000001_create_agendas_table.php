<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kegiatan / agenda unit. Kolom waktu & lokasi sengaja sama dengan tabel meetings
 * (date_time, end_time, location, meeting_location_id) karena keduanya berbagi ruangan
 * — lihat App\Services\RoomSchedule.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agendas', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('kategori', 50)->nullable();
            $table->text('description')->nullable();

            // Penyelenggara
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();

            // Waktu & tempat
            $table->dateTime('date_time')->index();
            $table->dateTime('end_time');
            $table->foreignId('meeting_location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('location')->nullable();
            $table->string('status', 20)->default('scheduled');

            // Pengumuman: jangkauan, surat pemberitahuan (opsional), dan jejak pengiriman
            $table->string('audience', 20)->default('unit');
            $table->foreignId('letter_number_id')->nullable()->unique()->constrained('letter_numbers')->restrictOnDelete();
            $table->text('notice_body')->nullable();
            $table->timestamp('announced_at')->nullable();
            $table->timestamp('whatsapp_sent_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // ID grup WhatsApp (dipakai n8n) untuk pengumuman per unit / perusahaan.
        Schema::table('units', fn (Blueprint $table) => $table->string('whatsapp_group')->nullable()->after('keycloak_code'));
        Schema::table('companies', fn (Blueprint $table) => $table->string('whatsapp_group')->nullable()->after('kode_surat'));
    }

    public function down(): void
    {
        Schema::table('companies', fn (Blueprint $table) => $table->dropColumn('whatsapp_group'));
        Schema::table('units', fn (Blueprint $table) => $table->dropColumn('whatsapp_group'));
        Schema::dropIfExists('agendas');
    }
};
