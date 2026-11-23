<?php

use App\Models\Document;
use App\Models\DocumentTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update semua DocumentTemplate agar blok tanda tangan menggunakan 58% spacer dan 42% tanda tangan
        $templates = DocumentTemplate::all();
        foreach ($templates as $template) {
            $updated = str_replace(
                [
                    '<td style="width: 60%; border: none;"></td><td style="width: 40%; border: none; text-align: left;">',
                    '<td style="width: 60%; border: none;"></td>',
                ],
                [
                    '<td style="width: 58%; border: none;">&nbsp;</td><td class="letter-signature-block" style="width: 42%; border: none; text-align: left;">',
                    '<td style="width: 58%; border: none;">&nbsp;</td>',
                ],
                $template->template_content
            );

            if ($updated !== $template->template_content) {
                $template->update(['template_content' => $updated]);
            }
        }

        // 2. Update dokumen existing yang tabel tanda tangannya kehilangan style karena editor
        $documents = Document::whereNotNull('content')->get();
        foreach ($documents as $doc) {
            $original = $doc->content;
            $updated = preg_replace_callback(
                '/<table([^>]*)>\s*(?:<tbody>)?\s*<tr([^>]*)>\s*<td([^>]*)>(?:\s*|<p>\s*<\/p>|<br\s*\/?>|&nbsp;)*<\/td>\s*<td([^>]*)>(.*?)<\/td>\s*<\/tr>\s*(?:<\/tbody>)?\s*<\/table>/is',
                function ($matches) {
                    $signContent = trim($matches[5]);
                    return '<table class="letter-signature-table" style="width: 100%; border: none; border-collapse: collapse; margin-top: 24px; margin-bottom: 20px;">'
                        . '<tbody>'
                        . '<tr>'
                        . '<td style="width: 58%; border: none; padding: 0;">&nbsp;</td>'
                        . '<td class="letter-signature-block" style="width: 42%; border: none; padding: 0; text-align: left; vertical-align: top;">'
                        . $signContent
                        . '</td>'
                        . '</tr>'
                        . '</tbody>'
                        . '</table>';
                },
                $original
            );

            if ($updated !== $original) {
                $doc->update(['content' => $updated]);
            }
        }
    }

    public function down(): void
    {
        // No destructive rollback needed
    }
};

