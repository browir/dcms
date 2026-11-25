<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('documents')) {
            Schema::table('documents', function (Blueprint $table) {
                if (! Schema::hasColumn('documents', 'has_stamp')) {
                    $table->boolean('has_stamp')->default(false)->after('signer_title');
                }
                if (! Schema::hasColumn('documents', 'signature_id')) {
                    $table->foreignId('signature_id')->nullable()->after('has_stamp')->constrained('signatures')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('documents')) {
            Schema::table('documents', function (Blueprint $table) {
                if (Schema::hasColumn('documents', 'signature_id')) {
                    $table->dropForeign(['signature_id']);
                    $table->dropColumn('signature_id');
                }
                if (Schema::hasColumn('documents', 'has_stamp')) {
                    $table->dropColumn('has_stamp');
                }
            });
        }
    }
};

