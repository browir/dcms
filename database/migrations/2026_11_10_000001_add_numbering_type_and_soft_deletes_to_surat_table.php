<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('surat', function (Blueprint $table) {
            if (! Schema::hasColumn('surat', 'numbering_type')) {
                $table->string('numbering_type', 20)
                    ->default('auto')
                    ->after('nomor_urut')
                    ->index();
            }

            if (! Schema::hasColumn('surat', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat', function (Blueprint $table) {
            if (Schema::hasColumn('surat', 'numbering_type')) {
                $table->dropIndex(['numbering_type']);
                $table->dropColumn('numbering_type');
            }

            if (Schema::hasColumn('surat', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};

