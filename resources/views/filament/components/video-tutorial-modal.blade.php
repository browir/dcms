@php
    $relativeUrl = url('/videos/panduan-penomoran-dokumen.mp4', [], false);
    $absoluteUrl = $videoUrl ?? asset('videos/panduan-penomoran-dokumen.mp4');
@endphp

<style>
    .dcms-tut-container {
        display: flex;
        flex-direction: column;
        gap: 16px;
        padding: 4px 0;
        font-family: inherit;
    }
    .dcms-tut-video-card {
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid rgba(229, 231, 235, 1);
        background: #000000;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
    }
    html.dark .dcms-tut-video-card {
        border-color: rgba(55, 65, 81, 1);
    }
    .dcms-tut-video {
        width: 100% !important;
        height: auto !important;
        max-height: 440px !important;
        min-height: 220px;
        display: block !important;
        margin: 0 auto;
        background: #000000;
        border-radius: 12px;
    }
    .dcms-tut-info-card {
        border-radius: 12px;
        border: 1px solid rgba(229, 231, 235, 1);
        background: rgba(249, 250, 251, 0.85);
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    html.dark .dcms-tut-info-card {
        border-color: rgba(55, 65, 81, 0.6);
        background: rgba(17, 24, 39, 0.6);
    }
    .dcms-tut-header {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 600;
        color: #0284c7;
    }
    html.dark .dcms-tut-header {
        color: #38bdf8;
    }
    .dcms-tut-header svg {
        width: 20px !important;
        height: 20px !important;
        min-width: 20px !important;
        max-width: 20px !important;
        min-height: 20px !important;
        max-height: 20px !important;
        flex-shrink: 0;
        display: inline-block;
    }
    .dcms-tut-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }
    @media (max-width: 640px) {
        .dcms-tut-grid {
            grid-template-columns: 1fr;
        }
    }
    .dcms-tut-step {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        background: #ffffff;
        padding: 12px;
        border-radius: 8px;
        border: 1px solid rgba(243, 244, 246, 1);
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    }
    html.dark .dcms-tut-step {
        background: rgba(31, 41, 55, 0.7);
        border-color: rgba(55, 65, 81, 0.5);
    }
    .dcms-tut-badge {
        width: 22px !important;
        height: 22px !important;
        min-width: 22px !important;
        max-width: 22px !important;
        min-height: 22px !important;
        max-height: 22px !important;
        border-radius: 9999px;
        background: #e0f2fe;
        color: #0284c7;
        font-size: 11px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    html.dark .dcms-tut-badge {
        background: rgba(2, 132, 199, 0.25);
        color: #7dd3fc;
    }
    .dcms-tut-content {
        font-size: 12px;
        line-height: 1.45;
        color: #4b5563;
    }
    html.dark .dcms-tut-content {
        color: #d1d5db;
    }
    .dcms-tut-content strong {
        display: block;
        font-weight: 600;
        color: #111827;
        margin-bottom: 2px;
    }
    html.dark .dcms-tut-content strong {
        color: #f9fafb;
    }
</style>

<div class="dcms-tut-container">
    {{-- Video Player Container --}}
    <div class="dcms-tut-video-card">
        <video 
            controls 
            preload="auto" 
            playsinline 
            class="dcms-tut-video"
            style="width: 100% !important; height: auto !important; max-height: 440px !important; min-height: 220px; display: block !important; margin: 0 auto; background: #000; border-radius: 12px;"
        >
            <source src="{{ $relativeUrl }}" type="video/mp4">
            <source src="{{ $absoluteUrl }}" type="video/mp4">
            Browser Anda tidak mendukung pemutar video HTML5. Silakan unduh video melalui tautan berikut: 
            <a href="{{ $relativeUrl }}" download style="color: #38bdf8; text-decoration: underline;">Unduh Video Panduan</a>
        </video>
    </div>

    {{-- Description & Key Steps --}}
    <div class="dcms-tut-info-card">
        <div class="dcms-tut-header">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 20px !important; height: 20px !important; min-width: 20px; max-width: 20px; display: inline-block;">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="16" x2="12" y2="12"></line>
                <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            <span>Ringkasan Langkah Pengambilan Nomor</span>
        </div>

        <div class="dcms-tut-grid">
            <div class="dcms-tut-step">
                <span class="dcms-tut-badge">1</span>
                <div class="dcms-tut-content">
                    <strong>Buka Formulir</strong>
                    <span>Klik tombol <em>Ambil Nomor Dokumen</em> di halaman ini, atau tombol <strong>+</strong> pada formulir Tambah Dokumen.</span>
                </div>
            </div>

            <div class="dcms-tut-step">
                <span class="dcms-tut-badge">2</span>
                <div class="dcms-tut-content">
                    <strong>Pilih Kategori Dokumen</strong>
                    <span>Pilih jenis surat / dokumen (misal: SOP, Edaran, SPT). Kode jenis otomatis disematkan pada nomor.</span>
                </div>
            </div>

            <div class="dcms-tut-step">
                <span class="dcms-tut-badge">3</span>
                <div class="dcms-tut-content">
                    <strong>Periksa Unit Kerja</strong>
                    <span>Unit kerja telah terkunci otomatis sesuai akun Anda. Kode perusahaan akan mengikuti unit Anda.</span>
                </div>
            </div>

            <div class="dcms-tut-step">
                <span class="dcms-tut-badge">4</span>
                <div class="dcms-tut-content">
                    <strong>Terbitkan Nomor</strong>
                    <span>Isi tanggal surat & judul dokumen, lalu klik <strong>Terbitkan Nomor</strong>. Nomor langsung siap dipakai.</span>
                </div>
            </div>
        </div>
    </div>
</div>
