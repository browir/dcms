@php
    $record = $getRecord();
    $content = $record?->content ?? '';
    $code = $record?->code_number ?? '-';
    $tanggalSurat = $record?->tanggal_surat
        ? $record->tanggal_surat->locale('id')->isoFormat('D MMMM YYYY')
        : now()->locale('id')->isoFormat('D MMMM YYYY');

    // Substitusi placeholder template jika belum terganti
    $content = str_replace(
        ['{nomor_surat}', '{nomor}', '{tanggal}', '{nama_direktur}', '{nip_direktur}'],
        [$code, $code, $tanggalSurat, 'dr. Puga Sharaz Wangi, M.A.R.S.', '01042019232'],
        $content
    );

    $downloadPdfUrl = route('documents.pdf.download', ['document' => $record->id]);
    $streamPdfUrl = route('documents.pdf.stream', ['document' => $record->id]);
    $downloadWordUrl = route('documents.word.download', ['document' => $record->id]);

    $kopUrl = asset('images/logo-rsusm.png');
    $watermarkUrl = asset('images/letter-watermark.png');
    $footerUrl = asset('images/letter-footer.png');
@endphp

{{--
    Catatan: view ini sengaja memakai CSS sendiri (prefix "dlp-"), bukan class utilitas Tailwind.
    Panel Filament tidak memakai custom theme yang meng-compile class Tailwind dari view kustom,
    sehingga class seperti "w-3.5 h-3.5" tidak punya efek dan ikon SVG melebar memenuhi layar.
--}}
<style>
    .dlp { display: flex; flex-direction: column; gap: 1rem; }

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

    .dlp-desk {
        display: flex; justify-content: center; overflow-x: auto; padding: 2rem 1.5rem;
        background: rgba(226,232,240,.8); border: 1px solid rgba(203,213,225,.7); border-radius: 1rem;
    }

    /* Lembar kertas: selalu putih agar menyerupai surat resmi, juga di mode gelap */
    .dlp-paper {
        position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between;
        width: 100%; max-width: 780px; min-height: 980px; padding: 48px 56px; border-radius: 4px;
        background: #fff; color: #1e293b;
        box-shadow: 0 10px 35px -5px rgba(0,0,0,.22), 0 0 1px 1px rgba(0,0,0,.08);
    }
    .dlp-kop { position: relative; z-index: 10; }
    .dlp-kop img { display: block; width: 100%; height: auto; max-height: 95px; margin: 0 auto; object-fit: contain; }
    .dlp-kop-line { border-bottom: 2px solid #2d7a60; margin: 10px 0 28px; }

    .dlp-watermark {
        position: absolute; top: 48%; left: 50%; transform: translate(-50%, -50%); z-index: 1;
        width: 340px; height: 340px; max-width: 70%; opacity: .10; pointer-events: none;
    }
    .dlp-watermark img { width: 100%; height: 100%; object-fit: contain; }

    .dlp-footer { position: relative; z-index: 10; margin: 48px -56px -48px; pointer-events: none; }
    .dlp-footer img { display: block; width: 100%; height: 60px; object-fit: cover; }

    /* Isi surat (HTML dari Rich Editor) */
    .dlp-body {
        position: relative; z-index: 5; flex: 1;
        font-family: Arial, Helvetica, sans-serif; font-size: 14.5px; line-height: 1.6; color: #1e293b;
        overflow-wrap: break-word;
    }
    .dlp-body p { margin: 0 0 10px; }
    .dlp-body h1, .dlp-body h2, .dlp-body h3, .dlp-body h4 { margin: 0 0 10px; font-weight: 700; line-height: 1.3; color: #0f172a; }
    .dlp-body h1 { font-size: 1.5em; } .dlp-body h2 { font-size: 1.3em; } .dlp-body h3 { font-size: 1.15em; }
    .dlp-body ul, .dlp-body ol { margin: 0 0 10px; padding-left: 1.5em; }
    .dlp-body ul { list-style: disc; } .dlp-body ol { list-style: decimal; }
    .dlp-body strong, .dlp-body b { font-weight: 700; }
    .dlp-body em, .dlp-body i { font-style: italic; }
    .dlp-body u { text-decoration: underline; }
    .dlp-body a { color: #047857; text-decoration: underline; }
    .dlp-body img { max-width: 100%; height: auto; }
    .dlp-body table { width: 100%; border-collapse: collapse; margin: .75rem 0; }
    .dlp-body td, .dlp-body th { padding: .375rem .5rem; vertical-align: top; }
    .dlp-body table[border='1'] td, .dlp-body table[border='1'] th,
    .dlp-body table.bordered td, .dlp-body table.bordered th { border: 1px solid #333; }

    /* Mode gelap Filament (class "dark" pada <html>) — kertas tetap putih */
    .dark .dlp-toolbar { background: rgba(30,41,59,.8); border-color: #334155; }
    .dark .dlp-badge { background: rgba(2,44,34,.6); color: #6ee7b7; border-color: #065f46; }
    .dark .dlp-code { color: #94a3b8; }
    .dark .dlp-btn-secondary { color: #e2e8f0; background: #334155; border-color: #475569; }
    .dark .dlp-btn-secondary:hover { background: #475569; }
    .dark .dlp-btn-word { color: #93c5fd; background: rgba(23,37,84,.5); border-color: #1e40af; }
    .dark .dlp-btn-word:hover { background: rgba(30,58,138,.6); }
    .dark .dlp-desk { background: rgba(15,23,42,.9); border-color: #1e293b; }

    @media (max-width: 640px) {
        .dlp-desk { padding: 1rem .5rem; }
        .dlp-paper { min-height: 0; padding: 28px 20px; }
        .dlp-footer { margin: 32px -20px -28px; }
        .dlp-body { font-size: 13.5px; }
    }
</style>

<div class="dlp">
    <!-- Toolbar -->
    <div class="dlp-toolbar">
        <div class="dlp-meta">
            <span class="dlp-badge">
                <span class="dlp-dot"></span>
                Format Surat Resmi
            </span>
            <span class="dlp-code">{{ $code }}</span>
        </div>

        <div class="dlp-actions">
            <a href="{{ $downloadPdfUrl }}" target="_blank" download class="dlp-btn dlp-btn-primary">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Unduh PDF
            </a>

            <a href="{{ $streamPdfUrl }}" target="_blank" class="dlp-btn dlp-btn-secondary">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                Buka Layar Penuh / Cetak
            </a>

            <a href="{{ $downloadWordUrl }}" download class="dlp-btn dlp-btn-word">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Unduh Word
            </a>
        </div>
    </div>

    <!-- Alas meja -->
    <div class="dlp-desk">
        <!-- Lembar kertas -->
        <div class="dlp-paper">
            <div class="dlp-kop">
                <img src="{{ $kopUrl }}" alt="Kop Surat RSU Syifa Medika Banjarbaru" />
                <div class="dlp-kop-line"></div>
            </div>

            <div class="dlp-watermark">
                <img src="{{ $watermarkUrl }}" alt="" />
            </div>

            <div class="dlp-body">
                {!! $content !!}
            </div>

            <div class="dlp-footer">
                <img src="{{ $footerUrl }}" alt="" />
            </div>
        </div>
    </div>
</div>
