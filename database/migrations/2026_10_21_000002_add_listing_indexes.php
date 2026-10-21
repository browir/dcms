<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indeks untuk kolom yang dipakai daftar data (tab, filter, urutan): status, tanggal,
 * dan organisasi. Indeks yang sudah ada (mis. dibuat manual di server) dilewati.
 */
return new class extends Migration
{
    private const INDEXES = [
        'documents' => [['status'], ['created_at'], ['expires_at'], ['is_mandatory_read']],
        'meetings' => [['date_time'], ['status', 'date_time'], ['company_id'], ['department_id'], ['unit_id']],
        'agendas' => [['end_time'], ['status']],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            foreach ($indexes as $columns) {
                if (Schema::hasIndex($table, $columns)) {
                    continue;
                }

                Schema::table($table, fn (Blueprint $t) => $t->index($columns));
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            foreach ($indexes as $columns) {
                $name = $table.'_'.implode('_', $columns).'_index';

                if (Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
                }
            }
        }
    }
};
