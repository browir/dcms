{{-- PWA: manifest, ikon, warna tema, dan registrasi service worker --}}
<link rel="manifest" href="{{ url('/manifest.json') }}">
<meta name="theme-color" content="#0B2545">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="DCMS">
<link rel="apple-touch-icon" href="{{ url('/pwa-icon.svg') }}">
<link rel="apple-touch-icon" sizes="512x512" href="{{ asset('images/logo.png') }}?v={{ @filemtime(public_path('images/logo.png')) ?: 1 }}">

<script>
    (function () {
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(function (e) {
                    console.warn('[PWA] Service worker gagal didaftarkan:', e);
                });
            });
        }

        // Simpan event beforeinstallprompt agar bisa dipicu oleh tombol "Install Aplikasi".
        window.deferredPwaPrompt = window.deferredPwaPrompt || null;
        window.addEventListener('beforeinstallprompt', function (e) {
            e.preventDefault();
            window.deferredPwaPrompt = e;
            window.dispatchEvent(new CustomEvent('pwa-installable'));
        });
        window.addEventListener('appinstalled', function () {
            window.deferredPwaPrompt = null;
            window.dispatchEvent(new CustomEvent('pwa-installed'));
        });
    })();
</script>
