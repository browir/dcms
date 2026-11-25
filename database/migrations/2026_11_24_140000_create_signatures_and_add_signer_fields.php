<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom signer ke tabel documents
        if (Schema::hasTable('documents')) {
            Schema::table('documents', function (Blueprint $table) {
                if (! Schema::hasColumn('documents', 'signer_name')) {
                    $table->string('signer_name')->nullable()->after('content');
                }
                if (! Schema::hasColumn('documents', 'signer_nip')) {
                    $table->string('signer_nip')->nullable()->after('signer_name');
                }
                if (! Schema::hasColumn('documents', 'signer_title')) {
                    $table->string('signer_title')->nullable()->after('signer_nip');
                }
            });
        }

        // 2. Tambah kolom signature_path ke tabel users jika belum ada
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'signature_path')) {
                    $table->string('signature_path')->nullable()->after('avatar_url');
                }
            });
        }

        // 3. Buat tabel signatures untuk menyimpan sketch / gambar tanda tangan pejabat
        if (! Schema::hasTable('signatures')) {
            Schema::create('signatures', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->string('name');
                $table->string('nip')->nullable();
                $table->string('position')->default('Direktur');
                $table->string('signature_path');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('documents')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->dropColumn(['signer_name', 'signer_nip', 'signer_title']);
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('signature_path');
            });
        }

        Schema::dropIfExists('signatures');
    }
};

