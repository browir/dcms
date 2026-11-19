<?php

namespace App\Services;

use App\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;

class DocumentConversionService
{
    /**
     * Download document as PDF
     */
    public static function downloadAsPdf(Document $document)
    {
        $type = $document->document_type;
        $title = str()->slug($document->title ?: 'document');

        try {
            $result = self::generatePdfResponse($document, $type, $title);

            if ($result !== null) {
                // Catat aktivitas unduh PDF ke audit log
                activity('dokumen')
                    ->performedOn($document)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'nomor_surat' => $document->nomor_surat,
                        'judul'       => $document->judul,
                        'format'      => 'PDF',
                        'ip'          => request()->ip(),
                    ])
                    ->event('downloaded')
                    ->log('Mengunduh dokumen sebagai PDF');
            }

            return $result;
        } catch (Exception $e) {
            Log::error('DocumentConversionService (PDF) Error: '.$e->getMessage());
            Notification::make()
                ->title('Terjadi Kesalahan Server')
                ->body('Gagal memproses dokumen: '.$e->getMessage())
                ->danger()
                ->send();

            return null;
        }
    }

    private static function generatePdfResponse(Document $document, string $type, string $title)
    {
            // 1. Jika dokumen berbasis Form atau Hybrid (punya content HTML)
            if (in_array($type, ['form', 'hybrid']) && $document->content) {
                $html = self::buildHtmlForPdf($document);
                $pdf = Pdf::loadHTML($html);

                return response()->streamDownload(function () use ($pdf) {
                    echo $pdf->output();
                }, "{$title}.pdf");
            }

            // 2. Jika dokumen berupa File Unggahan
            if (in_array($type, ['file', 'hybrid']) && $document->file_path) {
                $disk = Storage::disk('documents');
                if (! $disk->exists($document->file_path)) {
                    throw new Exception('File asli tidak ditemukan di server.');
                }

                $path = $disk->path($document->file_path);
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

                // Jika sudah PDF, langsung download
                if ($ext === 'pdf') {
                    return response()->download($path, "{$title}.pdf");
                }

                // Jika Word Document (.doc, .docx) -> Konversi ke PDF
                if (in_array($ext, ['doc', 'docx'])) {
                    $phpWord = IOFactory::load($path);

                    return response()->streamDownload(function () use ($phpWord) {
                        $pdfWriter = new \PhpOffice\PhpWord\Writer\PDF\DomPDF($phpWord);
                        $pdfWriter->save('php://output');
                    }, "{$title}.pdf");
                }

                // Jika Excel Document (.xls, .xlsx, .csv) -> Konversi ke PDF
                if (in_array($ext, ['xls', 'xlsx', 'csv'])) {
                    $spreadsheet = SpreadsheetIOFactory::load($path);

                    return response()->streamDownload(function () use ($spreadsheet) {
                        $pdfWriter = new \PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf($spreadsheet);
                        $pdfWriter->save('php://output');
                    }, "{$title}.pdf");
                }

                // Jika Image (.jpg, .jpeg, .png, dsb) -> Konversi ke PDF via DOMPDF
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'])) {
                    $imageData = base64_encode(file_get_contents($path));
                    $src = 'data:image/'.$ext.';base64,'.$imageData;
                    $html = "<html><body style='margin:0;padding:0;text-align:center;'><img src='{$src}' style='max-width:100%;'></body></html>";
                    $pdf = Pdf::loadHTML($html);
                    $pdf->setPaper('A4', 'portrait');

                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, "{$title}.pdf");
                }

                Notification::make()
                    ->title('Format Tidak Didukung')
                    ->body("Format ekstensi .{$ext} saat ini belum didukung untuk konversi ke PDF.")
                    ->danger()
                    ->send();

                return null;
            }

            Notification::make()
                ->title('Gagal Konversi')
                ->body('Dokumen ini tidak memiliki data yang valid untuk dikonversi ke PDF.')
                ->danger()
                ->send();

            return null;
        } catch (Exception $e) {
            Log::error('DocumentConversionService (PDF) Error: '.$e->getMessage());
            Notification::make()
                ->title('Terjadi Kesalahan Server')
                ->body('Gagal memproses dokumen: '.$e->getMessage())
                ->danger()
                ->send();

            return null;
        }
    }

    /**
     * Download document as Word (Docx)
     */
    public static function downloadAsWord(Document $document)
    {
        $type = $document->document_type;
        $title = str()->slug($document->title ?: 'document');

        try {
            // 1. Jika dokumen berbasis Form (Content HTML) -> Konversi HTML ke Word
            if (in_array($type, ['form', 'hybrid']) && $document->content) {
                $phpWord = new PhpWord;
                $section = $phpWord->addSection();

                // Header meta
                $section->addText($document->title, ['bold' => true, 'size' => 16]);
                $section->addText('Nomor Dokumen: '.($document->code_number ?? '-'), ['size' => 11]);
                $section->addText('Status: '.ucfirst($document->status), ['size' => 10, 'italic' => true]);
                $section->addTextBreak(1);

                // Body content (HTML to Word)
                Html::addHtml($section, $document->content, false, false);

                // Catat aktivitas unduh Word ke audit log
                activity('dokumen')
                    ->performedOn($document)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'nomor_surat' => $document->nomor_surat,
                        'judul'       => $document->judul,
                        'format'      => 'Word (DOCX)',
                        'ip'          => request()->ip(),
                    ])
                    ->event('downloaded')
                    ->log('Mengunduh dokumen sebagai Word');

                return response()->streamDownload(function () use ($phpWord) {
                    $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
                    $objWriter->save('php://output');
                }, "{$title}.docx");
            }

            // 2. Jika dokumen sudah berupa File Word
            if ($type === 'file' && $document->file_path) {
                $disk = Storage::disk('documents');
                if (! $disk->exists($document->file_path)) {
                    Notification::make()->title('File Hilang')->body('File asli tidak ditemukan di server.')->danger()->send();

                    return null;
                }

                $ext = strtolower(pathinfo($document->file_path, PATHINFO_EXTENSION));
                if (in_array($ext, ['doc', 'docx'])) {
                    // Catat aktivitas unduh Word ke audit log
                    activity('dokumen')
                        ->performedOn($document)
                        ->causedBy(auth()->user())
                        ->withProperties([
                            'nomor_surat' => $document->nomor_surat,
                            'judul'       => $document->judul,
                            'format'      => 'Word (DOCX)',
                            'ip'          => request()->ip(),
                        ])
                        ->event('downloaded')
                        ->log('Mengunduh dokumen sebagai Word');

                    return response()->download($disk->path($document->file_path), "{$title}.{$ext}");
                }
            }

            Notification::make()
                ->title('Format Tidak Didukung')
                ->body('Hanya dokumen berbasis teks/formulir yang dapat dikonversi ke Word (Docx) secara otomatis.')
                ->warning()
                ->send();

            return null;
        } catch (Exception $e) {
            Log::error('DocumentConversionService (Word) Error: '.$e->getMessage());
            Notification::make()
                ->title('Terjadi Kesalahan Server')
                ->body('Gagal mengonversi ke Word: '.$e->getMessage())
                ->danger()
                ->send();

            return null;
        }
    }

    /**
     * Membangun HTML template sederhana untuk DomPDF
     */
    private static function buildHtmlForPdf(Document $document): string
    {
        $title = htmlspecialchars($document->title ?? 'Tanpa Judul');
        $code = htmlspecialchars($document->code_number ?? '-');
        $date = $document->created_at ? $document->created_at->format('d M Y') : '-';
        $version = htmlspecialchars($document->version ?? '1.0');

        return "
        <html>
        <head>
            <meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
            <style>
                body { font-family: sans-serif; line-height: 1.6; color: #333; }
                h1 { font-size: 22px; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
                .meta { margin-bottom: 20px; font-size: 13px; color: #555; background: #f9f9f9; padding: 15px; border-radius: 5px; }
                .content { font-size: 12pt; margin-top: 30px; }
                table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
                table, th, td { border: 1px solid #ddd; }
                th, td { padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
            </style>
        </head>
        <body>
            <h1>{$title}</h1>
            <div class='meta'>
                <strong>Nomor Dokumen:</strong> {$code} <br>
                <strong>Versi:</strong> {$version} <br>
                <strong>Tanggal:</strong> {$date}
            </div>
            <div class='content'>
                {$document->content}
            </div>
        </body>
        </html>
        ";
    }
}
