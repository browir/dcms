<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Register nomor surat: nomor diambil dulu (seperti tombol "Buat Nomor Surat" di HRIS),
 * baru dokumen resminya diisi ke nomor itu. Satu nomor = satu dokumen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letter_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('nomor')->unique();
            $table->unsignedInteger('nomor_urut');
            $table->string('jenis_surat', 10);
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table->date('tanggal_surat')->index();
            $table->string('judul_surat');
            $table->string('perihal')->nullable();
            $table->text('deskripsi')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'tanggal_surat']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('letter_number_id')->nullable()->unique()->after('code_number')
                ->constrained('letter_numbers')->restrictOnDelete();
        });

        // Dokumen yang sudah bernomor format baru (punya jenis & tanggal surat) dicatat ke register.
        DB::table('documents')
            ->whereNotNull('jenis_surat')
            ->whereNotNull('code_number')
            ->whereNull('letter_number_id')
            ->orderBy('id')
            ->get()
            ->each(function ($doc) {
                $id = DB::table('letter_numbers')->insertGetId([
                    'nomor' => $doc->code_number,
                    'nomor_urut' => (int) strtok($doc->code_number, '/'),
                    'jenis_surat' => $doc->jenis_surat,
                    'company_id' => $doc->company_id,
                    'department_id' => $doc->department_id,
                    'unit_id' => $doc->unit_id,
                    'tanggal_surat' => $doc->tanggal_surat ?? $doc->created_at,
                    'judul_surat' => $doc->title,
                    'created_by' => $doc->user_id,
                    'created_at' => $doc->created_at,
                    'updated_at' => $doc->created_at,
                ]);

                DB::table('documents')->where('id', $doc->id)->update(['letter_number_id' => $id]);
            });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('letter_number_id');
        });

        Schema::dropIfExists('letter_numbers');
    }
};
