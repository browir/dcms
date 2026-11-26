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

        // Stempel resmi (RSU Syifa Medika Banjarbaru)
        $stampPath = public_path('images/stempel_rsusm.png');
        if (! is_file($stampPath)) {
            $stampPath = public_path('letterhead/stempel_rsusm.png');
        }
        $stampBase64 = is_file($stampPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($stampPath)) : null;

        return [
            'kop'       => $kopBase64,
            'watermark' => $bgBase64,   // alias untuk kompatibilitas
            'footer'    => $footerBase64,
            'bg'        => $bgBase64,
            'stamp'     => $stampBase64,
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
    public static function prepareLetterContent(
        ?string $content,
        ?string $title,
        ?string $code,
        mixed $tanggalSurat = null,
        ?string $signerName = null,
        ?string $signerNip = null,
        ?string $signerTitle = null,
        bool $hasStamp = false,
        ?string $signaturePath = null,
        bool $isForPdf = false
    ): string
    {
        $title = htmlspecialchars(filled($title) ? $title : 'Surat Resmi');
        $code = htmlspecialchars(filled($code) ? $code : '-');
        $tanggal = filled($tanggalSurat)
            ? \Illuminate\Support\Carbon::parse($tanggalSurat)->locale('id')->isoFormat('D MMMM YYYY')
            : now()->locale('id')->isoFormat('D MMMM YYYY');

        $signerName = filled($signerName) ? $signerName : 'dr. Puga Sharaz Wangi, M.A.R.S.';
        $signerNip = filled($signerNip) ? $signerNip : '01042019232';
        $signerTitle = filled($signerTitle) ? $signerTitle : 'Direktur';

        // Ganti placeholder template yang belum terisi jika ada
        $content = str_replace(
            ['{nomor_surat}', '{nomor}', '{tanggal}', '{nama_direktur}', '{nip_direktur}', '{jabatan_direktur}', '{jabatan}'],
            [$code, $code, $tanggal, $signerName, $signerNip, $signerTitle, $signerTitle],
            (string) $content
        );

        // Normalisasi semua tabel: tabel tata letak (identitas, jadwal, meta) tanpa border, tabel data bergaris
        $content = self::normalizeDocumentTables($content);

        // Pastikan blok tanda tangan terposisi di sebelah kanan (standar tata naskah dinas)
        // dan sematkan stempel serta goresan tanda tangan jika diaktifkan
        $content = self::alignSignatureBlockToRight($content, $hasStamp, $signaturePath, $isForPdf, $signerName);

        // Pastikan bagian Lampiran Surat selalu dimulai pada halaman baru agar tidak terpotong antar halaman
        if (str_contains($content, 'Lampiran Surat')) {
            $content = preg_replace(
                '/(?:<br\s*\/?>|\s|<hr\s*\/?>|<p[^>]*>\s*(?:&nbsp;|\s|<br\s*\/?>)*<\/p>)*(<div[^>]*page-break-before[^>]*>[\s\S]*?<\/div>\s*)?(<p\b[^>]*>\s*Lampiran Surat)/i',
                '<div style="page-break-before: always; break-before: page;"></div>$2',
                $content
            ) ?? $content;
        }

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
     * serta paragraf tanda tangan (single <p> dengan <br> maupun multi <p> dari RichEditor).
     */
    public static function alignSignatureBlockToRight(
        string $content,
        bool $hasStamp = false,
        ?string $signaturePath = null,
        bool $isForPdf = false,
        ?string $signerName = null
    ): string
    {
        // 1. Tangani tabel tanda tangan (2 kolom di mana kolom pertama kosong/spacer dan kolom kedua berisi tanda tangan, atau tabel dengan class letter-signature-table)
        $content = preg_replace_callback(
            '/<table\b[^>]*>((?:(?!<\/table>)[\s\S])*?)<\/table>/i',
            function ($tableMatch) {
                $tableInner = $tableMatch[1];

                // Jika sudah merupakan tabel tanda tangan lengkap dengan spacer dan blok penandatangan
                if (preg_match('/<td\b[^>]*class="[^"]*letter-signature-block[^"]*"[^>]*>([\s\S]*?)<\/td>/i', $tableInner, $blockMatch)) {
                    $signContent = trim($blockMatch[1]);
                    return '<table class="letter-signature-table" style="width: 100%; table-layout: fixed; border: none !important; border-collapse: collapse; margin-top: 24px; margin-bottom: 20px;">'
                        . '<tbody>'
                        . '<tr>'
                        . '<td style="width: 58%; border: none !important; padding: 0;">&nbsp;</td>'
                        . '<td class="letter-signature-block" style="width: 42%; border: none !important; padding: 0; text-align: left; vertical-align: top;">'
                        . $signContent
                        . '</td>'
                        . '</tr>'
                        . '</tbody>'
                        . '</table>';
                }

                // Periksa setiap baris <tr>: apakah ada baris 2 kolom di mana cell 1 kosong dan cell 2 berisi tanda tangan
                if (preg_match('/<tr\b[^>]*>\s*<td\b[^>]*>([\s\S]*?)<\/td>\s*<td\b[^>]*>([\s\S]*?)<\/td>\s*<\/tr>/i', $tableInner, $rowMatch)) {
                    $c1Text = trim(html_entity_decode(strip_tags($rowMatch[1])));
                    $c2Html = $rowMatch[2];

                    $isSigCell = (bool) preg_match('/(Direktur|Hormat kami|NIP\.|dr\.\s*Puga|\{nama_direktur\}|Plt\.|Kepala|Sekretaris|Mengetahui|Yang menyatakan|Penanda Tangan)/i', $c2Html);

                    if ($c1Text === '' && $isSigCell) {
                        $signContent = trim($c2Html);
                        return '<table class="letter-signature-table" style="width: 100%; table-layout: fixed; border: none !important; border-collapse: collapse; margin-top: 24px; margin-bottom: 20px;">'
                            . '<tbody>'
                            . '<tr>'
                            . '<td style="width: 58%; border: none !important; padding: 0;">&nbsp;</td>'
                            . '<td class="letter-signature-block" style="width: 42%; border: none !important; padding: 0; text-align: left; vertical-align: top;">'
                            . $signContent
                            . '</td>'
                            . '</tr>'
                            . '</tbody>'
                            . '</table>';
                    }
                }

                // Tabel 1 kolom berisi tanda tangan
                if (preg_match('/<tr\b[^>]*>\s*<td\b[^>]*>([\s\S]*?)<\/td>\s*<\/tr>/i', $tableInner, $oneRowMatch) && ! preg_match('/<td\b[^>]*>[\s\S]*?<\/td>\s*<td\b/i', $tableInner)) {
                    $singleHtml = $oneRowMatch[1];
                    if (preg_match('/(Direktur|Hormat kami|NIP\.|dr\.\s*Puga|\{nama_direktur\}|Plt\.|Kepala|Sekretaris|Mengetahui|Yang menyatakan|Penanda Tangan)/i', $singleHtml)
                        && ! str_contains($singleHtml, 'Pejabat berwenang')
                        && ! str_contains($singleHtml, 'Nama karyawan')) {
                        return '<table class="letter-signature-table" style="width: 100%; table-layout: fixed; border: none !important; border-collapse: collapse; margin-top: 24px; margin-bottom: 20px;">'
                            . '<tbody>'
                            . '<tr>'
                            . '<td style="width: 58%; border: none !important; padding: 0;">&nbsp;</td>'
                            . '<td class="letter-signature-block" style="width: 42%; border: none !important; padding: 0; text-align: left; vertical-align: top;">'
                            . trim($singleHtml)
                            . '</td>'
                            . '</tr>'
                            . '</tbody>'
                            . '</table>';
                    }
                }

                return $tableMatch[0];
            },
            $content
        );

        // 2. Multi-paragraf tanda tangan di luar tabel (urutan <p>...</p> berisi tanggal & jabatan/direktur/NIP)
        $pattern = '/((?:<p\b[^>]*>\s*(?:Banjarbaru,|Hormat kami|Ditetapkan di)[\s\S]*?<\/p>)(?:\s*<p\b[^>]*>[\s\S]*?<\/p>){1,6})/i';
        $content = preg_replace_callback($pattern, function ($matches) {
            $block = $matches[1];
            if ((str_contains($block, 'NIP') || str_contains($block, 'Direktur') || str_contains($block, 'Puga') || str_contains($block, 'Plt.') || str_contains($block, 'Kepala') || str_contains($block, 'Sekretaris'))
                && ! str_contains($block, 'letter-signature-block')
                && ! str_contains($block, 'Yang bertanda tangan')) {

                return '<table class="letter-signature-table" style="width: 100%; table-layout: fixed; border: none !important; border-collapse: collapse; margin-top: 24px; margin-bottom: 20px;">'
                    . '<tbody>'
                    . '<tr>'
                    . '<td style="width: 58%; border: none !important; padding: 0;">&nbsp;</td>'
                    . '<td class="letter-signature-block" style="width: 42%; border: none !important; padding: 0; text-align: left; vertical-align: top;">'
                    . $block
                    . '</td>'
                    . '</tr>'
                    . '</tbody>'
                    . '</table>';
            }
            return $matches[0];
        }, $content);

        // 3. Paragraf tunggal yang berisi tanda tangan dengan <br>
        $content = preg_replace_callback(
            '/<p\b[^>]*>([\s\S]*?)(?:<br\s*\/?>\s*)*((?:Banjarbaru,|Hormat kami|Ditetapkan di)[^<]*<br\s*\/?>[\s\S]*?NIP\.[^<]*)<\/p>/i',
            function ($matches) {
                if (str_contains($matches[0], 'letter-signature-block')) {
                    return $matches[0];
                }
                $prefix = trim($matches[1]);
                $sig = trim($matches[2]);

                $res = '';
                if ($prefix !== '') {
                    $res .= '<p>' . $prefix . '</p>';
                }
                $res .= '<table class="letter-signature-table" style="width: 100%; border: none !important; border-collapse: collapse; margin-top: 24px; margin-bottom: 20px;">'
                    . '<tbody>'
                    . '<tr>'
                    . '<td style="width: 58%; border: none !important; padding: 0;">&nbsp;</td>'
                    . '<td class="letter-signature-block" style="width: 42%; border: none !important; padding: 0; text-align: left; vertical-align: top;">'
                    . $sig
                    . '</td>'
                    . '</tr>'
                    . '</tbody>'
                    . '</table>';
                return $res;
            },
            $content
        );

        // 4. Setelah blok tanda tangan terbentuk di .letter-signature-block, sematkan stempel & tanda tangan jika diminta
        if ($hasStamp || $signaturePath) {
            $content = preg_replace_callback(
                '/<td\s+[^>]*class="[^"]*letter-signature-block[^"]*"([^>]*)>([\s\S]*?)<\/td>/i',
                function ($tdMatch) use ($hasStamp, $signaturePath, $isForPdf, $signerName) {
                    $attrs = $tdMatch[1];
                    $inner = $tdMatch[2];
                    $injected = self::injectSignatureAndStamp($inner, $hasStamp, $signaturePath, $isForPdf, $signerName);
                    return '<td class="letter-signature-block"' . $attrs . '>' . $injected . '</td>';
                },
                $content,
                1
            );
        }

        return $content;
    }

    /**
     * Menyisipkan gambar tanda tangan digital dan stempel resmi ke dalam blok penandatangan.
     */
    public static function injectSignatureAndStamp(
        string $signContent,
        bool $hasStamp = false,
        ?string $signaturePath = null,
        bool $isForPdf = false,
        ?string $signerName = null
    ): string {
        if (! $hasStamp && ! $signaturePath) {
            return $signContent;
        }

        // Hindari duplikasi jika sudah terpasang
        if (str_contains($signContent, 'letter-sig-stamp-container')) {
            return $signContent;
        }

        $stampSrc = null;
        if ($hasStamp) {
            if ($isForPdf) {
                $assets = self::getLetterheadAssets();
                $stampSrc = $assets['stamp'];
            } else {
                $stampSrc = asset('images/stempel_rsusm.png');
            }
        }

        $sigSrc = null;
        if ($signaturePath) {
            if ($isForPdf) {
                $fullPath = storage_path('app/public/' . $signaturePath);
                if (is_file($fullPath)) {
                    $sigSrc = 'data:image/png;base64,' . base64_encode(file_get_contents($fullPath));
                }
            } else {
                $sigSrc = \Illuminate\Support\Facades\Storage::disk('public')->url($signaturePath);
            }
        }

        $sigStampHtml = '<div class="letter-sig-stamp-container" style="position: relative; width: 100%; min-height: 80px; margin: 4px 0;">';
        if ($stampSrc) {
            $sigStampHtml .= '<img src="' . $stampSrc . '" alt="Stempel Resmi" class="letter-stamp-img" style="position: absolute; left: -50px; top: 0px; width: 145px; height: auto; opacity: 0.88; pointer-events: none; z-index: 2;" />';
        }
        if ($sigSrc) {
            $sigStampHtml .= '<img src="' . $sigSrc . '" alt="Tanda Tangan" class="letter-signature-img" style="position: relative; max-height: 100px; max-width: 220px; width: auto; object-fit: contain; display: block; z-index: 1;" />';
        } else {
            $sigStampHtml .= '<div style="height: 70px;">&nbsp;</div>';
        }
        $sigStampHtml .= '</div>';

        // 1. Multi-paragraf dengan <p>&nbsp;</p> atau <p><br></p> di antara jabatan dan nama
        if (preg_match('/(\s*<p\b[^>]*>(?:&nbsp;|<br\s*\/?>|\s)*<\/p>\s*)+/i', $signContent)) {
            return preg_replace('/(\s*<p\b[^>]*>(?:&nbsp;|<br\s*\/?>|\s)*<\/p>\s*)+/i', $sigStampHtml, $signContent, 1);
        }

        // 2. Rentetan <br> di dalam teks (misal: Direktur,<br><br><br> atau <strong>Direktur,</strong><br><br><br>)
        if (preg_match('/(<br\s*\/?>\s*){2,}/i', $signContent)) {
            return preg_replace('/(<br\s*\/?>\s*){2,}/i', '<br>' . $sigStampHtml, $signContent, 1);
        }

        // 3. Sisipkan tepat sebelum nama penanda tangan (ber-underline, ber-strong, diawali dr., atau cocok dengan $signerName / [Nama Penanda Tangan])
        $namePatterns = ['\[Nama Penanda Tangan\]'];
        if (filled($signerName)) {
            $namePatterns[] = preg_quote($signerName, '/');
        }
        $nameRegex = '(?:<p\b[^>]*>\s*)?(?:<strong>\s*<u>|<u>\s*<strong>|<strong>|<u>|dr\.|' . implode('|', $namePatterns) . ')';
        if (preg_match('/(' . $nameRegex . ')/i', $signContent)) {
            return preg_replace('/(' . $nameRegex . ')/i', $sigStampHtml . '$1', $signContent, 1);
        }

        // 4. Sisipkan sebelum baris NIP jika nama tidak terdeteksi
        if (preg_match('/(<(?:p|br\s*\/?>)\s*NIP[\.:\s])/i', $signContent)) {
            return preg_replace('/(<(?:p|br\s*\/?>)\s*NIP[\.:\s])/i', $sigStampHtml . '$1', $signContent, 1);
        }

        return $signContent . $sigStampHtml;
    }

    /**
     * Menandai tabel metadata kepala surat (Nomor & Tanggal di sisi kanan)
     * agar tidak terkena border tabel konten.
     */
    public static function markMetaTables(string $content): string
    {
        return self::normalizeDocumentTables($content);
    }

    /**
     * Melakukan normalisasi semua tabel di dalam dokumen resmi:
     * - Tabel tata letak (identitas, jadwal, kepala surat, blok tanda tangan): TANPA BORDER (border: none)
     * - Tabel data (tabel petugas SPT, daftar undangan, rincian SPPD, tabel ber-th): BERGARIS (1px solid #333)
     */
    public static function normalizeDocumentTables(string $content): string
    {
        $res = preg_replace_callback(
            '/<table\b([^>]*)>((?:(?!<\/table>)[\s\S])*?)<\/table>/i',
            function ($matches) {
                $tableAttrs = $matches[1];
                $tableInner = $matches[2];

                // 1. Signature table: deteksi tabel tanda tangan (baik yang sudah memiliki class maupun yang berasal dari TipTap)
                $isSignatureTable = str_contains($tableAttrs, 'letter-signature-table');
                $sigContent = null;

                if (! $isSignatureTable && preg_match('/<tr\b[^>]*>\s*<td\b[^>]*>([\s\S]*?)<\/td>\s*<td\b[^>]*>([\s\S]*?)<\/td>\s*<\/tr>/i', $tableInner, $sigRowMatch)) {
                    $c1Text = trim(html_entity_decode(strip_tags($sigRowMatch[1])));
                    $c2Html = $sigRowMatch[2];
                    $isSigCell = (bool) preg_match('/(Direktur|Hormat kami|NIP\.|dr\.\s*Puga|\{nama_direktur\}|Plt\.|Kepala|Sekretaris|Mengetahui|Yang menyatakan|Penanda Tangan)/i', $c2Html);
                    if ($c1Text === '' && $isSigCell && ! str_contains($c2Html, 'Yang bertanda tangan') && ! str_contains($c2Html, 'menerangkan bahwa')) {
                        $isSignatureTable = true;
                        $sigContent = trim($c2Html);
                    }
                }

                if ($isSignatureTable) {
                    if ($sigContent === null) {
                        if (preg_match('/<td\b[^>]*class="[^"]*letter-signature-block[^"]*"[^>]*>([\s\S]*?)<\/td>/i', $tableInner, $bMatch)) {
                            $sigContent = trim($bMatch[1]);
                        } elseif (preg_match('/<tr\b[^>]*>\s*<td\b[^>]*>([\s\S]*?)<\/td>\s*<td\b[^>]*>([\s\S]*?)<\/td>\s*<\/tr>/i', $tableInner, $rMatch)) {
                            $r1Text = trim(html_entity_decode(strip_tags($rMatch[1])));
                            if ($r1Text === '') {
                                $sigContent = trim($rMatch[2]);
                            }
                        }
                    }

                    if ($sigContent !== null) {
                        return '<table class="letter-signature-table" style="width: 100%; table-layout: fixed; border: none !important; border-collapse: collapse; margin-top: 24px; margin-bottom: 20px;">'
                            . '<tbody><tr>'
                            . '<td style="width: 58%; border: none !important; padding: 0;">&nbsp;</td>'
                            . '<td class="letter-signature-block" style="width: 42%; border: none !important; padding: 0; text-align: left; vertical-align: top;">'
                            . $sigContent
                            . '</td>'
                            . '</tr></tbody>'
                            . '</table>';
                    }

                    return '<table class="letter-signature-table" style="width: 100%; table-layout: fixed; border: none !important; border-collapse: collapse; margin-top: 24px; margin-bottom: 20px;">'
                        . $tableInner
                        . '</table>';
                }

                // 2. Meta table (Nomor & Tanggal di sisi kanan)
                $hasNomorOrPerihal = (str_contains($tableInner, 'Nomor :') || str_contains($tableInner, 'Perihal :') || str_contains($tableInner, 'Lampiran :'));
                $hasTanggalAtas = (str_contains($tableInner, 'Banjarbaru,') && ! str_contains($tableInner, 'Direktur'));
                if (str_contains($tableAttrs, 'letter-meta-table') || ($hasNomorOrPerihal && $hasTanggalAtas)) {
                    return '<table class="letter-meta-table" style="width: 100%; border: none !important; border-collapse: collapse; margin-bottom: 16px;">'
                        . preg_replace('/<td\b([^>]*)>/i', '<td$1 style="border: none !important; padding: 2px 4px !important;">', $tableInner)
                        . '</table>';
                }

                // 3. Periksa apakah tabel memiliki header <th>
                $hasTh = (bool) preg_match('/<th\b/i', $tableInner);

                // Periksa jumlah kolom maksimum
                $maxCols = 0;
                if (preg_match_all('/<tr\b[^>]*>([\s\S]*?)<\/tr>/i', $tableInner, $rows)) {
                    foreach ($rows[1] as $r) {
                        $colCount = preg_match_all('/<(?:td|th)\b/i', $r);
                        if ($colCount > $maxCols) {
                            $maxCols = $colCount;
                        }
                    }
                }

                // Periksa kata kunci tabel tata letak (identitas biodata atau jadwal)
                $hasLayoutKeywords = (bool) preg_match('/(Nama|NIP|Jabatan|Hari|Tanggal|Waktu|Pukul|Tempat|Pangkat|Golongan|Keperluan|Transportasi)\s*<\/strong>\s*<\/td>\s*<td[^>]*>\s*:/i', $tableInner)
                    || (bool) preg_match('/<(?:td|p|strong)[^>]*>(Nama|NIP|Jabatan|Hari,\s*Tanggal|Hari\/Tanggal|Waktu|Pukul|Tempat):?\s*<\/(?:td|p|strong)>\s*<td[^>]*>\s*:/i', $tableInner);

                $isExplicitBorderless = str_contains($tableAttrs, 'border: none') || str_contains($tableAttrs, 'border="0"') || str_contains($tableAttrs, 'letter-layout-table');

                // Khusus SPPD (8 butir), harus bergaris (data table)
                $isSPPD = str_contains($tableInner, 'Pejabat berwenang pemberi perintah') || str_contains($tableInner, 'Nama karyawan yang diberi perintah');

                // Jika TANPA <th> dan (<= 2 kolom atau ada keyword identitas/jadwal atau eksplisit borderless) dan BUKAN SPPD:
                if (! $hasTh && ! $isSPPD && ($hasLayoutKeywords || $isExplicitBorderless || $maxCols <= 2)) {
                    $cleanInner = preg_replace_callback('/<td\b([^>]*)>/i', function ($tdMatch) {
                        $attrs = preg_replace('/\s*style=(["\']).*?\1/i', '', $tdMatch[1]);
                        return '<td' . $attrs . ' style="border: none !important; padding: 3px 4px !important; vertical-align: top;">';
                    }, $tableInner);
                    return '<table class="letter-layout-table" style="width: 100%; border: none !important; border-collapse: collapse; margin-bottom: 14px;">' . $cleanInner . '</table>';
                }

                // Selain itu: Tabel Data (Petugas, SPPD, Daftar Undangan, dll.) -> bergaris
                $cleanInner = preg_replace_callback('/<th\b([^>]*)>/i', function ($thMatch) {
                    $attrs = preg_replace('/\s*style=(["\']).*?\1/i', '', $thMatch[1]);
                    return '<th' . $attrs . ' style="border: 1px solid #333 !important; padding: 6px 10px; background-color: #f2f2f2; font-weight: bold; text-align: center;">';
                }, $tableInner);
                $cleanInner = preg_replace_callback('/<td\b([^>]*)>/i', function ($tdMatch) {
                    $attrs = preg_replace('/\s*style=(["\']).*?\1/i', '', $tdMatch[1]);
                    return '<td' . $attrs . ' style="border: 1px solid #333 !important; padding: 6px 10px; vertical-align: middle;">';
                }, $cleanInner);
                return '<table class="letter-data-table" style="width: 100%; border-collapse: collapse; border: 1px solid #333 !important; margin-bottom: 16px;">' . $cleanInner . '</table>';
            },
            $content
        );

        return $res ?? $content;
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
        $stamp = collect(['images/stempel_rsusm.png', 'letterhead/stempel_rsusm.png'])->first(fn ($p) => is_file(public_path($p)));

        return [
            'kop'    => $kop ? asset($kop) : null,
            'bg'     => $bg ? asset($bg) : null,
            'footer' => $footer ? asset($footer) : null,
            'stamp'  => $stamp ? asset($stamp) : null,
        ];
    }

    public static function buildHtmlForPdf(Document $document): string
    {
        $assets = self::getLetterheadAssets($document->company_id);
        $title = htmlspecialchars($document->title ?? 'Surat Resmi');

        $signaturePath = null;
        if ($document->signature_id) {
            $sig = $document->signature;
            if ($sig) {
                $sigOwner = $sig->user_id;
                $allowedSigners = array_filter([
                    $document->user_id,
                    $document->created_by,
                    $document->reviewed_by_direktur,
                    $document->direktur_id,
                    $document->reviewed_by_kabid,
                    $document->kabid_id,
                    $document->approver_id,
                ]);

                if ($document->status === 'approved' || in_array($sigOwner, $allowedSigners, true)) {
                    $signaturePath = $sig->signature_path;
                }
            }
        }

        $content = self::prepareLetterContent(
            $document->content,
            $document->title,
            $document->code_number,
            $document->tanggal_surat,
            $document->signer_name,
            $document->signer_nip,
            $document->signer_title,
            (bool) $document->has_stamp,
            $signaturePath,
            true
        );
        $optionalHeading = '';

        // Kop surat — ditampilkan sebagai running element (hanya halaman 1 via @page :first)
        // Untuk dompdf: kop dimasukkan sebagai bagian dari konten biasa (bukan fixed)
        // agar tidak diulang di setiap halaman lampiran
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
                    page-break-inside: auto;
                }
                thead {
                    display: table-header-group;
                }
                tr {
                    page-break-inside: avoid;
                    break-inside: avoid;
                }
                /* Judul section (mis. DAFTAR UNDANGAN) tidak terpisah dari tabel di bawahnya */
                p + table {
                    page-break-before: avoid;
                    break-before: avoid;
                }
                table.letter-layout-table,
                table.letter-meta-table,
                table.letter-signature-table,
                table[border='0'],
                table[style*='border: none'] {
                    border: none !important;
                }
                table.letter-layout-table td,
                table.letter-layout-table th,
                table.letter-meta-table td,
                table.letter-meta-table th,
                table.letter-signature-table td,
                table.letter-signature-table th,
                table[border='0'] td,
                table[border='0'] th,
                table[style*='border: none'] td,
                table[style*='border: none'] th {
                    border: none !important;
                    padding: 3px 4px !important;
                }
                table.letter-signature-table {
                    width: 100% !important;
                    table-layout: fixed !important;
                    border: none !important;
                    margin-top: 24px !important;
                    margin-bottom: 20px !important;
                }
                table.letter-signature-table td {
                    border: none !important;
                    padding: 0 !important;
                }
                table.letter-signature-table tr > td:first-child {
                    width: 58% !important;
                    border: none !important;
                }
                table.letter-signature-table tr > td:last-child,
                .letter-signature-block {
                    width: 42% !important;
                    text-align: left !important;
                    vertical-align: top !important;
                    padding-left: 0 !important;
                    border: none !important;
                }
                .letter-sig-stamp-container {
                    position: relative;
                    width: 100%;
                    min-height: 65px;
                    margin: 4px 0;
                }
                .letter-sig-stamp-container .letter-stamp-img {
                    position: absolute;
                    left: -50px;
                    top: 0px;
                    width: 135px;
                    height: auto;
                    opacity: 0.88;
                }
                .letter-sig-stamp-container .letter-signature-img {
                    position: relative;
                    max-height: 75px;
                    max-width: 175px;
                    object-fit: contain;
                    display: block;
                }
                table.letter-data-table,
                table:not(.letter-layout-table):not(.letter-meta-table):not(.letter-signature-table) {
                    border-collapse: collapse;
                    border: 1px solid #333;
                }
                table.letter-data-table td,
                table.letter-data-table th,
                table:not(.letter-layout-table):not(.letter-meta-table):not(.letter-signature-table) td,
                table:not(.letter-layout-table):not(.letter-meta-table):not(.letter-signature-table) th {
                    border: 1px solid #333;
                    padding: 6px 10px;
                    vertical-align: middle;
                }
                table.letter-data-table th,
                table:not(.letter-layout-table):not(.letter-meta-table):not(.letter-signature-table) th {
                    font-weight: bold;
                    text-align: center;
                    background-color: #f2f2f2;
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
