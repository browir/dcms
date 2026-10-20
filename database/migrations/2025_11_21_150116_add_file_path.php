<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kolom content dipakai Meeting tapi belum pernah dibuat oleh migrasi mana pun
        if (! Schema::hasColumn('meetings', 'content')) {
            Schema::table('meetings', function (Blueprint $table) {
                $table->text('content')->nullable()->after('agenda');
            });
        }

        Schema::table('meetings', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn('file_path');
        });
    }
};
