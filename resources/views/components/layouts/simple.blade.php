<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    x-data
    x-bind:class="$store.theme?.dark ? 'dark' : ''"
>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Verifikasi Dua Faktor' }} — DCMS</title>
    {{-- Deteksi dark mode sebelum render untuk mencegah flash --}}
    <script>
        (function() {
            try {
                var pref = JSON.parse(localStorage.getItem('filament-color-scheme') || '""');
                if (pref === 'dark' || (pref === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch(e) {}
        })();
    </script>
    @filamentStyles
    @vite('resources/css/app.css')
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', system-ui, sans-serif;
            background: #f4f6fa;
        }
        html.dark body {
            background: #070d1a;
        }
    </style>
</head>
<body>
    {{ $slot }}
    @filamentScripts
    @livewireScripts
</body>
</html>
