<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $seeder = require __DIR__ . '/2026_11_16_000002_seed_rsusm_document_templates.php';
        $seeder->up();
    }

    public function down(): void
    {
        // No-op
    }
};
