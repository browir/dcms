<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Penomoran surat format HRIS: {urut}/{jenis}.{kode_unit}/{kode_surat_perusahaan}/{bulan_romawi}/{tahun}
 * Lihat PENOMORAN-SURAT-HRIS.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Kode penerbit di nomor surat (mis. SGG, RSUSM-BRB). Kosong = pakai `code`.
            $table->string('kode_surat', 20)->nullable()->unique()->after('code');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->string('jenis_surat', 10)->nullable()->after('category_id');
            $table->date('tanggal_surat')->nullable()->index()->after('jenis_surat');
        });

        // Counter nomor urut: satu urutan per (perusahaan, tahun tanggal surat),
        // dipakai bersama semua jenis surat dan semua unit.
        Schema::create('letter_number_counters', function (Blueprint $table) {
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->primary(['company_id', 'year']);
        });

        // Jaring pengaman kedua terhadap nomor kembar. Dilewati bila data lama sudah ada yang kembar.
        $duplicates = DB::table('documents')
            ->whereNotNull('code_number')
            ->select('code_number')
            ->groupBy('code_number')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('code_number');

        if ($duplicates->isEmpty()) {
            Schema::table('documents', function (Blueprint $table) {
                $table->unique('code_number');
            });
        } else {
            Log::warning('Indeks unik documents.code_number tidak dibuat: ada nomor kembar.', [
                'code_numbers' => $duplicates->take(20)->all(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('documents', 'documents_code_number_unique')) {
            Schema::table('documents', fn (Blueprint $table) => $table->dropUnique('documents_code_number_unique'));
        }

        Schema::dropIfExists('letter_number_counters');

        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['tanggal_surat']);
            $table->dropColumn(['jenis_surat', 'tanggal_surat']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique(['kode_surat']);
            $table->dropColumn('kode_surat');
        });
    }
};
