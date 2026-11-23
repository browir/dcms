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
     * Resolves letterhead assets (kop surat, background watermark+footer) as base64 data URIs.
     * Menggunakan asset asli dari template Word RSU Syifa Medika Banjarbaru.
     */
    public static function getLetterheadAssets(?int $companyId = null): array
    {
        // Kop surat: logo RSU Syifa Medika Banjarbaru (PNG dari template Word)
        $kopPath = public_path('images/kop-rsusm.png');
        // Fallback ke file lama jika ada
        if (! is_file($kopPath)) {
            $kopPath = public_path('images/logo-rsusm.png');
        }

        // Background surat: satu gambar berisi watermark palang medis + gelombang footer
        // (diambil langsung dari template Word resmi RSU Syifa Medika)
        // Utamakan versi JPEG: PNG ber-alpha (transparan) besar membuat dompdf memproses
        // kanal alpha piksel demi piksel via GD dan menghabiskan memory_limit.
        $bgPath = public_path('images/letter-bg.jpg');
        if (! is_file($bgPath)) {
            $bgPath = public_path('images/letter-bg.png');
        }

        $mimeOf = fn (string $path) => preg_match('/\.jpe?g$/i', $path) ? 'image/jpeg' : 'image/png';
        $kopBase64 = is_file($kopPath) ? 'data:'.$mimeOf($kopPath).';base64,'.base64_encode(file_get_contents($kopPath)) : null;
        $bgBase64  = is_file($bgPath)  ? 'data:'.$mimeOf($bgPath).';base64,'.base64_encode(file_get_contents($bgPath))  : null;

        // Footer slogan surat: "Pelayanan Profesional dan Tepercaya"
        $footerPath = public_path('images/letter-footer-slogan.png');
        if (! is_file($footerPath)) {
            $footerPath = public_path('images/letter-footer-slogan.jpg');
        }
        if (! is_file($footerPath)) {
            $footerPath = public_path('images/letter-footer.png');
        }
        $footerBase64 = is_file($footerPath) ? 'data:'.$mimeOf($footerPath).';base64,'.base64_encode(file_get_contents($footerPath)) : null;

        return [
            'kop'       => $kopBase64,
            'watermark' => $bgBase64,   // alias untuk kompatibilitas
            'footer'    => $footerBase64,
            'bg'        => $bgBase64,
        ];
    }

    /**
     * Membangun HTML surat resmi berstandar rumah sakit (kop, watermark, gelombang footer)
     */
    /**
     * Isi surat siap render: placeholder template diganti, dan judul + nomor ditambahkan
     * di atas bila isi belum punya kepala surat resmi.
     *
     * Dipakai bersama oleh PDF (buildHtmlForPdf) dan pratinjau HTML
     * (view filament.partials.letter-sheet) agar keduanya selalu identik.
     */
    public static function prepareLetterContent(?string $content, ?string $title, ?string $code, mixed $tanggalSurat = null): string
    {
        $title = htmlspecialchars(filled($title) ? $title : 'Surat Resmi');
        $code = htmlspecialchars(filled($code) ? $code : '-');
        $tanggal = filled($tanggalSurat)
            ? \Illuminate\Support\Carbon::parse($tanggalSurat)->locale('id')->isoFormat('D MMMM YYYY')
            : now()->locale('id')->isoFormat('D MMMM YYYY');

        // Ganti placeholder template yang belum terisi jika ada
        $content = str_replace(
            ['{nomor_surat}', '{nomor}', '{tanggal}', '{nama_direktur}', '{nip_direktur}'],
            [$code, $code, $tanggal, 'dr. Puga Sharaz Wangi, M.A.R.S.', '01042019232'],
            (string) $content
        );

        // Pastikan blok tanda tangan terposisi di sebelah kanan (standar tata naskah dinas)
        $content = self::alignSignatureBlockToRight($content);

        $hasOfficialLetterHeader = str_contains($content, 'SURAT')
            || str_contains($content, 'KEPUTUSAN')
            || str_contains($content, 'Nomor:')
            || str_contains($content, 'Yth.');

        if ($hasOfficialLetterHeader) {
            return $content;
        }

        return "
                <div style='text-align: center; margin-bottom: 24px;'>
                    <h2 style='font-size: 14pt; font-weight: bold; margin: 0; text-transform: uppercase; text-decoration: underline;'>{$title}</h2>
                    <p style='font-size: 10.5pt; margin: 4px 0 0;'>Nomor: {$code}</p>
                </div>
            ".$content;
    }

    /**
     * Memastikan blok tanda tangan surat berada di sebelah kanan dokumen (standar tata naskah dinas).
     * Menangani tabel tanda tangan (kolom kiri kosong/spacer, kolom kanan isi tanda tangan)
     * serta paragraf tanda tangan tanpa tabel.
     */
    public static function alignSignatureBlockToRight(string $content): string
    {
        // 1. Tangani tabel 2 kolom di mana kolom pertama kosong/spacer dan kolom kedua berisi tanda tangan
        $content = preg_replace_callback(
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
            $content
        );

        // 2. Jika ada paragraf tanda tangan mandiri di luar tabel yang berisi tanggal & jabatan/direktur
        if (! str_contains($content, 'letter-signature-table')) {
            $content = preg_replace_callback(
                '/<p([^>]*)>\s*((?:Banjarbaru,|Hormat kami|Ditetapkan di)[^<]*<br[^>]*>.*?NIP\.[^<]*)<\/p>/is',
                function ($matches) {
                    return '<table class="letter-signature-table" style="width: 100%; border: none; border-collapse: collapse; margin-top: 24px; margin-bottom: 20px;">'
                        . '<tbody>'
                        . '<tr>'
                        . '<td style="width: 58%; border: none; padding: 0;">&nbsp;</td>'
                        . '<td class="letter-signature-block" style="width: 42%; border: none; padding: 0; text-align: left; vertical-align: top;">'
                        . $matches[2]
                        . '</td>'
                        . '</tr>'
                        . '</tbody>'
                        . '</table>';
                },
                $content
            );
        }

        return $content;
    }

    /**
     * URL publik aset kop & latar surat — sama persis dengan yang dipakai PDF.
     *
     * @return array{kop: ?string, bg: ?string}
     */
    public static function getLetterheadAssetUrls(): array
    {
        $kop = collect(['images/kop-rsusm.png', 'images/logo-rsusm.png'])->first(fn ($p) => is_file(public_path($p)));
        $bg = collect(['images/letter-bg.jpg', 'images/letter-bg.png'])->first(fn ($p) => is_file(public_path($p)));
        $footer = collect(['images/letter-footer-slogan.png', 'images/letter-footer-slogan.jpg', 'images/letter-footer.png'])->first(fn ($p) => is_file(public_path($p)));

        return [
            'kop'    => $kop ? asset($kop) : null,
            'bg'     => $bg ? asset($bg) : null,
            'footer' => $footer ? asset($footer) : null,
        ];
    }

    public static function buildHtmlForPdf(Document $document): string
    {
        $assets = self::getLetterheadAssets($document->company_id);
        $title = htmlspecialchars($document->title ?? 'Surat Resmi');

        $content = self::prepareLetterContent($document->content, $document->title, $document->code_number, $document->tanggal_surat);
        $optionalHeading = '';

        // Kop surat di header (fixed, atas halaman)
        $kopHtml = $assets['kop']
            ? "<div class='header-kop'><img src='{$assets['kop']}' alt='Kop Surat RSU Syifa Medika'/></div>"
            : '';

        // Background: satu gambar berisi watermark palang medis + gelombang footer
        // Diposisikan di tengah-bawah halaman sebagai latar belakang
        $bgHtml = $assets['bg']
            ? "<div class='letter-bg'><img src='{$assets['bg']}' alt='Background Surat'/></div>"
            : '';

        // Footer slogan di kanan bawah halaman ("Pelayanan Profesional dan Tepercaya")
        $footerHtml = $assets['footer']
            ? "<div class='footer-slogan'><img src='{$assets['footer']}' alt='Pelayanan Profesional dan Tepercaya'/></div>"
            : '';

        return "
        <!DOCTYPE html>
        <html lang='id'>
        <head>
            <meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
            <title>{$title}</title>
            <style>
                @page {
                    margin: 120px 60px 60px 60px;
                }
                body {
                    font-family: Arial, Helvetica, sans-serif;
                    font-size: 11pt;
                    line-height: 1.5;
                    color: #1a1a1a;
                }
                /* Kop surat: header tetap di atas setiap halaman */
                .header-kop {
                    position: fixed;
                    top: -108px;
                    left: -50px;
                    right: -50px;
                    height: 100px;
                    text-align: center;
                }
                .header-kop img {
                    width: 100%;
                    height: 100px;
                    object-fit: fill;
                }
                /* Background: watermark palang medis + gelombang footer
                   Mencakup dari tengah sampai bawah halaman */
                .letter-bg {
                    position: fixed;
                    top: 60px;
                    left: -60px;
                    right: -60px;
                    bottom: -60px;
                    z-index: -1;
                    text-align: center;
                }
                .letter-bg img {
                    width: 100%;
                    height: 100%;
                    object-fit: fill;
                }
                /* Konten surat di atas background */
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
                table.letter-signature-table {
                    width: 100% !important;
                    border: none !important;
                    border-collapse: collapse !important;
                    margin-top: 24px !important;
                    margin-bottom: 20px !important;
                }
                table.letter-signature-table td {
                    border: none !important;
                    padding: 0 !important;
                }
                .letter-signature-block {
                    text-align: left !important;
                    vertical-align: top !important;
                }
                /* Footer slogan di kanan bawah */
                .footer-slogan {
                    position: fixed;
                    bottom: -22px;
                    right: -30px;
                    width: 283px;
                    height: 16px;
                    text-align: right;
                    z-index: 5;
                }
                .footer-slogan img {
                    width: 283px;
                    height: 16px;
                    object-fit: contain;
                }
            </style>
        </head>
        <body>
            {$kopHtml}
            {$bgHtml}
            {$footerHtml}

            <div class='letter-content'>
                {$optionalHeading}
                {$content}
            </div>
        </body>
        </html>
        ";
    }
}
