<div
    x-data="{
        cooldown: {{ $cooldown }},
        startCooldown() {
            if (this.cooldown <= 0) return;
            const t = setInterval(() => {
                this.cooldown--;
                if (this.cooldown <= 0) clearInterval(t);
            }, 1000);
        }
    }"
    x-init="startCooldown()"
    @code-resent.window="cooldown = {{ config('two_factor.resend_cooldown_seconds', 60) }}; startCooldown()"
>
    <x-filament::section
        icon="heroicon-o-shield-check"
        icon-color="primary"
        icon-size="sm"
    >
        <x-slot name="heading">Verifikasi Dua Faktor (2FA)</x-slot>

        <x-slot name="description">
            Lapisan keamanan ekstra: kode OTP dikirim ke email saat login dari perangkat baru.
        </x-slot>

        <x-slot name="afterHeader">
            @if ($this->user->hasTwoFactorEnabled())
                <x-filament::badge color="success" icon="heroicon-m-check-circle">Aktif</x-filament::badge>
            @else
                <x-filament::badge color="gray" icon="heroicon-m-x-circle">Nonaktif</x-filament::badge>
            @endif
        </x-slot>

        <div class="space-y-4">

            {{-- ── Notifikasi sukses ──────────────────────────────────────────── --}}
            @if ($successMsg)
                <div class="rounded-xl bg-success-50 dark:bg-success-950/40 border border-success-200 dark:border-success-800/50 p-3.5 flex items-start justify-between gap-3 text-sm text-success-700 dark:text-success-300 transition" role="status">
                    <div class="flex items-start gap-2.5 min-w-0">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="text-success-600 dark:text-success-400 shrink-0 mt-0.5" style="width: 18px; height: 18px; min-width: 18px; min-height: 18px;" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
                        </svg>
                        <span class="break-words font-medium leading-relaxed">{{ $successMsg }}</span>
                    </div>
                    <button type="button" wire:click="dismissSuccess" class="text-success-500 hover:text-success-700 dark:text-success-400 dark:hover:text-success-200 p-0.5 rounded transition shrink-0" title="Tutup">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 16px; height: 16px; min-width: 16px; min-height: 16px;" aria-hidden="true">
                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                        </svg>
                    </button>
                </div>
            @endif

            {{-- ── Notifikasi error inline ────────────────────────────────────── --}}
            @if ($errorMsg && $step === 'idle')
                <div class="rounded-xl bg-danger-50 dark:bg-danger-950/40 border border-danger-200 dark:border-danger-800/50 p-3.5 flex items-start justify-between gap-3 text-sm text-danger-700 dark:text-danger-300 transition" role="alert">
                    <div class="flex items-start gap-2.5 min-w-0">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="text-danger-600 dark:text-danger-400 shrink-0 mt-0.5" style="width: 18px; height: 18px; min-width: 18px; min-height: 18px;" aria-hidden="true">
                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                        </svg>
                        <span class="break-words font-medium leading-relaxed">{{ $errorMsg }}</span>
                    </div>
                    <button type="button" wire:click="dismissError" class="text-danger-500 hover:text-danger-700 dark:text-danger-400 dark:hover:text-danger-200 p-0.5 rounded transition shrink-0" title="Tutup peringatan">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 16px; height: 16px; min-width: 16px; min-height: 16px;" aria-hidden="true">
                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                        </svg>
                    </button>
                </div>
            @endif

            {{-- ═══════════════════════════ IDLE STATE ═════════════════════════ --}}
            @if ($step === 'idle')

                @if ($this->user->hasTwoFactorEnabled())
                    {{-- Status: 2FA aktif — tampilkan keterangan + tombol nonaktifkan --}}
                    <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                        Setiap login dari perangkat baru memerlukan kode verifikasi yang dikirim ke email.
                        Diaktifkan pada
                        <strong>{{ $this->user->two_factor_enabled_at?->translatedFormat('d F Y, H:i') ?? '—' }}</strong>.
                    </p>
                    <div class="mt-4">
                        <x-filament::button
                            wire:click="initDisable"
                            wire:loading.attr="disabled"
                            wire:target="initDisable"
                            wire:confirm="Nonaktifkan verifikasi dua faktor? Kode konfirmasi akan dikirim ke email Anda."
                            color="danger"
                            icon="heroicon-m-lock-open"
                            size="sm"
                        >
                            <span wire:loading.remove wire:target="initDisable">Nonaktifkan 2FA</span>
                            <span wire:loading wire:target="initDisable">Mengirim kode…</span>
                        </x-filament::button>
                    </div>
                @else
                    {{-- Status: 2FA nonaktif — tampilkan penjelasan + tombol aktifkan --}}
                    <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                        Aktifkan untuk melindungi akun Anda dari akses tidak sah.
                        Kode verifikasi akan dikirim ke email saat login dari perangkat baru.
                    </p>
                    <div class="mt-4">
                        <x-filament::button
                            wire:click="initEnable"
                            wire:loading.attr="disabled"
                            wire:target="initEnable"
                            color="primary"
                            icon="heroicon-m-shield-check"
                            size="sm"
                        >
                            <span wire:loading.remove wire:target="initEnable">Aktifkan 2FA</span>
                            <span wire:loading wire:target="initEnable">Mengirim kode…</span>
                        </x-filament::button>
                    </div>
                @endif

            {{-- ═══════════════════ CONFIRM ENABLE / DISABLE ════════════════════ --}}
            @elseif (in_array($step, ['confirm_enable', 'confirm_disable']))

                <div style="max-width: 480px;">

                    {{-- Sub-heading biasa tanpa card pembungkus --}}
                    <div style="margin-bottom: 16px;">
                        <p class="tfa-confirm-heading">
                            {{ $step === 'confirm_enable' ? 'Konfirmasi Aktifkan 2FA' : 'Konfirmasi Nonaktifkan 2FA' }}
                        </p>
                        <p class="tfa-confirm-desc">
                            Kode verifikasi telah dikirim ke <strong>{{ Auth::user()->email }}</strong>.<br>
                            Masukkan kode 6 digit untuk {{ $step === 'confirm_enable' ? 'mengaktifkan' : 'menonaktifkan' }} 2FA.
                        </p>
                    </div>

                    {{-- Pesan error inline --}}
                    @if ($errorMsg)
                        <div
                            class="tfa-error-alert"
                            role="alert"
                            aria-live="polite"
                            style="margin-bottom: 16px;"
                        >
                            <div class="tfa-error-alert-body">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                                </svg>
                                <span>{{ $errorMsg }}</span>
                            </div>
                            <button
                                type="button"
                                wire:click="dismissError"
                                class="tfa-error-dismiss"
                                aria-label="Tutup peringatan"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                                </svg>
                            </button>
                        </div>
                    @endif

                    {{-- Input OTP — satu input statis, langsung di-render oleh Blade/PHP --}}
                    {{-- Tidak memakai <template x-for> supaya input selalu ada di DOM tanpa Alpine --}}
                    <div style="margin-bottom: 20px;">
                        <label for="otp_code_input" class="tfa-label">Kode Verifikasi</label>

                        <input
                            id="otp_code_input"
                            type="text"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            maxlength="6"
                            autocomplete="one-time-code"
                            placeholder="000000"
                            wire:model="code"
                            wire:loading.attr="disabled"
                            @if ($errorMsg) aria-invalid="true" @endif
                            aria-describedby="otp_helper"
                            autofocus
                            class="tfa-otp-input{{ $errorMsg ? ' tfa-otp-error' : '' }}"
                        >

                        <p class="tfa-helper" id="otp_helper">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-13a.75.75 0 0 0-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 0 0 0-1.5h-3.25V5Z" clip-rule="evenodd" />
                            </svg>
                            <span>Berlaku {{ config('two_factor.code_ttl_minutes', 10) }} menit · Sekali pakai</span>
                        </p>
                    </div>

                    {{-- Baris Tombol --}}
                    <div class="tfa-btn-row">
                        @if ($step === 'confirm_enable')
                            <x-filament::button
                                wire:click="confirmEnable"
                                wire:loading.attr="disabled"
                                wire:target="confirmEnable"
                                color="primary"
                                icon="heroicon-m-shield-check"
                                size="md"
                            >
                                <span wire:loading.remove wire:target="confirmEnable">Aktifkan 2FA</span>
                                <span wire:loading wire:target="confirmEnable">Memverifikasi…</span>
                            </x-filament::button>
                        @else
                            <x-filament::button
                                wire:click="confirmDisable"
                                wire:loading.attr="disabled"
                                wire:target="confirmDisable"
                                color="danger"
                                icon="heroicon-m-lock-open"
                                size="md"
                            >
                                <span wire:loading.remove wire:target="confirmDisable">Nonaktifkan 2FA</span>
                                <span wire:loading wire:target="confirmDisable">Memverifikasi…</span>
                            </x-filament::button>
                        @endif

                        <x-filament::button
                            wire:click="resendCode"
                            wire:loading.attr="disabled"
                            wire:target="resendCode"
                            color="gray"
                            icon="heroicon-m-paper-airplane"
                            size="md"
                            outlined
                            x-bind:disabled="cooldown > 0"
                        >
                            <span wire:loading.remove wire:target="resendCode">
                                <span x-show="cooldown <= 0">Kirim ulang kode</span>
                                <span x-show="cooldown > 0" x-cloak>
                                    Kirim ulang (<span x-text="cooldown"></span>d)
                                </span>
                            </span>
                            <span wire:loading wire:target="resendCode">Mengirim ulang…</span>
                        </x-filament::button>

                        <x-filament::button
                            wire:click="cancel"
                            wire:loading.attr="disabled"
                            color="gray"
                            size="md"
                        >
                            Batal
                        </x-filament::button>
                    </div>

                </div>

            @endif

            {{-- ════════ PENGATURAN DURASI DEFAULT (hanya jika 2FA aktif) ════════ --}}
            @if ($this->user->hasTwoFactorEnabled())

                <x-filament::section collapsible>
                    <x-slot name="heading">Durasi Kepercayaan Perangkat Default</x-slot>
                    <x-slot name="description">
                        Berapa lama perangkat diingat secara default saat login dengan 2FA.
                        Perangkat yang sudah didaftarkan tidak terpengaruh.
                    </x-slot>

                    <div class="flex flex-wrap items-center gap-3">
                        <x-filament::input.wrapper style="width: auto; min-width: 140px;">
                            <x-filament::input.select wire:model="defaultTrustDays">
                                @foreach ($this->durationOptions as $days => $label)
                                    <option value="{{ $days }}">{{ $label }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>

                        <x-filament::button
                            wire:click="saveDefaultTrustDays"
                            color="gray"
                            icon="heroicon-m-check"
                            size="sm"
                        >
                            Simpan
                        </x-filament::button>
                    </div>
                </x-filament::section>

                {{-- ════════════════ DAFTAR PERANGKAT TEPERCAYA ════════════════ --}}
                <x-filament::section collapsible>
                    <x-slot name="heading">Perangkat Tepercaya</x-slot>
                    <x-slot name="description">
                        Perangkat yang tidak dimintai kode 2FA selama masa berlakunya.
                    </x-slot>

                    <x-slot name="afterHeader">
                        @if ($this->trustedDevices->isNotEmpty())
                            <x-filament::button
                                wire:click="revokeAllDevices"
                                wire:confirm="Cabut semua perangkat tepercaya? Semua perangkat akan dimintai kode 2FA saat login."
                                color="danger"
                                size="xs"
                            >
                                Cabut semua
                            </x-filament::button>
                        @endif
                    </x-slot>

                    <ul class="divide-y divide-gray-100 dark:divide-white/10">
                        @forelse ($this->trustedDevices as $device)
                            <li class="flex items-center justify-between py-3 gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <x-filament::icon
                                        icon="heroicon-o-computer-desktop"
                                        class="w-5 h-5 text-primary-500 shrink-0"
                                    />
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">
                                            {{ $device->display_name }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-500">
                                            IP: {{ $device->last_ip ?? $device->ip_address ?? '—' }}
                                            · Terakhir: {{ $device->last_used_at?->diffForHumans() ?? '—' }}
                                            · Kedaluwarsa: {{ $device->expires_at->translatedFormat('d M Y') }}
                                        </p>
                                    </div>
                                </div>
                                <x-filament::button
                                    wire:click="revokeDevice({{ $device->id }})"
                                    wire:confirm="Cabut kepercayaan perangkat ini?"
                                    color="danger"
                                    size="xs"
                                    outlined
                                >
                                    Cabut
                                </x-filament::button>
                            </li>
                        @empty
                            <li class="py-4 text-center">
                                <p class="text-sm text-gray-400 dark:text-gray-500">
                                    Belum ada perangkat tepercaya yang terdaftar.
                                </p>
                            </li>
                        @endforelse
                    </ul>
                </x-filament::section>

            @endif

        </div>
    </x-filament::section>
</div>
