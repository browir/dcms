{{--
    Animasi smooth fade saat toggle light/dark mode.
    Memakai View Transitions API (native browser) — tanpa dependency tambahan.
    Degradasi elegan: jika browser tidak mendukung, toggle tetap berfungsi normal.

    Cara kerja:
    1. Mencegat event 'theme-changed' di window (fase capture, sebelum Alpine Filament).
    2. Memanggil document.startViewTransition() lalu menerapkan perubahan tema di dalamnya.
    3. CSS opacity cross-fade smooth antara snapshot lama dan tampilan baru.
    4. Menghormati prefers-reduced-motion — animasi dinonaktifkan otomatis.

    Catatan teknis:
    - Filament menyimpan preferensi di localStorage key 'filament:theme:mode'.
    - Event 'theme-changed' di-dispatch ke window, bukan document.
    - Alpine store: window.Alpine.store('theme', 'dark'|'light').
    - Referensi kode sumber: public/js/filament/filament/app.js (alpine:init block).
--}}

<style>
    /*
     * View Transition: fade smooth antara tema lama dan baru.
     * Durasi 350ms dengan easing ease-in-out untuk kesan premium.
     */
    ::view-transition-old(root) {
        animation: dcms-vt-fade-out 0.35s ease-in-out forwards;
    }

    ::view-transition-new(root) {
        animation: dcms-vt-fade-in 0.35s ease-in-out forwards;
    }

    @keyframes dcms-vt-fade-out {
        from { opacity: 1; }
        to   { opacity: 0; }
    }

    @keyframes dcms-vt-fade-in {
        from { opacity: 0; }
        to   { opacity: 1; }
    }

    /* Hormati preferensi pengguna: kurangi gerakan jika diminta */
    @media (prefers-reduced-motion: reduce) {
        ::view-transition-old(root),
        ::view-transition-new(root) {
            animation: none !important;
        }
    }
</style>

<script>
(function () {
    'use strict';

    /**
     * Jalankan View Transition yang membungkus callback perubahan tema.
     * Jika browser tidak mendukung View Transitions API, fallback langsung.
     *
     * @param {Function} applyTheme - fungsi yang benar-benar mengubah tema
     */
    function runViewTransition(applyTheme) {
        if (!document.startViewTransition) {
            applyTheme();
            return;
        }
        document.startViewTransition(applyTheme);
    }

    /**
     * Tangkap event 'theme-changed' di window (fase capture, sebelum handler Alpine Filament).
     *
     * Filament mendengarkan event ini di:
     *   window.addEventListener('theme-changed', n => { localStorage...; Alpine.store... })
     * — referensi: public/js/filament/filament/app.js, blok alpine:init.
     *
     * Kita cegat dengan stopImmediatePropagation() agar handler Alpine tidak berjalan
     * dulu, lalu kita terapkan perubahan tema sendiri di dalam startViewTransition().
     */
    window.addEventListener('theme-changed', function (e) {
        /* Cegah handler Alpine Filament berjalan di luar transisi */
        e.stopImmediatePropagation();

        var newTheme = e.detail; /* 'dark', 'light', atau 'system' */

        runViewTransition(function () {
            applyFilamentTheme(newTheme);
        });
    }, true /* capture: true → berjalan lebih awal dari listener Alpine */);

    /**
     * Terapkan tema ke Filament secara manual:
     * 1. Simpan ke localStorage (key yang dipakai Filament: 'filament:theme:mode').
     * 2. Update Alpine store agar ikon tombol toggle ikut berubah.
     * 3. Fallback: terapkan class .dark ke <html> langsung jika Alpine belum siap.
     */
    function applyFilamentTheme(theme) {
        try {
            localStorage.setItem('filament:theme:mode', theme);
        } catch (err) {
            /* localStorage tidak tersedia — abaikan */
        }

        /* Resolusi 'system' ke dark/light berdasarkan preferensi OS */
        var resolvedTheme = theme;
        if (theme === 'system') {
            resolvedTheme = window.matchMedia('(prefers-color-scheme: dark)').matches
                ? 'dark'
                : 'light';
        }

        /* Update Alpine store — Alpine effect akan menerapkan class .dark ke <html> */
        if (window.Alpine && typeof window.Alpine.store === 'function') {
            try {
                window.Alpine.store('theme', resolvedTheme);
                return;
            } catch (err) {
                /* Alpine store gagal — fallback manual di bawah */
            }
        }

        /* Fallback manual jika Alpine belum siap */
        if (resolvedTheme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }

})();
</script>
