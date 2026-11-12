{{--
    Animasi circular reveal saat toggle light/dark mode.
    Memakai View Transitions API (native browser) — tanpa dependency tambahan.
    Degradasi elegan: jika browser tidak mendukung, toggle tetap berfungsi normal.

    Cara kerja:
    1. Mencegat event 'theme-changed' di window (fase capture).
    2. Menyimpan posisi klik sebagai titik awal lingkaran (--dcms-vt-x, --dcms-vt-y).
    3. Memanggil document.startViewTransition(), menerapkan perubahan tema di dalamnya.
    4. CSS clip-path: circle() menganimasikan ekspansi dari titik klik ke seluruh layar.
    5. Menghormati prefers-reduced-motion — animasi dinonaktifkan otomatis.

    Catatan teknis:
    - Filament menyimpan preferensi di localStorage key 'filament:theme:mode'.
    - Event 'theme-changed' di-dispatch ke window, bukan document.
    - Alpine store: window.Alpine.store('theme', 'dark'|'light').
    - Referensi kode sumber: public/js/filament/filament/app.js (alpine:init block).
--}}

<style>
    /*
     * View Transition: nonaktifkan animasi default browser,
     * kita ganti sepenuhnya dengan clip-path circular reveal.
     */
    ::view-transition-old(root),
    ::view-transition-new(root) {
        animation: none;
        mix-blend-mode: normal;
    }

    /* Lapisan BARU (tema yang akan muncul) mengembang dari titik klik */
    ::view-transition-new(root) {
        clip-path: circle(0% at var(--dcms-vt-x, 50%) var(--dcms-vt-y, 0%));
        animation: dcms-vt-reveal 0.42s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        z-index: 2;
    }

    /* Lapisan LAMA (tema sebelumnya) tetap di bawah, tidak bergerak */
    ::view-transition-old(root) {
        animation: none;
        z-index: 1;
    }

    @keyframes dcms-vt-reveal {
        to {
            /* 150% memastikan lingkaran menutupi sudut terjauh layar */
            clip-path: circle(150% at var(--dcms-vt-x, 50%) var(--dcms-vt-y, 0%));
        }
    }

    /* Hormati preferensi pengguna: kurangi gerakan jika diminta */
    @media (prefers-reduced-motion: reduce) {
        ::view-transition-old(root),
        ::view-transition-new(root) {
            animation: none !important;
            clip-path: none !important;
        }
    }
</style>

<script>
(function () {
    'use strict';

    /* Simpan posisi klik terakhir dari seluruh dokumen (untuk titik asal lingkaran) */
    var _vtX = '50%';
    var _vtY = '0%';

    document.addEventListener('click', function (e) {
        _vtX = e.clientX + 'px';
        _vtY = e.clientY + 'px';
    }, true /* capture: rekam sebelum handler lain berjalan */);

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

        /* Set CSS custom property untuk posisi titik asal lingkaran */
        document.documentElement.style.setProperty('--dcms-vt-x', _vtX);
        document.documentElement.style.setProperty('--dcms-vt-y', _vtY);

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
     * 2. Terapkan/hapus class .dark di <html> (via Alpine effect jika tersedia).
     * 3. Perbarui Alpine store agar ikon tombol toggle ikut berubah.
     *
     * Ini mereplikasi persis logika di app.js agar perilaku identik.
     */
    function applyFilamentTheme(theme) {
        try {
            localStorage.setItem('filament:theme:mode', theme);
        } catch (err) {
            /* localStorage tidak tersedia (mode privat ketat, dll.) — abaikan */
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
                return; /* Alpine effect menangani classList, selesai */
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
