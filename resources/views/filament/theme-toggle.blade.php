{{-- Tombol ganti mode terang/gelap di topbar. Memakai mekanisme tema bawaan Filament
     (event theme-changed → localStorage 'theme' → kelas .dark di <html>). --}}
<div class="fi-topbar-item dcms-theme-toggle" x-data>
    <button
        type="button"
        x-on:click="$dispatch('theme-changed', $store.theme === 'dark' ? 'light' : 'dark')"
        x-bind:title="$store.theme === 'dark' ? 'Ganti ke mode terang' : 'Ganti ke mode gelap'"
        x-bind:aria-label="$store.theme === 'dark' ? 'Ganti ke mode terang' : 'Ganti ke mode gelap'"
        class="dcms-theme-toggle-btn"
    >
        {{-- Bulan: tampil di mode terang --}}
        <svg x-show="$store.theme !== 'dark'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
        </svg>
        {{-- Matahari: tampil di mode gelap --}}
        <svg x-show="$store.theme === 'dark'" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
        </svg>
    </button>

    <style>
        .dcms-theme-toggle { display: flex; align-items: center; margin-right: .25rem; }
        .dcms-theme-toggle-btn {
            display: flex; align-items: center; justify-content: center;
            width: 2.25rem; height: 2.25rem; border-radius: 50%;
            color: rgba(255, 255, 255, .75);
            transition: background-color .2s ease, color .2s ease, transform .2s cubic-bezier(.34, 1.56, .64, 1);
        }
        .dcms-theme-toggle-btn:hover { background-color: rgba(255, 255, 255, .12); color: #fff; transform: scale(1.08); }
        .dcms-theme-toggle-btn:focus-visible { outline: 2px solid #93c5fd; outline-offset: 2px; }
        .dcms-theme-toggle-btn svg { width: 1.2rem; height: 1.2rem; }
        html.dark .dcms-theme-toggle-btn { color: #fcd34d; }
    </style>
</div>
