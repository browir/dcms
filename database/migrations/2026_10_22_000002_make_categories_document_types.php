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
 *
 * Aman dijalankan ulang dan untuk skema yang berbeda dari migration awal (mis. di server
 * kolom company_id pernah dihapus): setiap kolom/index dicek dulu.
 */
return new class extends Migration
{
    /** Kategori lama yang namanya bukan kode HRIS. */
    private const ALIAS = ['SK' => 'E', 'SE' => 'C', 'MI' => 'D', 'UND' => 'H'];

    public function up(): void
    {
        $table = 'document_categories';

        Schema::table($table, function (Blueprint $t) use ($table) {
            if (! Schema::hasColumn($table, 'code')) {
                $t->string('code', 10)->nullable();
            }
            if (! Schema::hasColumn($table, 'kelompok')) {
                $t->string('kelompok', 20)->default('dokumen');
            }
        });

        if (Schema::hasColumn($table, 'company_id')) {
            Schema::table($table, fn (Blueprint $t) => $t->unsignedBigInteger('company_id')->nullable()->change());
        } else {
            Schema::table($table, fn (Blueprint $t) => $t->foreignId('company_id')->nullable()->constrained()->nullOnDelete());
        }

        if (! Schema::hasIndex($table, ['code', 'company_id'])) {
            Schema::table($table, fn (Blueprint $t) => $t->index(['code', 'company_id']));
        }

        if (! Schema::hasColumn('letter_numbers', 'category_id')) {
            Schema::table('letter_numbers', function (Blueprint $t) {
                $t->foreignId('category_id')->nullable()->after('jenis_surat')
                    ->constrained('document_categories')->nullOnDelete();
            });
        }

        $columns = Schema::getColumnListing($table);
        $only = fn (array $values): array => array_intersect_key($values, array_flip($columns));

        // Kategori lama: kode dari awalan atau nama (SK → E, SOP → SOP, ...).
        foreach (DB::table($table)->whereNull('code')->get() as $category) {
            $key = strtoupper(trim((string) ($category->prefix ?? $category->name ?? '')));
            $jenis = JenisSurat::tryFrom(self::ALIAS[$key] ?? $key);

            if ($jenis) {
                DB::table($table)->where('id', $category->id)
                    ->update(['code' => $jenis->value, 'kelompok' => $jenis->kelompokAwal()]);
            }
        }

        // Jenis HRIS yang belum punya kategori → kategori berlaku untuk semua perusahaan.
        $existing = DB::table($table)->whereNotNull('code')->pluck('code')->all();
        foreach (JenisSurat::cases() as $jenis) {
            if (! in_array($jenis->value, $existing, true)) {
                DB::table($table)->insert($only([
                    'name' => $jenis->label(),
                    'prefix' => $jenis->value,
                    'code' => $jenis->value,
                    'kelompok' => $jenis->kelompokAwal(),
                    'company_id' => null,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        // Nomor yang sudah terbit: kategori dengan kode yang sama (milik perusahaannya bila ada).
        foreach (DB::table('letter_numbers')->whereNull('category_id')->get(['id', 'jenis_surat', 'company_id']) as $letter) {
            $categoryId = DB::table($table)
                ->where('code', $letter->jenis_surat)
                ->where(fn ($q) => $q->where('company_id', $letter->company_id)->orWhereNull('company_id'))
                ->orderByRaw('company_id is null')
                ->value('id');

            if ($categoryId) {
                DB::table('letter_numbers')->where('id', $letter->id)->update(['category_id' => $categoryId]);
            }
        }

        // Dokumen dengan nomor: kategori mengikuti nomornya.
        if (Schema::hasColumn('documents', 'category_id')) {
            foreach (DB::table('documents')->whereNotNull('letter_number_id')->get(['id', 'letter_number_id']) as $document) {
                $categoryId = DB::table('letter_numbers')->where('id', $document->letter_number_id)->value('category_id');

                if ($categoryId) {
                    DB::table('documents')->where('id', $document->id)->update(['category_id' => $categoryId]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('letter_numbers', 'category_id')) {
            Schema::table('letter_numbers', fn (Blueprint $t) => $t->dropConstrainedForeignId('category_id'));
        }

        DB::table('document_categories')->whereNull('company_id')->whereNotIn('id', DB::table('documents')->whereNotNull('category_id')->select('category_id'))->delete();

        Schema::table('document_categories', function (Blueprint $t) {
            $t->dropIndex(['code', 'company_id']);
            $t->dropColumn(['code', 'kelompok']);
        });
    }
};
