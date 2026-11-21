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
        return self::handlePdfResponse($document, inline: false);
    }

    /**
     * Stream document as PDF for inline viewing / printing in browser
     */
    public static function streamPdf(Document $document)
    {
        return self::handlePdfResponse($document, inline: true);
    }

    private static function handlePdfResponse(Document $document, bool $inline = false)
    {
        $type = $document->document_type;
        $title = str()->slug($document->title ?: 'document');

        try {
            $result = self::generatePdfResponse($document, $type, $title, $inline);

            if ($result !== null) {
                // Catat aktivitas unduh/lihat PDF ke audit log
                activity('dokumen')
                    ->performedOn($document)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'nomor_surat' => $document->nomor_surat,
                        'judul'       => $document->judul,
                        'format'      => 'PDF',
                        'mode'        => $inline ? 'inline' : 'attachment',
                        'ip'          => request()->ip(),
                    ])
                    ->event($inline ? 'viewed' : 'downloaded')
                    ->log($inline ? 'Melihat pratinjau PDF dokumen' : 'Mengunduh dokumen sebagai PDF');
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

    private static function generatePdfResponse(Document $document, string $type, string $title, bool $inline = false)
    {
        try {
            // 1. Jika dokumen berbasis Form atau Hybrid (punya content HTML)
            if (in_array($type, ['form', 'hybrid']) && $document->content) {
                $html = self::buildHtmlForPdf($document);
                $pdf = Pdf::loadHTML($html);
                $pdf->setPaper('A4', 'portrait');

                if ($inline) {
                    return $pdf->stream("{$title}.pdf");
                }

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

                // Jika sudah PDF, langsung sajikan
                if ($ext === 'pdf') {
                    if ($inline) {
                        return response()->file($path, [
                            'Content-Type' => 'application/pdf',
                            'Content-Disposition' => 'inline; filename="'.$title.'.pdf"',
                        ]);
                    }
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

                    if ($inline) {
                        return $pdf->stream("{$title}.pdf");
                    }

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
     * Resolves letterhead assets (kop surat, watermark, footer waves) as base64 data URIs.
     */
    public static function getLetterheadAssets(?int $companyId = null): array
    {
        $logoKopPath = public_path('images/logo-rsusm.png');
        $watermarkPath = public_path('images/letter-watermark.png');
        $footerPath = public_path('images/letter-footer.png');

        $kopBase64 = is_file($logoKopPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoKopPath)) : null;
        $watermarkBase64 = is_file($watermarkPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($watermarkPath)) : null;
        $footerBase64 = is_file($footerPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($footerPath)) : null;

        return [
            'kop' => $kopBase64,
            'watermark' => $watermarkBase64,
            'footer' => $footerBase64,
        ];
    }

    /**
     * Membangun HTML surat resmi berstandar rumah sakit (kop, watermark, gelombang footer)
     */
    public static function buildHtmlForPdf(Document $document): string
    {
        $assets = self::getLetterheadAssets($document->company_id);
        $title = htmlspecialchars($document->title ?? 'Surat Resmi');
        $code = htmlspecialchars($document->code_number ?? '-');

        // Ganti placeholder template yang belum terisi jika ada
        $content = $document->content ?? '';
        $tanggalSurat = $document->tanggal_surat
            ? $document->tanggal_surat->locale('id')->isoFormat('D MMMM YYYY')
            : now()->locale('id')->isoFormat('D MMMM YYYY');

        $content = str_replace(
            ['{nomor_surat}', '{nomor}', '{tanggal}', '{nama_direktur}', '{nip_direktur}'],
            [$code, $code, $tanggalSurat, 'dr. Puga Sharaz Wangi, M.A.R.S.', '01042019232'],
            $content
        );

        $hasOfficialLetterHeader = str_contains($content, 'SURAT')
            || str_contains($content, 'KEPUTUSAN')
            || str_contains($content, 'Nomor:')
            || str_contains($content, 'Yth.');

        $optionalHeading = '';
        if (! $hasOfficialLetterHeader) {
            $optionalHeading = "
                <div style='text-align: center; margin-bottom: 24px;'>
                    <h2 style='font-size: 14pt; font-weight: bold; margin: 0; text-transform: uppercase; text-decoration: underline;'>{$title}</h2>
                    <p style='font-size: 10.5pt; margin: 4px 0 0;'>Nomor: {$code}</p>
                </div>
            ";
        }

        $kopHtml = $assets['kop']
            ? "<div class='header-kop'><img src='{$assets['kop']}' alt='Kop Surat RSU Syifa Medika'/></div><div class='header-line'></div>"
            : '';

        $footerHtml = $assets['footer']
            ? "<div class='footer-waves'><img src='{$assets['footer']}' alt='Footer Graphic'/></div>"
            : '';

        $watermarkHtml = $assets['watermark']
            ? "<div class='watermark'><img src='{$assets['watermark']}' alt='Watermark'/></div>"
            : '';

        return "
        <!DOCTYPE html>
        <html lang='id'>
        <head>
            <meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
            <title>{$title}</title>
            <style>
                @page {
                    margin: 125px 60px 85px 60px;
                }
                body {
                    font-family: Arial, Helvetica, sans-serif;
                    font-size: 11pt;
                    line-height: 1.5;
                    color: #1a1a1a;
                }
                .header-kop {
                    position: fixed;
                    top: -110px;
                    left: -40px;
                    right: -40px;
                    height: 95px;
                    text-align: center;
                }
                .header-kop img {
                    width: 100%;
                    height: auto;
                    max-height: 95px;
                }
                .header-line {
                    position: fixed;
                    top: -10px;
                    left: -40px;
                    right: -40px;
                    border-bottom: 2px solid #2d7a60;
                }
                .footer-waves {
                    position: fixed;
                    bottom: -85px;
                    left: -60px;
                    right: -60px;
                    height: 75px;
                    text-align: center;
                }
                .footer-waves img {
                    width: 100%;
                    height: 75px;
                }
                .watermark {
                    position: fixed;
                    top: 280px;
                    left: 140px;
                    width: 320px;
                    height: 320px;
                    z-index: -1000;
                    text-align: center;
                }
                .watermark img {
                    width: 100%;
                    height: 100%;
                }
                .letter-content {
                    position: relative;
                    z-index: 10;
                    text-align: justify;
                }
                p {
                    margin: 0 0 10px 0;
                }
                table {
                    border-collapse: collapse;
                    width: 100%;
                    margin-bottom: 15px;
                }
                table[border='1'], table.bordered {
                    border: 1px solid #333;
                }
                table[border='1'] td, table[border='1'] th, table.bordered td, table.bordered th {
                    border: 1px solid #333;
                    padding: 6px 10px;
                }
                table[border='0'] td, table[border='0'] th, table[style*='border: none'] td, table[style*='border: none'] th {
                    border: none;
                }
            </style>
        </head>
        <body>
            {$kopHtml}
            {$footerHtml}
            {$watermarkHtml}

            <div class='letter-content'>
                {$optionalHeading}
                {$content}
            </div>
        </body>
        </html>
        ";
    }
}
