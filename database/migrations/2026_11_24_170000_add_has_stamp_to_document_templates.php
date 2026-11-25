<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('document_templates')) {
            Schema::table('document_templates', function (Blueprint $table) {
                if (! Schema::hasColumn('document_templates', 'has_stamp')) {
                    $table->boolean('has_stamp')->default(false)->after('is_active');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('document_templates')) {
            Schema::table('document_templates', function (Blueprint $table) {
                if (Schema::hasColumn('document_templates', 'has_stamp')) {
                    $table->dropColumn('has_stamp');
                }
            });
        }
    }
};

