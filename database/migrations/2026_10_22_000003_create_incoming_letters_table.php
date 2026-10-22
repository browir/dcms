<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Surat Masuk: surat dari pihak luar, dicatat dengan nomor agenda masuk dan ditujukan ke unit. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('incoming_letters')) {
            return;
        }

        Schema::create('incoming_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('nomor_agenda', 50);
            $table->unsignedInteger('nomor_urut');
            $table->unsignedSmallInteger('tahun');

            $table->string('pengirim');
            $table->string('nomor_surat')->nullable()->comment('Nomor surat dari pengirim');
            $table->date('tanggal_surat')->nullable();
            $table->date('tanggal_terima');
            $table->string('perihal');
            $table->text('ringkasan')->nullable();
            $table->string('sifat', 20)->default('biasa');
            $table->string('file_path')->nullable();

            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('status', 20)->default('baru');
            $table->timestamp('read_at')->nullable();
            $table->foreignId('read_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'tahun', 'nomor_urut']);
            $table->index(['unit_id', 'status']);
            $table->index('tanggal_terima');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incoming_letters');
    }
};
