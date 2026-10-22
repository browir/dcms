<?php

use App\Enum\JenisSurat;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kategori Dokumen jadi satu-satunya master jenis dokumen:
 * - code     : kode jenis HRIS yang dipakai di nomor dokumen (A, C, E, SOP, ...).
 * - kelompok : 'dokumen' (Dokumen Resmi) atau 'surat_keluar' — menentukan menu tampilnya.
 * - company_id boleh kosong = berlaku untuk semua perusahaan.
 * Nomor dokumen (letter_numbers) ikut menyimpan kategorinya.
 */
return new class extends Migration
{
    /** Kategori lama yang namanya bukan kode HRIS. */
    private const ALIAS = ['SK' => 'E', 'SE' => 'C', 'MI' => 'D', 'UND' => 'H'];

    public function up(): void
    {
        Schema::table('document_categories', function (Blueprint $table) {
            $table->string('code', 10)->nullable()->after('prefix');
            $table->string('kelompok', 20)->default('dokumen')->after('code');
            $table->unsignedBigInteger('company_id')->nullable()->change();
            $table->index(['code', 'company_id']);
        });

        Schema::table('letter_numbers', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('jenis_surat')
                ->constrained('document_categories')->nullOnDelete();
        });

        // Kategori lama: kode dari awalan (SK → E, SOP → SOP, ...).
        foreach (DB::table('document_categories')->get() as $category) {
            $prefix = strtoupper(trim((string) $category->prefix));
            $jenis = JenisSurat::tryFrom(self::ALIAS[$prefix] ?? $prefix);

            if ($jenis) {
                DB::table('document_categories')->where('id', $category->id)
                    ->update(['code' => $jenis->value, 'kelompok' => $jenis->kelompokAwal()]);
            }
        }

        // Jenis HRIS yang belum punya kategori → kategori berlaku untuk semua perusahaan.
        $existing = DB::table('document_categories')->whereNotNull('code')->pluck('code')->all();
        foreach (JenisSurat::cases() as $jenis) {
            if (! in_array($jenis->value, $existing, true)) {
                DB::table('document_categories')->insert([
                    'name' => $jenis->label(),
                    'prefix' => $jenis->value,
                    'code' => $jenis->value,
                    'kelompok' => $jenis->kelompokAwal(),
                    'company_id' => null,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Nomor yang sudah terbit: kategori dengan kode yang sama (milik perusahaannya bila ada).
        foreach (DB::table('letter_numbers')->whereNull('category_id')->get(['id', 'jenis_surat', 'company_id']) as $letter) {
            $categoryId = DB::table('document_categories')
                ->where('code', $letter->jenis_surat)
                ->where(fn ($q) => $q->where('company_id', $letter->company_id)->orWhereNull('company_id'))
                ->orderByRaw('company_id is null')
                ->value('id');

            if ($categoryId) {
                DB::table('letter_numbers')->where('id', $letter->id)->update(['category_id' => $categoryId]);
            }
        }

        // Dokumen dengan nomor: kategori mengikuti nomornya.
        foreach (DB::table('documents')->whereNotNull('letter_number_id')->get(['id', 'letter_number_id']) as $document) {
            $categoryId = DB::table('letter_numbers')->where('id', $document->letter_number_id)->value('category_id');

            if ($categoryId) {
                DB::table('documents')->where('id', $document->id)->update(['category_id' => $categoryId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('letter_numbers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });

        DB::table('document_categories')->whereNull('company_id')->whereNotIn('id', DB::table('documents')->select('category_id'))->delete();

        Schema::table('document_categories', function (Blueprint $table) {
            $table->dropIndex(['code', 'company_id']);
            $table->dropColumn(['code', 'kelompok']);
        });
    }
};
