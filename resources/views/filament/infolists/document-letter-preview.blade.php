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

<div class="space-y-4">
    <!-- Action / Toolbar Header -->
    <div class="flex flex-wrap items-center justify-between gap-3 p-3.5 bg-slate-100 dark:bg-slate-800/80 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
        <div class="flex items-center gap-2.5">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Format Surat Resmi
            </span>
            <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                {{ $code }}
            </span>
        </div>

        <div class="flex items-center gap-2">
            <!-- Unduh PDF -->
            <a href="{{ $downloadPdfUrl }}" target="_blank" download
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 rounded-lg shadow-sm transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Unduh PDF
            </a>

            <!-- Buka di Tab Baru / Cetak -->
            <a href="{{ $streamPdfUrl }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-700 hover:bg-slate-50 dark:hover:bg-slate-600 border border-slate-300 dark:border-slate-600 rounded-lg shadow-sm transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                Buka Layar Penuh / Cetak
            </a>

            <!-- Unduh Word -->
            <a href="{{ $downloadWordUrl }}" download
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/50 hover:bg-blue-100 border border-blue-200 dark:border-blue-800 rounded-lg transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Unduh Word
            </a>
        </div>
    </div>

    <!-- Background Desk Container (Dark-friendly wrapper) -->
    <div class="py-8 px-2 sm:px-6 bg-slate-200/80 dark:bg-slate-900/90 rounded-2xl border border-slate-300/70 dark:border-slate-800 flex justify-center overflow-x-auto">
        <!-- Sheet of Paper (Always white paper for realistic official letter look) -->
        <div style="background-color: #ffffff !important; color: #1e293b !important; width: 100%; max-width: 780px; min-height: 980px; padding: 48px 56px; border-radius: 4px; box-shadow: 0 10px 35px -5px rgba(0,0,0,0.22), 0 0 1px 1px rgba(0,0,0,0.08); position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between;"
             class="transition-all duration-200">

            <!-- Top Header (Kop Surat) -->
            <div style="position: relative; z-index: 10;">
                <img src="{{ $kopUrl }}" 
                     alt="Kop Surat RSU Syifa Medika Banjarbaru" 
                     style="width: 100%; height: auto; max-height: 95px; object-fit: contain; display: block; margin: 0 auto;" />
                <div style="border-bottom: 2px solid #2d7a60; margin-top: 10px; margin-bottom: 28px;"></div>
            </div>

            <!-- Watermark Background Emblem -->
            <div style="position: absolute; top: 48%; left: 50%; transform: translate(-50%, -50%); width: 340px; height: 340px; opacity: 0.10; pointer-events: none; z-index: 1;">
                <img src="{{ $watermarkUrl }}" alt="Watermark RSU Syifa Medika" style="width: 100%; height: 100%; object-fit: contain;" />
            </div>

            <!-- Letter Body Content -->
            <div style="position: relative; z-index: 5; flex: 1; font-family: Arial, Helvetica, sans-serif; font-size: 14.5px; line-height: 1.6; color: #1e293b;"
                 class="prose prose-slate max-w-none [&_table]:w-full [&_table]:border-collapse [&_table]:my-3 [&_td]:py-1.5 [&_td]:px-2">
                {!! $content !!}
            </div>

            <!-- Bottom Wave Graphic Footer -->
            <div style="margin-top: 48px; margin-left: -56px; margin-right: -56px; margin-bottom: -48px; position: relative; z-index: 10; pointer-events: none;">
                <img src="{{ $footerUrl }}" alt="RSU Syifa Medika Footer Wave" style="width: 100%; height: 60px; object-fit: cover; display: block;" />
            </div>
        </div>
    </div>
</div>

