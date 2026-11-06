<x-filament-panels::page>
    {{-- Form profil (tanpa action bar bawaan — tombol ada di paling bawah) --}}
    {{ $this->content }}

    {{-- Pengaturan Verifikasi Dua Faktor — card terpisah --}}
    @livewire('two-factor-settings')

    {{-- ── Action bar: selalu di PALING BAWAH, setelah semua card ──────────── --}}
    {{-- wire:click="save" memanggil method save() di class EditProfile (Livewire),
         identik dengan form submit sebelumnya. --}}
    <div style="display:flex; justify-content:flex-end; gap:12px; padding-top:8px; flex-wrap:wrap;">
        <x-filament::button
            type="button"
            color="gray"
            outlined
            icon="heroicon-o-arrow-left"
            x-on:click="document.referrer ? window.history.back() : (window.location.href = '{{ filament()->getUrl() }}')"
        >
            Kembali
        </x-filament::button>

        <x-filament::button
            type="button"
            color="primary"
            icon="heroicon-o-check-circle"
            wire:click="save"
            wire:loading.attr="disabled"
            wire:target="save"
        >
            <span wire:loading.remove wire:target="save">Simpan Perubahan</span>
            <span wire:loading wire:target="save">Menyimpan…</span>
        </x-filament::button>
    </div>
</x-filament-panels::page>
