<x-filament-panels::page>
    <x-filament::section
        x-data="{
            bust: @js((string) $this->logoVersion()),
            src() { return @js(asset('images/logo.png')) + '?v=' + this.bust },
        }"
        x-on:logo-updated.window="
            bust = $event.detail.version;
            // Tukar favicon & apple-touch-icon di head tanpa reload.
            document.querySelectorAll(&quot;link[rel~='icon'], link[rel='apple-touch-icon'], link[rel='shortcut icon']&quot;)
                .forEach(l => { l.href = l.href.split('?')[0] + '?v=' + bust; });
        "
    >
        <x-slot name="heading">Logo Aplikasi</x-slot>
        <x-slot name="description">
            Berkas ini dipakai di favicon, halaman login &amp; registrasi, email undangan rapat,
            notifikasi, dan ikon PWA. Mengunggah logo baru akan langsung menimpa berkas lama
            (<code>public/images/logo.png</code>) dan pratinjau di bawah diperbarui tanpa perlu memuat ulang halaman.
            <br>
            <span class="text-xs text-gray-400">Catatan: header PDF notulensi memakai logo terpisah
            (<code>logo-pdf.png</code>) dan tidak ikut berubah.</span>
        </x-slot>

        <div class="grid gap-4 sm:grid-cols-2">
            {{-- Pratinjau di latar terang --}}
            <div class="flex flex-col items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700">
                <img :src="src()" alt="Logo (latar terang)" class="max-h-24 w-auto object-contain">
                <span class="text-xs font-medium text-gray-500">Latar terang</span>
            </div>

            {{-- Pratinjau di latar gelap (seperti navbar) --}}
            <div class="flex flex-col items-center justify-center gap-2 rounded-xl border border-gray-200 p-6 dark:border-gray-700" style="background:#0B2545;">
                <img :src="src()" alt="Logo (latar gelap)" class="max-h-24 w-auto object-contain">
                <span class="text-xs font-medium" style="color:rgba(255,255,255,.7);">Latar gelap (navbar)</span>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap items-center gap-3">
            {{ $this->uploadLogoAction }}
            <p class="text-sm text-gray-500">
                Disarankan PNG transparan, rasio mendekati 1:1 atau lanskap, sisi terpanjang &le; 1024px.
            </p>
        </div>
    </x-filament::section>
</x-filament-panels::page>
