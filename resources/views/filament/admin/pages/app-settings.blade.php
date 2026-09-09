<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Logo Aplikasi</x-slot>
        <x-slot name="description">
            Dipakai di favicon, halaman login &amp; registrasi, email undangan rapat, notifikasi,
            dan ikon PWA. Klik <strong>Unggah / Ganti Logo</strong> di kanan atas untuk menggantinya.
            Logo kustom disimpan di <code>storage/app/public/branding/</code> sehingga tidak hilang saat deploy.
            <br>
            <span class="text-xs text-gray-400">Header PDF notulensi memakai logo terpisah
            (<code>public/images/logo-pdf.png</code>) dan tidak ikut berubah.</span>
        </x-slot>

        <div
            class="grid gap-4 sm:grid-cols-2"
            x-data="{ src: @js($this->previewUrl()) }"
            x-on:logo-updated.window="src = src.split('?')[0] + '?v=' + $event.detail.version; $nextTick(() => {
                Array.prototype.forEach.call(document.getElementsByTagName('link'), function (l) {
                    if ((l.getAttribute('rel') || '').indexOf('icon') !== -1) {
                        l.href = l.href.split('?')[0] + '?v=' + $event.detail.version;
                    }
                });
            })"
        >
            <div class="flex flex-col items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700">
                <img :src="src" alt="Logo (latar terang)" class="max-h-24 w-auto object-contain" loading="lazy" decoding="async">
                <span class="text-xs font-medium text-gray-500">Latar terang</span>
            </div>

            <div class="flex flex-col items-center justify-center gap-2 rounded-xl border border-gray-200 p-6 dark:border-gray-700" style="background:#0B2545;">
                <img :src="src" alt="Logo (latar gelap)" class="max-h-24 w-auto object-contain" loading="lazy" decoding="async">
                <span class="text-xs font-medium" style="color:rgba(255,255,255,.7);">Latar gelap (navbar)</span>
            </div>
        </div>

        <p class="mt-4 text-sm text-gray-500">
            Disarankan PNG transparan, rasio 1:1 atau lanskap, sisi terpanjang &le; 1024px. Maks. 4 MB.
        </p>
    </x-filament::section>
</x-filament-panels::page>
