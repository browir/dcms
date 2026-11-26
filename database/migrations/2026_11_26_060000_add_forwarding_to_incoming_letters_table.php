<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incoming_letters', function (Blueprint $table) {
            // Kolom penerima terusan (user yang surat diteruskan kepadanya)
            $table->foreignId('forwarded_to')->nullable()->after('completed_at')
                ->constrained('users')->nullOnDelete()
                ->comment('User yang menjadi tujuan terusan surat masuk');
            $table->timestamp('forwarded_at')->nullable()->after('forwarded_to');
            $table->text('forward_notes')->nullable()->after('forwarded_at')
                ->comment('Catatan disposisi/terusan dari pengirim terusan');
            $table->foreignId('forwarded_by')->nullable()->after('forward_notes')
                ->constrained('users')->nullOnDelete()
                ->comment('User yang meneruskan surat');
        });
    }

    public function down(): void
    {
        Schema::table('incoming_letters', function (Blueprint $table) {
            $table->dropForeign(['forwarded_to']);
            $table->dropForeign(['forwarded_by']);
            $table->dropColumn(['forwarded_to', 'forwarded_at', 'forward_notes', 'forwarded_by']);
        });
    }
};

