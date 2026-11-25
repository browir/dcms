{{--
    Lembar surat resmi — replika HTML dari PDF (DocumentConversionService::buildHtmlForPdf).
    Dipakai bersama oleh:
      - halaman detail dokumen (filament.infolists.document-letter-preview)
      - pratinjau langsung di form buat/edit dokumen (DocumentForm)

    Variabel:
      $content       ?string   HTML isi surat (dari Rich Editor)
      $title         ?string   judul dokumen (dipakai bila isi belum punya kepala surat)
      $code          ?string   nomor surat
      $tanggalSurat  mixed     tanggal surat (Carbon / string tanggal / null = hari ini)
      $badge         ?string   label badge di toolbar
      $note          ?string   keterangan kecil di toolbar
      $downloadPdfUrl, $streamPdfUrl, $downloadWordUrl   ?string  tombol aksi (tampil bila diisi)

    Geometri mengikuti PDF persis. Halaman A4 di dompdf (96 dpi) = 794 × 1123 px; semua ukuran
    ditulis dalam px PDF lalu diskalakan ke lebar kertas di layar lewat --u (= 1 px PDF).
    Jika PDF diubah (margin @page, posisi kop/latar, font), ubah juga angka di sini.

    Sengaja memakai CSS sendiri (prefix "dlp-"), bukan class Tailwind: panel Filament tidak
    memakai custom theme yang meng-compile class Tailwind dari view kustom.
--}}
@php
    $code = filled($code ?? null) ? $code : null;
    $badge ??= 'Format Surat Resmi';
    $note ??= null;
    $downloadPdfUrl ??= null;
    $streamPdfUrl ??= null;
    $downloadWordUrl ??= null;
    $hasStamp = (bool) ($hasStamp ?? false);
    $signaturePath ??= null;

    $isEmpty = blank(trim(strip_tags((string) ($content ?? ''), '<img><table>')));
    $letterHtml = $isEmpty ? '' : \App\Services\DocumentConversionService::prepareLetterContent(
        $content ?? '',
        $title ?? null,
        $code,
        $tanggalSurat ?? null,
        $signerName ?? null,
        $signerNip ?? null,
        $signerTitle ?? null,
        $hasStamp,
        $signaturePath,
        false
    );
    $assets = \App\Services\DocumentConversionService::getLetterheadAssetUrls();
@endphp

@once
<style>
    .dlp { display: flex; flex-direction: column; gap: 1rem; }

    /* ── Toolbar ─────────────────────────────────────────────── */
    .dlp-toolbar {
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem;
        padding: .875rem; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: .75rem;
        box-shadow: 0 1px 2px rgba(0,0,0,.05);
    }
    .dlp-meta { display: flex; flex-wrap: wrap; align-items: center; gap: .625rem; min-width: 0; }
    .dlp-badge {
        display: inline-flex; align-items: center; gap: .375rem; padding: .25rem .75rem; border-radius: 9999px;
        font-size: .75rem; font-weight: 600; line-height: 1rem;
        background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7;
    }
    .dlp-dot { width: .5rem; height: .5rem; border-radius: 9999px; background: #10b981; animation: dlp-pulse 2s ease-in-out infinite; }
    @keyframes dlp-pulse { 50% { opacity: .4; } }
    .dlp-code { font-size: .75rem; font-weight: 500; color: #64748b; overflow-wrap: anywhere; }
    .dlp-note { font-size: .75rem; color: #64748b; }

    .dlp-actions { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
    .dlp-btn {
        display: inline-flex; align-items: center; gap: .375rem; padding: .375rem .75rem; border-radius: .5rem;
        font-size: .75rem; font-weight: 600; line-height: 1rem; text-decoration: none; white-space: nowrap;
        border: 1px solid transparent; box-shadow: 0 1px 2px rgba(0,0,0,.05); transition: background-color .15s;
    }
    .dlp-btn svg { width: .875rem; height: .875rem; flex-shrink: 0; }
    .dlp-btn-primary { color: #fff; background: #059669; }
    .dlp-btn-primary:hover { background: #047857; }
    .dlp-btn-secondary { color: #334155; background: #fff; border-color: #cbd5e1; }
    .dlp-btn-secondary:hover { background: #f8fafc; }
    .dlp-btn-word { color: #1d4ed8; background: #eff6ff; border-color: #bfdbfe; box-shadow: none; }
    .dlp-btn-word:hover { background: #dbeafe; }

    /* ── Alas meja ───────────────────────────────────────────── */
    .dlp-desk {
        overflow-x: auto; padding: 2rem 1.5rem;
        background: rgba(226,232,240,.8); border: 1px solid rgba(203,213,225,.7); border-radius: 1rem;
    }

    /* ── Kertas A4 (replika PDF) ─────────────────────────────── */
    .dlp-paper {
        --u: 1px;                       /* fallback: ukuran asli 794px, bisa digeser horizontal */
        position: relative; width: 794px; margin: 0 auto;
        aspect-ratio: 794 / 1123;       /* minimal 1 halaman A4, memanjang bila isi lebih */
        background: #fff;
        box-shadow: 0 10px 35px -5px rgba(0,0,0,.22), 0 0 1px 1px rgba(0,0,0,.08);
    }
    @supports (container-type: inline-size) {
        .dlp-paper { width: 100%; max-width: 794px; container-type: inline-size; --u: calc(100cqw / 794); }
    }

    /* Kop: .header-kop di PDF → top 12, kiri/kanan 10, tinggi 100 (gambar direntang penuh) */
    .dlp-kop {
        position: absolute; z-index: 1;
        top: calc(12 * var(--u)); left: calc(10 * var(--u)); right: calc(10 * var(--u)); height: calc(100 * var(--u));
    }
    /* Latar: .letter-bg di PDF → dari y 180 sampai bawah halaman, selebar halaman */
    .dlp-bg {
        position: absolute; z-index: 0; pointer-events: none;
        top: calc(180 * var(--u)); left: 0; right: 0; height: calc(943 * var(--u));
    }
    /* Footer slogan: "Pelayanan Profesional dan Tepercaya" di kanan bawah halaman */
    .dlp-footer {
        position: absolute; z-index: 1; pointer-events: none;
        bottom: calc(38 * var(--u)); right: calc(30 * var(--u));
        width: calc(283 * var(--u)); height: calc(16.5 * var(--u));
    }
    .dlp-kop img, .dlp-bg img, .dlp-footer img { display: block; width: 100%; height: 100%; max-width: none; object-fit: contain; }
    .dlp-kop img, .dlp-bg img { object-fit: fill; }

    /* Garis batas halaman (bila isi lebih dari 1 halaman A4) */
    .dlp-pagebreaks {
        position: absolute; inset: 0; z-index: 3; pointer-events: none;
        background-image: repeating-linear-gradient(to bottom,
            transparent 0, transparent calc(1123 * var(--u) - 1px),
            rgba(100,116,139,.55) calc(1123 * var(--u) - 1px), rgba(100,116,139,.55) calc(1123 * var(--u)));
    }

    /* Isi: @page margin 120 60 60 60; body Arial 11pt, line-height 1.5, rata kiri-kanan */
    .dlp-content {
        position: relative; z-index: 2;
        padding: calc(120 * var(--u)) calc(60 * var(--u)) calc(60 * var(--u));
        font-family: Arial, Helvetica, sans-serif; font-size: calc(14.667 * var(--u)); line-height: 1.5;
        color: #1a1a1a; text-align: justify; overflow-wrap: break-word;
    }
    /* Kembalikan gaya bawaan dompdf (html.css) yang di-reset oleh CSS Filament */
    .dlp-content p { margin: 0 0 calc(10 * var(--u)); }
    .dlp-content h1, .dlp-content h2, .dlp-content h3, .dlp-content h4, .dlp-content h5, .dlp-content h6 { font-weight: bold; line-height: 1.5; color: inherit; }
    .dlp-content h1 { font-size: 2em; margin: .67em 0; }
    .dlp-content h2 { font-size: 1.5em; margin: .83em 0; }
    .dlp-content h3 { font-size: 1.17em; margin: 1em 0; }
    .dlp-content h4 { font-size: 1em; margin: 1.33em 0; }
    .dlp-content h5 { font-size: .83em; margin: 1.67em 0; }
    .dlp-content h6 { font-size: .67em; margin: 2.33em 0; }
    .dlp-content ul, .dlp-content ol { margin: 1em 0; padding-left: calc(40 * var(--u)); }
    .dlp-content ul { list-style: disc; }
    .dlp-content ol { list-style: decimal; }
    .dlp-content li { display: list-item; }
    .dlp-content blockquote { margin: 1em calc(40 * var(--u)); }
    .dlp-content strong, .dlp-content b { font-weight: bold; }
    .dlp-content em, .dlp-content i { font-style: italic; }
    .dlp-content u { text-decoration: underline; }
    .dlp-content s, .dlp-content strike { text-decoration: line-through; }
    .dlp-content a { color: #00f; text-decoration: underline; }
    .dlp-content code, .dlp-content pre { font-family: "Courier New", Courier, monospace; }
    .dlp-content hr { border: 0; border-top: 1px inset gray; margin: .5em 0; }
    .dlp-content img { display: inline; max-width: 100%; height: auto; }
    .dlp-content table {
        width: 100%;
        border-collapse: collapse;
        margin: 0 0 calc(15 * var(--u));
        text-align: left;
    }
    /* Tabel tata letak tanpa border (identitas biodata, jadwal, tanda tangan & meta nomor/tanggal surat) */
    .dlp-content table.letter-layout-table,
    .dlp-content table.letter-meta-table,
    .dlp-content table.letter-signature-table,
    .dlp-content table[border='0'],
    .dlp-content table[style*="border: none"] {
        border: none !important;
    }
    .dlp-content table.letter-layout-table td,
    .dlp-content table.letter-layout-table th,
    .dlp-content table.letter-meta-table td,
    .dlp-content table.letter-meta-table th,
    .dlp-content table.letter-signature-table td,
    .dlp-content table.letter-signature-table th,
    .dlp-content table[border='0'] td,
    .dlp-content table[border='0'] th,
    .dlp-content table[style*="border: none"] td,
    .dlp-content table[style*="border: none"] th {
        border: none !important;
        padding: calc(3 * var(--u)) calc(4 * var(--u)) !important;
    }
    .dlp-content table.letter-signature-table {
        width: 100% !important;
        table-layout: fixed !important;
        border: none !important;
        margin-top: calc(24 * var(--u)) !important;
        margin-bottom: calc(20 * var(--u)) !important;
    }
    .dlp-content table.letter-signature-table td {
        border: none !important;
        padding: 0 !important;
    }
    .dlp-content table.letter-signature-table tr > td:first-child {
        width: 58% !important;
        min-width: 58% !important;
        max-width: 58% !important;
        border: none !important;
    }
    .dlp-content table.letter-signature-table tr > td:last-child,
    .dlp-content .letter-signature-block {
        width: 42% !important;
        min-width: 42% !important;
        max-width: 42% !important;
        text-align: left !important;
        vertical-align: top !important;
        padding-left: 0 !important;
        border: none !important;
    }
    .dlp-content .letter-sig-stamp-container {
        position: relative;
        width: 100%;
        min-height: calc(65 * var(--u));
        margin: calc(4 * var(--u)) 0;
    }
    .dlp-content .letter-sig-stamp-container .letter-stamp-img {
        position: absolute;
        left: calc(-50 * var(--u));
        top: 0;
        width: calc(135 * var(--u));
        height: auto;
        opacity: 0.88;
        pointer-events: none;
        z-index: 2;
    }
    .dlp-content .letter-sig-stamp-container .letter-signature-img {
        position: relative;
        max-height: calc(75 * var(--u));
        max-width: calc(175 * var(--u));
        object-fit: contain;
        display: block;
        z-index: 1;
    }

    /* Tabel data bergaris (Tabel Petugas SPT, Daftar Undangan, SPPD, dll.) */
    .dlp-content table.letter-data-table,
    .dlp-content table:not(.letter-layout-table):not(.letter-meta-table):not(.letter-signature-table) {
        border-collapse: collapse;
        border: 1px solid #333 !important;
    }
    .dlp-content table.letter-data-table td,
    .dlp-content table.letter-data-table th,
    .dlp-content table:not(.letter-layout-table):not(.letter-meta-table):not(.letter-signature-table) td,
    .dlp-content table:not(.letter-layout-table):not(.letter-meta-table):not(.letter-signature-table) th {
        padding: calc(6 * var(--u)) calc(10 * var(--u));
        vertical-align: middle;
        border: 1px solid #333 !important;
    }
    .dlp-content table.letter-data-table th,
    .dlp-content table:not(.letter-layout-table):not(.letter-meta-table):not(.letter-signature-table) th {
        font-weight: bold;
        text-align: center;
        background-color: #f2f2f2;
    }

    .dlp-empty { color: #94a3b8; font-style: italic; text-align: center; padding-top: calc(200 * var(--u)); }

    /* Mode gelap Filament (class "dark" pada <html>) — kertas tetap putih */
    .dark .dlp-toolbar { background: rgba(30,41,59,.8); border-color: #334155; }
    .dark .dlp-badge { background: rgba(2,44,34,.6); color: #6ee7b7; border-color: #065f46; }
    .dark .dlp-code, .dark .dlp-note { color: #94a3b8; }
    .dark .dlp-btn-secondary { color: #e2e8f0; background: #334155; border-color: #475569; }
    .dark .dlp-btn-secondary:hover { background: #475569; }
    .dark .dlp-btn-word { color: #93c5fd; background: rgba(23,37,84,.5); border-color: #1e40af; }
    .dark .dlp-btn-word:hover { background: rgba(30,58,138,.6); }
    .dark .dlp-desk { background: rgba(15,23,42,.9); border-color: #1e293b; }

    @media (max-width: 640px) {
        .dlp-desk { padding: .75rem .5rem; }
    }

    /* Pratinjau di samping editor (form buat/edit): ikut terlihat saat menggulir */
    .dlp-sticky { position: sticky; top: 5rem; align-self: start; }

    /* Kolom sempit (mis. di samping editor): alas meja & toolbar lebih ringkas */
    .dlp { container-type: inline-size; }
    @container (max-width: 560px) {
        .dlp-desk { padding: .75rem; border-radius: .75rem; }
        .dlp-toolbar { padding: .625rem; }
    }
</style>
@endonce

<div class="dlp">
    <!-- Toolbar -->
    <div class="dlp-toolbar">
        <div class="dlp-meta">
            <span class="dlp-badge">
                <span class="dlp-dot"></span>
                {{ $badge }}
            </span>
            <span class="dlp-code">{{ $code ?? 'Nomor belum dipilih' }}</span>
            @if ($note)
                <span class="dlp-note">· {{ $note }}</span>
            @endif
        </div>

        @if ($downloadPdfUrl || $streamPdfUrl || $downloadWordUrl)
            <div class="dlp-actions">
                @if ($downloadPdfUrl)
                    <a href="{{ $downloadPdfUrl }}" target="_blank" download class="dlp-btn dlp-btn-primary">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Unduh PDF
                    </a>
                @endif

                @if ($streamPdfUrl)
                    <a href="{{ $streamPdfUrl }}" target="_blank" class="dlp-btn dlp-btn-secondary">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        Buka Layar Penuh / Cetak
                    </a>
                @endif

                @if ($downloadWordUrl)
                    <a href="{{ $downloadWordUrl }}" download class="dlp-btn dlp-btn-word">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Unduh Word
                    </a>
                @endif
            </div>
        @endif
    </div>

    <!-- Alas meja + kertas A4 -->
    <div class="dlp-desk">
        <div class="dlp-paper">
            @if ($assets['kop'])
                <div class="dlp-kop"><img src="{{ $assets['kop'] }}" alt="Kop Surat RSU Syifa Medika Banjarbaru" /></div>
            @endif

            @if ($assets['bg'])
                <div class="dlp-bg"><img src="{{ $assets['bg'] }}" alt="" /></div>
            @endif

            @if (!empty($assets['footer']))
                <div class="dlp-footer"><img src="{{ $assets['footer'] }}" alt="Pelayanan Profesional dan Tepercaya" /></div>
            @endif

            <div class="dlp-pagebreaks" aria-hidden="true"></div>

            <div class="dlp-content">
                @if ($isEmpty)
                    <p class="dlp-empty">Isi surat akan tampil di sini saat Anda mulai menulis di editor.</p>
                @else
                    {!! $letterHtml !!}
                @endif
            </div>
        </div>
    </div>
</div>
