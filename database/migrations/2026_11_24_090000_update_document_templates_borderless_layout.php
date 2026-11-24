<?php

use App\Models\DocumentTemplate;
use App\Services\DocumentConversionService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $templates = DocumentTemplate::where('code', 'like', 'RSUSM-%')->get();

        foreach ($templates as $tpl) {
            $updated = DocumentConversionService::normalizeDocumentTables($tpl->template_content);
            $updated = DocumentConversionService::alignSignatureBlockToRight($updated);
            $tpl->update(['template_content' => $updated]);
        }
    }

    public function down(): void
    {
        // No-op
    }
};
