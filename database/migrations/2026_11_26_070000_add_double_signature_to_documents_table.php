<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->boolean('has_double_signature')->default(false)->after('has_stamp');
            $table->foreignId('kabid_signature_id')->nullable()->after('signature_id')->constrained('signatures')->nullOnDelete();
            $table->string('kabid_signer_name')->nullable()->after('signer_title');
            $table->string('kabid_signer_nip')->nullable()->after('kabid_signer_name');
            $table->string('kabid_signer_title')->nullable()->after('kabid_signer_nip');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('kabid_signature_id');
            $table->dropColumn([
                'has_double_signature',
                'kabid_signer_name',
                'kabid_signer_nip',
                'kabid_signer_title',
            ]);
        });
    }
};

