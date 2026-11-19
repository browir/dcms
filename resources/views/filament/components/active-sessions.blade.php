<div>
    <x-filament::section
        icon="heroicon-o-computer-desktop"
        icon-color="primary"
        icon-size="sm"
    >
        <x-slot name="heading">Manajemen Sesi & Perangkat Aktif</x-slot>

        <x-slot name="description">
            Kelola peramban dan perangkat yang saat ini memiliki akses aktif ke akun Anda.
        </x-slot>

        <x-slot name="afterHeader">
            <x-filament::badge color="info">
                {{ count($this->sessions) }} Sesi Terhubung
            </x-filament::badge>
        </x-slot>

        <div class="space-y-4">

            {{-- ── Notifikasi Sukses ── --}}
            @if ($successMsg)
                <div class="rounded-xl bg-success-50 dark:bg-success-950/40 border border-success-200 dark:border-success-800/50 p-3.5 flex items-start justify-between gap-3 text-sm text-success-700 dark:text-success-300 transition" role="status">
                    <div class="flex items-start gap-2.5 min-w-0">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="text-success-600 dark:text-success-400 shrink-0 mt-0.5" style="width: 18px; height: 18px;" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
                        </svg>
                        <span class="break-words font-medium">{{ $successMsg }}</span>
                    </div>
                    <button type="button" wire:click="dismissSuccess" class="text-success-500 hover:text-success-700 dark:text-success-400 p-0.5 rounded transition shrink-0" title="Tutup">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 16px; height: 16px;">
                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                        </svg>
                    </button>
                </div>
            @endif

            {{-- ── Notifikasi Error ── --}}
            @if ($errorMsg)
                <div class="rounded-xl bg-danger-50 dark:bg-danger-950/40 border border-danger-200 dark:border-danger-800/50 p-3.5 flex items-start justify-between gap-3 text-sm text-danger-700 dark:text-danger-300 transition" role="alert">
                    <div class="flex items-start gap-2.5 min-w-0">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="text-danger-600 dark:text-danger-400 shrink-0 mt-0.5" style="width: 18px; height: 18px;" aria-hidden="true">
                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                        </svg>
                        <span class="break-words font-medium">{{ $errorMsg }}</span>
                    </div>
                    <button type="button" wire:click="dismissError" class="text-danger-500 hover:text-danger-700 dark:text-danger-400 p-0.5 rounded transition shrink-0" title="Tutup">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 16px; height: 16px;">
                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                        </svg>
                    </button>
                </div>
            @endif

            {{-- ── Daftar Sesi ── --}}
            <div class="divide-y divide-gray-100 dark:divide-white/10 rounded-xl border border-gray-200 dark:border-white/10 overflow-hidden bg-white dark:bg-gray-900">
                @foreach ($this->sessions as $session)
                    <div class="p-4 flex items-center justify-between gap-4 transition hover:bg-gray-50/60 dark:hover:bg-white/[0.02]">
                        <div class="flex items-center gap-3.5 min-w-0">
                            {{-- Icon Perangkat --}}
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 {{ $session['is_current_device'] ? 'bg-primary-50 text-primary-600 dark:bg-primary-950/60 dark:text-primary-400 border border-primary-200 dark:border-primary-800/40' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400 border border-gray-200 dark:border-gray-700/60' }}">
                                @if ($session['browser_info']['device_type'] === 'mobile')
                                    <x-filament::icon icon="heroicon-o-device-phone-mobile" class="w-5 h-5" />
                                @else
                                    <x-filament::icon icon="heroicon-o-computer-desktop" class="w-5 h-5" />
                                @endif
                            </div>

                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-semibold text-sm text-gray-900 dark:text-gray-100">
                                        {{ $session['browser_info']['summary'] }}
                                    </span>
                                    @if ($session['is_current_device'])
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-success-50 text-success-700 border border-success-200 dark:bg-success-950/60 dark:text-success-400 dark:border-success-800/50">
                                            <span class="w-1.5 h-1.5 rounded-full bg-success-500 animate-pulse"></span>
                                            Perangkat Ini
                                        </span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 flex items-center gap-2 flex-wrap">
                                    <span>IP: <code class="font-mono text-[11px]">{{ $session['ip_address'] }}</code></span>
                                    <span>·</span>
                                    <span>{{ $session['is_current_device'] ? 'Sedang aktif' : 'Aktif ' . $session['last_active'] }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Tombol Keluarkan Single Session (hanya untuk perangkat lain) --}}
                        @if (! $session['is_current_device'])
                            <x-filament::button
                                color="danger"
                                size="xs"
                                outlined
                                wire:click="logoutSingleSession('{{ $session['id'] }}')"
                                wire:loading.attr="disabled"
                            >
                                Keluarkan
                            </x-filament::button>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- ── Form Konfirmasi Logout Semua Sesi Lain ── --}}
            @if ($confirmingLogout)
                <div class="rounded-xl border border-danger-200 dark:border-danger-800/60 bg-danger-50/50 dark:bg-danger-950/20 p-4 space-y-3">
                    <div class="flex items-start gap-2.5">
                        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-5 h-5 text-danger-600 dark:text-danger-400 shrink-0 mt-0.5" />
                        <div>
                            <h4 class="text-sm font-semibold text-danger-900 dark:text-danger-200">Konfirmasi Keluar dari Semua Perangkat Lain</h4>
                            <p class="text-xs text-danger-700 dark:text-danger-300 mt-0.5">
                                Masukkan kata sandi akun Anda untuk memastikan bahwa tindakan ini memang dilakukan oleh Anda.
                            </p>
                        </div>
                    </div>

                    <div class="max-w-md">
                        <input
                            type="password"
                            wire:model="password"
                            placeholder="Kata sandi akun Anda"
                            class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm focus:border-danger-500 focus:ring-danger-500"
                            wire:keydown.enter="logoutOtherDevices"
                        />
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <x-filament::button
                            color="danger"
                            size="sm"
                            wire:click="logoutOtherDevices"
                            wire:loading.attr="disabled"
                        >
                            Konfirmasi & Keluarkan
                        </x-filament::button>

                        <x-filament::button
                            color="gray"
                            size="sm"
                            outlined
                            wire:click="cancelLogout"
                        >
                            Batal
                        </x-filament::button>
                    </div>
                </div>
            @else
                <div class="flex justify-end pt-1">
                    <x-filament::button
                        color="danger"
                        size="sm"
                        outlined
                        icon="heroicon-o-arrow-right-on-rectangle"
                        wire:click="confirmLogoutOtherDevices"
                    >
                        Keluar dari Semua Perangkat Lain
                    </x-filament::button>
                </div>
            @endif

        </div>
    </x-filament::section>
</div>

