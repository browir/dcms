<div class="min-h-screen flex items-center justify-center px-4 py-12"
     style="background: var(--ui-bg, #f4f6fa);">
    <div class="w-full max-w-md">
        {{-- Card --}}
        <div class="bg-white dark:bg-[#111a2e] rounded-2xl shadow-xl border border-gray-100 dark:border-[#26344d] p-8">

            {{-- Header --}}
            <div class="text-center mb-8">
                {{-- Ikon perisai / ponsel --}}
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-blue-50 dark:bg-blue-900/30 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Verifikasi Google Authenticator
                </h1>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Buka aplikasi <strong>Google Authenticator</strong> di ponsel Anda dan masukkan 6 digit kode yang tampil.
                </p>
            </div>

            {{-- Error --}}
            @if ($errorMsg)
                <div class="mb-4 rounded-xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800/50 px-4 py-3 flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" style="width: 20px; height: 20px; min-width: 20px; min-height: 20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
                    </svg>
                    <p class="text-sm font-medium text-red-700 dark:text-red-300 leading-relaxed">{{ $errorMsg }}</p>
                </div>
            @endif

            {{-- Form --}}
            <form wire:submit="verify" class="space-y-5">

                {{-- Input kode --}}
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Kode Authenticator
                    </label>
                    <input
                        id="code"
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        maxlength="6"
                        autocomplete="one-time-code"
                        wire:model="code"
                        placeholder="000000"
                        autofocus
                        class="w-full text-center text-2xl font-mono font-bold tracking-[0.5em] rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#0f1729] text-gray-900 dark:text-white px-4 py-3.5 placeholder-gray-300 dark:placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    >
                    <p class="mt-1.5 text-xs text-gray-400 dark:text-gray-500 text-center">
                        Kode berubah otomatis setiap 30 detik pada layar ponsel Anda.
                    </p>
                </div>

                {{-- Pilihan perangkat tepercaya --}}
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 space-y-3">
                    <div class="flex items-center gap-3">
                        <input
                            id="trust-device"
                            type="checkbox"
                            wire:model.live="trustDevice"
                            class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        >
                        <label for="trust-device" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Percayai perangkat ini
                        </label>
                    </div>

                    @if ($trustDevice)
                        <div class="pl-7">
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Durasi kepercayaan</label>
                            <select
                                wire:model="trustDays"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#0f1729] text-gray-900 dark:text-white text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                @foreach ($this->durationOptions as $days => $label)
                                    <option value="{{ $days }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                Perangkat tepercaya tidak akan dimintai kode selama masa berlakunya.
                            </p>
                        </div>
                    @endif
                </div>

                {{-- Tombol verifikasi --}}
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="w-full flex items-center justify-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold py-3 px-4 transition disabled:opacity-60 disabled:cursor-not-allowed"
                >
                    <span wire:loading.remove>Verifikasi & Masuk</span>
                    <span wire:loading class="flex items-center gap-2">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Memverifikasi…
                    </span>
                </button>
            </form>

            {{-- Kembali ke login --}}
            <div class="mt-6 pt-5 border-t border-gray-100 dark:border-gray-800 text-center">
                <a href="{{ route('filament.admin.auth.login') }}"
                   class="text-sm text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition">
                    ← Kembali ke halaman masuk
                </a>
            </div>
        </div>

        {{-- Footer --}}
        <p class="mt-6 text-center text-xs text-gray-400 dark:text-gray-600">
            Jangan bagikan kode ini kepada siapa pun, termasuk tim dukungan.
        </p>
    </div>
</div>
