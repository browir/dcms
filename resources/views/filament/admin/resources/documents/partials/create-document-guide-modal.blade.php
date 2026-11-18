@php
    $urlCara1Rel = url('/videos/panduan-buat-dokumen-cara-1.mp4', [], false);
    $urlCara1Abs = asset('videos/panduan-buat-dokumen-cara-1.mp4');
    $urlCara2Rel = url('/videos/panduan-buat-dokumen-cara-2.mp4', [], false);
    $urlCara2Abs = asset('videos/panduan-buat-dokumen-cara-2.mp4');
@endphp

<style>
    .cdm-guide-wrap {
        font-family: inherit;
        font-size: 0.85rem;
        color: #374151;
        display: flex;
        flex-direction: column;
        gap: 1rem;
        padding: 0.2rem 0 0.4rem;
    }
    html.dark .cdm-guide-wrap {
        color: #d1d5db;
    }

    /* Banner Header */
    .cdm-banner {
        position: relative;
        overflow: hidden;
        border-radius: 0.875rem;
        background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 60%, #2563eb 100%);
        padding: 0.95rem 1.15rem;
        color: #fff;
    }
    .cdm-banner-inner {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        position: relative;
        z-index: 1;
    }
    .cdm-banner-icon {
        width: 2.3rem;
        height: 2.3rem;
        min-width: 2.3rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.65rem;
        background: rgba(255, 255, 255, 0.18);
        flex-shrink: 0;
    }
    .cdm-banner-glow {
        position: absolute;
        right: -1.5rem;
        bottom: -1.5rem;
        width: 6.5rem;
        height: 6.5rem;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
        filter: blur(20px);
    }

    /* Tab Switcher */
    .cdm-tab-nav {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.5rem;
        background: rgba(243, 244, 246, 0.9);
        padding: 0.35rem;
        border-radius: 0.75rem;
        border: 1px solid #e5e7eb;
    }
    html.dark .cdm-tab-nav {
        background: rgba(17, 24, 39, 0.8);
        border-color: rgba(55, 65, 81, 0.7);
    }
    .cdm-tab-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.6rem 0.8rem;
        border-radius: 0.55rem;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
        background: transparent;
        color: #4b5563;
        text-align: center;
    }
    html.dark .cdm-tab-btn {
        color: #9ca3af;
    }
    .cdm-tab-btn:hover {
        color: #1d4ed8;
        background: rgba(255, 255, 255, 0.6);
    }
    html.dark .cdm-tab-btn:hover {
        color: #38bdf8;
        background: rgba(31, 41, 55, 0.6);
    }
    .cdm-tab-btn.is-active {
        background: #ffffff;
        color: #1d4ed8;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    }
    html.dark .cdm-tab-btn.is-active {
        background: #1f2937;
        color: #38bdf8;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
    }

    /* Video Card */
    .cdm-video-card {
        border-radius: 0.75rem;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        background: #000000;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08);
    }
    html.dark .cdm-video-card {
        border-color: rgba(55, 65, 81, 0.8);
    }
    .cdm-video-player {
        width: 100% !important;
        height: auto !important;
        max-height: 420px !important;
        min-height: 220px;
        display: block !important;
        margin: 0 auto;
        background: #000000;
        border-radius: 0.75rem;
    }

    /* Steps Grid */
    .cdm-steps-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }
    @media (max-width: 640px) {
        .cdm-tab-nav {
            grid-template-columns: 1fr;
        }
        .cdm-steps-grid {
            grid-template-columns: 1fr;
        }
        .cdm-bottom-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Step Card */
    .cdm-step-card {
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        background: #ffffff;
        padding: 0.8rem 0.9rem;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }
    html.dark .cdm-step-card {
        background: rgba(31, 41, 55, 0.7);
        border-color: rgba(55, 65, 81, 0.6);
    }

    .cdm-step-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.1rem;
    }
    .cdm-step-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 0.4rem;
        padding: 0.12rem 0.45rem;
        font-weight: 700;
        font-size: 0.7rem;
    }
    .cdm-badge-1 { background: #dbeafe; color: #1d4ed8; }
    .cdm-badge-2 { background: #e0e7ff; color: #4338ca; }
    .cdm-badge-3 { background: #fef3c7; color: #d97706; }
    .cdm-badge-4 { background: #dcfce7; color: #15803d; }
    html.dark .cdm-badge-1 { background: rgba(29, 78, 216, 0.25); color: #93c5fd; }
    html.dark .cdm-badge-2 { background: rgba(67, 56, 202, 0.25); color: #a5b4fc; }
    html.dark .cdm-badge-3 { background: rgba(217, 119, 6, 0.25); color: #fde68a; }
    html.dark .cdm-badge-4 { background: rgba(21, 128, 61, 0.25); color: #86efac; }

    .cdm-step-title {
        font-weight: 700;
        font-size: 0.8rem;
        color: #111827;
    }
    html.dark .cdm-step-title {
        color: #f9fafb;
    }
    .cdm-step-desc {
        font-size: 0.72rem;
        color: #4b5563;
        line-height: 1.45;
        margin: 0;
    }
    html.dark .cdm-step-desc {
        color: #9ca3af;
    }

    /* Bottom Info Boxes */
    .cdm-bottom-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }
    .cdm-info-box {
        display: flex;
        align-items: flex-start;
        gap: 0.65rem;
        border-radius: 0.75rem;
        padding: 0.65rem 0.85rem;
        font-size: 0.71rem;
        line-height: 1.4;
    }
    .cdm-info-box-blue {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1e40af;
    }
    html.dark .cdm-info-box-blue {
        background: rgba(30, 58, 138, 0.25);
        border-color: rgba(59, 130, 246, 0.4);
        color: #bfdbfe;
    }
    .cdm-info-box-amber {
        background: #fffbeb;
        border: 1px solid #fde68a;
        color: #92400e;
    }
    html.dark .cdm-info-box-amber {
        background: rgba(146, 64, 14, 0.2);
        border-color: rgba(245, 158, 11, 0.35);
        color: #fde68a;
    }
    .cdm-info-icon {
        width: 1.7rem;
        height: 1.7rem;
        min-width: 1.7rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        flex-shrink: 0;
        margin-top: 1px;
    }
    .cdm-info-icon-blue { background: #1d4ed8; color: #fff; }
    .cdm-info-icon-amber { background: #d97706; color: #fff; }
    .cdm-info-title { font-weight: 700; font-size: 0.74rem; margin-bottom: 0.1rem; }
    .cdm-info-desc { margin: 0; }
</style>

<div class="cdm-guide-wrap" x-data="{ tab: 'cara1' }">

    {{-- Banner Header --}}
    <div class="cdm-banner">
        <div class="cdm-banner-inner">
            <div class="cdm-banner-icon">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width: 20px !important; height: 20px !important; min-width: 20px; max-width: 20px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
            </div>
            <div>
                <div style="font-size:0.92rem; font-weight:800; margin-bottom:0.1rem;">Video Panduan Pembuatan Dokumen Baru</div>
                <div style="font-size:0.72rem; color:rgba(255,255,255,0.88); line-height:1.4;">Pilih salah satu metode pembuatan dokumen di bawah untuk melihat video tutorial dan ringkasan langkahnya.</div>
            </div>
        </div>
        <div class="cdm-banner-glow"></div>
    </div>

    {{-- Tab Buttons --}}
    <div class="cdm-tab-nav">
        <button 
            type="button" 
            class="cdm-tab-btn" 
            :class="{ 'is-active': tab === 'cara1' }" 
            @click="tab = 'cara1'"
        >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px !important; height: 16px !important; min-width: 16px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            <span>Cara 1: Dari Formulir Dokumen</span>
        </button>

        <button 
            type="button" 
            class="cdm-tab-btn" 
            :class="{ 'is-active': tab === 'cara2' }" 
            @click="tab = 'cara2'"
        >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px !important; height: 16px !important; min-width: 16px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 8.25h13.5m-13.5 7.5h13.5M9.75 3.75l-1.5 16.5m7.5-16.5l-1.5 16.5" />
            </svg>
            <span>Cara 2: Dari Penomoran Surat (Isi Dokumen)</span>
        </button>
    </div>

    {{-- TAB 1: CARA 1 (Bikin Dokumen Langsung) --}}
    <div x-show="tab === 'cara1'" x-cloak style="display: flex; flex-direction: column; gap: 0.85rem;">
        {{-- Video Player Container Cara 1 --}}
        <div class="cdm-video-card">
            <video 
                controls 
                preload="metadata" 
                playsinline 
                class="cdm-video-player"
            >
                <source src="{{ $urlCara1Rel }}" type="video/mp4">
                <source src="{{ $urlCara1Abs }}" type="video/mp4">
                Browser Anda tidak mendukung pemutar video HTML5. Silakan unduh melalui 
                <a href="{{ $urlCara1Rel }}" download style="color: #38bdf8; text-decoration: underline;">Tautan Unduh Video Cara 1</a>.
            </video>
        </div>

        {{-- Langkah-langkah Cara 1 --}}
        <div class="cdm-steps-grid">
            <div class="cdm-step-card">
                <div class="cdm-step-top">
                    <span class="cdm-step-badge cdm-badge-1">Langkah 1</span>
                </div>
                <div class="cdm-step-title">Buka Menu Tambah Dokumen</div>
                <p class="cdm-step-desc">Klik tombol <strong>Tambah Dokumen</strong> pada halaman Manajemen Dokumen (atau <em>Buat Surat Keluar</em>).</p>
            </div>

            <div class="cdm-step-card">
                <div class="cdm-step-top">
                    <span class="cdm-step-badge cdm-badge-2">Langkah 2</span>
                </div>
                <div class="cdm-step-title">Ambil Nomor Langsung di Form</div>
                <p class="cdm-step-desc">Pada input <em>Nomor Dokumen</em>, klik icon <strong>+</strong>. Pilih kategori surat lalu klik <strong>Terbitkan Nomor</strong>. Nomor otomatis terpasang.</p>
            </div>

            <div class="cdm-step-card">
                <div class="cdm-step-top">
                    <span class="cdm-step-badge cdm-badge-3">Langkah 3</span>
                </div>
                <div class="cdm-step-title">Lengkapi Data & Berkas</div>
                <p class="cdm-step-desc">Pilih tipe berkas (PDF / Word / Excel / Formulir teks), isi judul resmi, departemen, dan tentukan status (Draft / Pending Review).</p>
            </div>

            <div class="cdm-step-card">
                <div class="cdm-step-top">
                    <span class="cdm-step-badge cdm-badge-4">Langkah 4</span>
                </div>
                <div class="cdm-step-title">Periksa Kesiapan & Simpan</div>
                <p class="cdm-step-desc">Pantau indikator kesiapan dokumen di kanan atas hingga lengkap (100%), kemudian klik tombol <strong>Buat</strong>.</p>
            </div>
        </div>
    </div>

    {{-- TAB 2: CARA 2 (Dari Register Penomoran) --}}
    <div x-show="tab === 'cara2'" x-cloak style="display: flex; flex-direction: column; gap: 0.85rem;">
        {{-- Video Player Container Cara 2 --}}
        <div class="cdm-video-card">
            <video 
                controls 
                preload="metadata" 
                playsinline 
                class="cdm-video-player"
            >
                <source src="{{ $urlCara2Rel }}" type="video/mp4">
                <source src="{{ $urlCara2Abs }}" type="video/mp4">
                Browser Anda tidak mendukung pemutar video HTML5. Silakan unduh melalui 
                <a href="{{ $urlCara2Rel }}" download style="color: #38bdf8; text-decoration: underline;">Tautan Unduh Video Cara 2</a>.
            </video>
        </div>

        {{-- Langkah-langkah Cara 2 --}}
        <div class="cdm-steps-grid">
            <div class="cdm-step-card">
                <div class="cdm-step-top">
                    <span class="cdm-step-badge cdm-badge-1">Langkah 1</span>
                </div>
                <div class="cdm-step-title">Buka Menu Penomoran Dokumen</div>
                <p class="cdm-step-desc">Masuk ke menu <strong>Penomoran Dokumen</strong> (atau <em>Surat Keluar</em>) pada bilah navigasi kiri sistem DCMS.</p>
            </div>

            <div class="cdm-step-card">
                <div class="cdm-step-top">
                    <span class="cdm-step-badge cdm-badge-2">Langkah 2</span>
                </div>
                <div class="cdm-step-title">Terbitkan Nomor Terlebih Dahulu</div>
                <p class="cdm-step-desc">Klik tombol <strong>Ambil Nomor Dokumen</strong>, pilih jenis dokumen & isi judul, lalu klik <strong>Terbitkan Nomor</strong>.</p>
            </div>

            <div class="cdm-step-card">
                <div class="cdm-step-top">
                    <span class="cdm-step-badge cdm-badge-3">Langkah 3</span>
                </div>
                <div class="cdm-step-title">Klik Tombol "Isi Dokumen"</div>
                <p class="cdm-step-desc">Klik tombol biru <strong>Isi Dokumen Sekarang</strong> pada notifikasi sukses atau tombol <strong>Isi Dokumen</strong> pada tabel register.</p>
            </div>

            <div class="cdm-step-card">
                <div class="cdm-step-top">
                    <span class="cdm-step-badge cdm-badge-4">Langkah 4</span>
                </div>
                <div class="cdm-step-title">Data Terkunci Otomatis & Unggah</div>
                <p class="cdm-step-desc">Formulir terbuka dengan Nomor, Judul, Tanggal, & Unit yang sudah terisi otomatis. Cukup unggah berkas lalu simpan!</p>
            </div>
        </div>
    </div>

    {{-- Bottom Info Highlight Boxes --}}
    <div class="cdm-bottom-grid">
        <div class="cdm-info-box cdm-info-box-blue">
            <div class="cdm-info-icon cdm-info-icon-blue">
                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="width: 12px !important; height: 12px !important; min-width: 12px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <div>
                <div class="cdm-info-title">Pencegahan File Duplikat</div>
                <p class="cdm-info-desc">Sistem otomatis mendeteksi berkas serupa berdasarkan hash konten untuk mencegah penumpukan dokumen ganda.</p>
            </div>
        </div>

        <div class="cdm-info-box cdm-info-box-amber">
            <div class="cdm-info-icon cdm-info-icon-amber">
                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="width: 12px !important; height: 12px !important; min-width: 12px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0zm-9 3.75h.008v.008H12v-.008z"/>
                </svg>
            </div>
            <div>
                <div class="cdm-info-title">Pengingat Kedaluwarsa & Kepatuhan</div>
                <p class="cdm-info-desc">Pengingat kedaluwarsa dokumen dikirim otomatis <strong>30 hari</strong> sebelumnya. Aktifkan <em>Wajib Dibaca</em> agar tampil di Compliance Hub.</p>
            </div>
        </div>
    </div>

</div>
