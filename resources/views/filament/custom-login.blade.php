<div class="dcms-login-screen fixed inset-0 min-h-screen flex items-center justify-center font-sans overflow-y-auto p-4 md:p-10 transition-colors duration-300"
    x-data="{ showTutorial: false, showSsoGuide: false }">

    <script>
        (function () {
            const stored = localStorage.getItem('theme') || localStorage.getItem('filament:theme:mode');
            const isDark = stored === 'dark' || (!stored && window.matchMedia('(prefers-color-scheme: dark)').matches);
            if (isDark) {
                document.documentElement.classList.add('dark');
            } else if (stored === 'light') {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>
    
    {{-- Tombol Ganti Tema (Dark / Light) di pojok kanan atas --}}
    <div class="fixed top-4 right-4 z-50">
        <button
            type="button"
            x-data
            @click="
                const isDark = document.documentElement.classList.contains('dark');
                if (isDark) {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('theme', 'light');
                    localStorage.setItem('filament:theme:mode', 'light');
                    window.dispatchEvent(new CustomEvent('theme-changed', { detail: 'light' }));
                } else {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('theme', 'dark');
                    localStorage.setItem('filament:theme:mode', 'dark');
                    window.dispatchEvent(new CustomEvent('theme-changed', { detail: 'dark' }));
                }
            "
            class="dcms-theme-toggle-btn flex items-center justify-center w-10 h-10 rounded-xl transition-all shadow-sm"
            title="Ganti Mode Gelap / Terang"
            aria-label="Ganti Mode Gelap / Terang"
        >
            {{-- Icon Sun (tampil saat mode gelap) --}}
            <svg class="w-5 h-5 hidden dark:block text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
            </svg>
            {{-- Icon Moon (tampil saat mode terang) --}}
            <svg class="w-5 h-5 block dark:hidden text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
            </svg>
        </button>
    </div>

    {{-- Container Utama --}}
    <div class="relative w-full max-w-[440px] flex flex-col items-center animate-fade-in mx-auto z-10">
        
        {{-- Section Branding --}}
        <div class="w-full text-center mb-8">
            <div class="dcms-logo-wrapper inline-flex items-center justify-center p-3 rounded-2xl transition-all">
                <img src="{{ \App\Support\Branding::logoUrl() }}" alt="Logo DCMS"
                    class="h-28 md:h-32 w-auto object-contain">
            </div>

            <p class="dcms-brand-sub text-sm leading-relaxed max-w-sm mx-auto font-medium mt-3">
                Manajemen Rapat dan Dokumen
            </p>
        </div>

        {{-- Card Utama --}}
        <div class="dcms-login-card w-full rounded-xl shadow-[0_15px_60px_-10px_rgba(0,0,0,0.08)] p-10 md:p-12 mb-8 z-10 relative transition-all duration-300">

            @if ($ssoError = $this->ssoError())
                <div role="alert" class="dcms-sso-alert mb-5">{{ $ssoError }}</div>
            @endif

            {{-- Login utama: akun DCMS --}}
            <div class="mb-6">
                <h2 class="dcms-login-card-title text-lg font-bold">Masuk ke DCMS</h2>
                <p class="dcms-login-card-desc mt-1 text-[13px]">Gunakan nama pengguna/email dan kata sandi akun DCMS Anda.</p>
            </div>

            <form
                wire:submit.prevent="authenticate"
                x-ref="loginForm"
                x-on:keydown.enter="if (! ['BUTTON', 'A', 'TEXTAREA'].includes($event.target.tagName) && $event.target.type !== 'checkbox') { $event.preventDefault(); $refs.loginForm.requestSubmit() }"
                class="space-y-5"
            >
                @csrf
                <div class="fi-form-override space-y-5">
                    {{ $this->form }}
                </div>

                <div style="padding-top: 1rem;">
                    <button type="submit"
                        class="w-full py-4 bg-[#2563eb] text-white rounded-lg font-bold text-base transition-colors duration-200 hover:bg-[#1d4ed8] active:scale-[0.98] focus:outline-none focus:ring-4 focus:ring-blue-200 tracking-wide uppercase">
                        Masuk
                    </button>
                </div>
            </form>

            @if ($this->ssoEnabled())
                {{-- Login sekunder: akun SSO karyawan (Keycloak / HRIS) --}}
                <div class="dcms-sso-divider" role="separator"><span>atau</span></div>

                <a href="{{ route('keycloak-sso.redirect') }}" class="dcms-sso-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                    </svg>
                    Masuk dengan Akun SSO (HRIS)
                </a>
                <p class="dcms-sso-hint">
                    Username = NIP, password = password HRIS. SSO harus diaktifkan dulu di HRIS.
                    <button type="button" class="dcms-sso-guide-link" @click="showSsoGuide = true">Cara mengaktifkan SSO</button>
                </p>
            @endif

            <div class="dcms-login-divider mt-8 text-center pt-6">
                @if (filament()->hasRegistration())
                    <p class="dcms-login-foot-text text-[13px] mb-4">
                        Belum punya akun?
                        <a href="{{ filament()->getRegistrationUrl() }}"
                            class="dcms-login-foot-link font-bold hover:underline">Buat Akun</a>
                    </p>
                @endif

                {{-- Tombol Panduan dengan Heroicon Info --}}
                <button @click="showTutorial = true" 
                    type="button"
                    class="dcms-panduan-btn inline-flex items-center gap-2 px-4 py-2 rounded-full text-[12px] font-bold transition-all group">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 group-hover:scale-110 transition-transform">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                    Panduan
                </button>
            </div>
        </div>

        {{-- Tombol Install Aplikasi (PWA) --}}
        <div x-data="pwaInstall()" x-show="canShow" x-cloak class="w-full -mt-4 mb-8">
            <button type="button" @click="install()"
                class="dcms-pwa-btn w-full flex items-center justify-center gap-2.5 py-3.5 px-4 rounded-xl font-bold text-[13px] shadow-sm transition-all active:scale-[0.98]">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                </svg>
                <span x-text="isIOS ? 'Pasang Aplikasi ke Layar Utama' : 'Install Aplikasi DCMS'"></span>
            </button>
            <p class="dcms-pwa-hint mt-2 text-center text-[11px] font-medium">
                Akses lebih cepat langsung dari layar ponsel Anda
            </p>

            {{-- Panduan install manual (iOS / browser tanpa prompt otomatis) --}}
            <template x-teleport="body">
                <div x-show="showHelp"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"
                    @keydown.escape.window="showHelp = false" style="display: none;">
                    <div @click.away="showHelp = false"
                        class="dcms-modal-card rounded-2xl shadow-2xl max-w-sm w-full overflow-hidden">
                        <div class="dcms-modal-head px-6 py-4 border-b flex justify-between items-center">
                            <h3 class="text-base font-bold">Pasang Aplikasi DCMS</h3>
                            <button @click="showHelp = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="dcms-modal-body p-6 text-[13px] leading-relaxed">
                            <template x-if="isIOS">
                                <ol class="list-decimal list-inside space-y-2">
                                    <li>Buka menu <b>Bagikan</b> (ikon kotak dengan panah ke atas) di Safari.</li>
                                    <li>Pilih <b>Tambah ke Layar Utama</b> / <b>Add to Home Screen</b>.</li>
                                    <li>Ketuk <b>Tambah</b>. Ikon DCMS akan muncul di layar utama.</li>
                                </ol>
                            </template>
                            <template x-if="!isIOS">
                                <ol class="list-decimal list-inside space-y-2">
                                    <li>Buka menu browser (ikon <b>⋮</b> di pojok kanan atas).</li>
                                    <li>Pilih <b>Install aplikasi</b> atau <b>Tambahkan ke layar utama</b>.</li>
                                    <li>Konfirmasi dengan menekan <b>Install</b>.</li>
                                </ol>
                            </template>
                        </div>
                        <div class="dcms-modal-foot px-6 py-4 border-t text-right">
                            <button @click="showHelp = false" class="px-5 py-2 bg-gray-800 text-white rounded-lg font-bold text-[13px] hover:bg-gray-900 transition-colors">Mengerti</button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="w-full text-center text-[12px] text-[#94a3b8] font-medium tracking-wide">
            &copy; 2026 Syifa Global Group.
        </div>
    </div>

    {{-- Modal Tutorial --}}
    <template x-teleport="body">
        <div x-show="showTutorial" 
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"
            @keydown.escape.window="showTutorial = false"
            style="display: none;">
            
            <div @click.away="showTutorial = false" 
                class="dcms-modal-card rounded-2xl shadow-2xl max-w-4xl w-full overflow-hidden animate-fade-in">
                <div class="dcms-modal-head px-6 py-4 border-b flex justify-between items-center">
                    <h3 class="text-lg font-bold flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-blue-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                        </svg>
                        Panduan Halaman Masuk
                    </h3>
                    <button @click="showTutorial = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="dcms-modal-body p-4 overflow-y-auto max-h-[80vh]">
                    <img src="{{ asset('images/masuk.jpg') }}" alt="Panduan Masuk" class="w-full h-auto rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                </div>
                <div class="dcms-modal-foot px-6 py-4 border-t text-right">
                    <button @click="showTutorial = false" class="px-6 py-2 bg-gray-800 text-white rounded-lg font-bold hover:bg-gray-900 transition-colors">Tutup</button>
                </div>
            </div>
        </div>
    </template>

    {{-- Modal Panduan Aktivasi SSO --}}
    @if ($this->ssoEnabled())
        <template x-teleport="body">
            <div x-show="showSsoGuide"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"
                @keydown.escape.window="showSsoGuide = false" style="display: none;">
                <div @click.away="showSsoGuide = false"
                    role="dialog" aria-modal="true" aria-labelledby="sso-guide-title"
                    class="dcms-modal-card rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
                    <div class="dcms-modal-head px-6 py-4 border-b flex justify-between items-center">
                        <h3 id="sso-guide-title" class="text-base font-bold">Cara Mengaktifkan Login SSO</h3>
                        <button @click="showSsoGuide = false" aria-label="Tutup" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="dcms-modal-body p-6 text-[13px] leading-relaxed max-h-[70vh] overflow-y-auto">
                        <p class="mb-4">Akun SSO memakai akun karyawan di HRIS. Aktifkan sekali saja, setelah itu Anda bisa masuk ke DCMS dengan akun SSO.</p>
                        <ol class="dcms-sso-steps">
                            <li>
                                Buka <a href="https://hris.syifaglobalgroup.com" target="_blank" rel="noopener" class="text-[#2563eb] dark:text-[#60a5fa] font-bold hover:underline">hris.syifaglobalgroup.com</a> dan masuk dengan akun HRIS Anda.
                            </li>
                            <li>Klik <b>foto profil</b> di pojok kanan atas, lalu pilih menu <b>Akun</b>.</li>
                            <li>Pilih <b>SSO</b> untuk membuka halaman <b>Password SSO</b>.</li>
                            <li>Ketik <b>password HRIS</b> Anda, lalu klik <b>Samakan Password SSO</b>.</li>
                            <li>Kembali ke halaman ini, klik <b>Masuk dengan Akun SSO (HRIS)</b>, lalu masuk dengan <b>NIP</b> sebagai username dan <b>password HRIS</b> Anda.</li>
                        </ol>
                        <p class="mt-4 text-[12px] text-gray-500 dark:text-gray-400">Jika password HRIS diganti, password SSO otomatis ikut berubah.</p>
                    </div>
                    <div class="dcms-modal-foot px-6 py-4 border-t flex justify-end gap-2">
                        <a href="https://hris.syifaglobalgroup.com" target="_blank" rel="noopener"
                            class="px-4 py-2 border border-gray-300 dark:border-slate-700 text-gray-700 dark:text-gray-200 rounded-lg font-bold text-[13px] hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors">Buka HRIS</a>
                        <button @click="showSsoGuide = false" class="px-5 py-2 bg-gray-800 text-white rounded-lg font-bold text-[13px] hover:bg-gray-900 transition-colors">Mengerti</button>
                    </div>
                </div>
            </div>
        </template>
    @endif

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('pwaInstall', () => ({
                isIOS: false,
                isStandalone: false,
                installable: false,
                showHelp: false,
                get canShow() {
                    return !this.isStandalone && (this.installable || this.isIOS);
                },
                init() {
                    const ua = window.navigator.userAgent.toLowerCase();
                    this.isIOS = /iphone|ipad|ipod/.test(ua) ||
                        (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
                    this.isStandalone = window.matchMedia('(display-mode: standalone)').matches ||
                        window.navigator.standalone === true;

                    if (window.deferredPwaPrompt) {
                        this.installable = true;
                    }
                    window.addEventListener('pwa-installable', () => { this.installable = true; });
                    window.addEventListener('pwa-installed', () => {
                        this.installable = false;
                        this.isStandalone = true;
                    });
                },
                async install() {
                    const promptEvent = window.deferredPwaPrompt;
                    if (promptEvent) {
                        promptEvent.prompt();
                        try { await promptEvent.userChoice; } catch (e) {}
                        window.deferredPwaPrompt = null;
                        this.installable = false;
                        return;
                    }
                    // iOS Safari atau browser yang tidak menyediakan prompt otomatis
                    this.showHelp = true;
                },
            }));
        });
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    borderRadius: { 'xl': '18px' },
                }
            }
        }
    </script>

    <style>
        /* ── Layar Halaman Login ── */
        .dcms-login-screen {
            background-color: #f1f3f7;
        }
        html.dark .dcms-login-screen {
            background: #070d1a !important;
            background: linear-gradient(160deg, #070d1a 0%, #0b1530 55%, #0e1b3d 100%) !important;
        }

        /* ── Tombol Ganti Tema ── */
        .dcms-theme-toggle-btn {
            background-color: rgba(255, 255, 255, 0.9);
            border: 1px solid #cbd5e1;
            color: #475569;
        }
        .dcms-theme-toggle-btn:hover {
            background-color: #ffffff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        html.dark .dcms-theme-toggle-btn {
            background-color: #111a2e !important;
            border-color: #26344d !important;
            color: #fcd34d !important;
        }
        html.dark .dcms-theme-toggle-btn:hover {
            background-color: #152039 !important;
            border-color: #3b82f6 !important;
        }

        /* ── Logo & Branding ── */
        .dcms-logo-wrapper {
            background: transparent;
        }
        html.dark .dcms-logo-wrapper {
            background: rgba(255, 255, 255, 0.96) !important;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.45) !important;
        }
        .dcms-brand-sub {
            color: #64748b;
        }
        html.dark .dcms-brand-sub {
            color: #94a3b8 !important;
        }

        /* ── Card Login ── */
        .dcms-login-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
        }
        html.dark .dcms-login-card {
            background-color: #111a2e !important;
            border: 1px solid #26344d !important;
            box-shadow: 0 1px 0 rgba(255, 255, 255, 0.04) inset, 0 25px 60px -15px rgba(0, 0, 0, 0.75) !important;
        }

        /* ── Judul & Deskripsi ── */
        .dcms-login-card-title {
            color: #0B2545;
        }
        html.dark .dcms-login-card-title {
            color: #f8fafc !important;
        }
        .dcms-login-card-desc {
            color: #64748b;
        }
        html.dark .dcms-login-card-desc {
            color: #94a3b8 !important;
        }

        /* ── Form Inputs & Labels ── */
        .fi-form-override .fi-fo-field-wrp,
        .fi-form-override .fi-grid,
        .fi-form-override .fi-section-content {
            display: flex !important;
            flex-direction: column !important;
            width: 100% !important;
            gap: 0.25rem !important;
            border: none !important;
        }

        .fi-form-override label {
            color: #475569 !important;
            font-size: 0.75rem !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            margin-bottom: 0.2rem !important;
        }
        html.dark .fi-form-override label {
            color: #94a3b8 !important;
        }

        .fi-form-override .fi-input-wrp {
            background-color: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 8px !important;
            padding: 2px 4px !important;
            transition: all 0.2s ease;
        }
        html.dark .fi-form-override .fi-input-wrp {
            background-color: #0f1729 !important;
            border-color: #26344d !important;
        }
        html.dark .fi-form-override .fi-input-wrp:focus-within {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25) !important;
        }

        .fi-form-override .fi-input {
            color: #1e293b !important;
            font-size: 0.95rem !important;
            padding: 0.75rem !important;
        }
        html.dark .fi-form-override .fi-input {
            color: #f1f5f9 !important;
            background-color: transparent !important;
        }
        html.dark .fi-form-override .fi-input::placeholder {
            color: #64748b !important;
        }

        /* ── Checkbox Ingat Saya ── */
        .fi-form-override input[type="checkbox"] {
            -webkit-appearance: none !important;
            appearance: none !important;
            width: 1.2rem !important;
            height: 1.2rem !important;
            background-color: #ffffff !important;
            border: 2px solid #cbd5e1 !important;
            border-radius: 4px !important;
            position: relative !important;
            cursor: pointer !important;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        html.dark .fi-form-override input[type="checkbox"] {
            background-color: #0f1729 !important;
            border-color: #334155 !important;
        }
        .fi-form-override input[type="checkbox"]:checked {
            background-color: #2563eb !important;
            border-color: #2563eb !important;
        }
        .fi-form-override input[type="checkbox"]:checked::after {
            content: '✓' !important;
            position: absolute !important;
            color: white !important;
            font-size: 0.8rem !important;
            font-weight: bold !important;
            left: 50% !important;
            top: 50% !important;
            transform: translate(-50%, -50%) !important;
        }
        .fi-form-override .fi-checkbox-label,
        .fi-form-override span.text-sm {
            color: #475569 !important;
        }
        html.dark .fi-form-override .fi-checkbox-label,
        html.dark .fi-form-override span.text-sm {
            color: #cbd5e1 !important;
        }

        /* ── Footer Card ── */
        .dcms-login-divider {
            border-top: 1px solid #f1f5f9;
        }
        html.dark .dcms-login-divider {
            border-top-color: #1e293b !important;
        }
        .dcms-login-foot-text {
            color: #64748b;
        }
        html.dark .dcms-login-foot-text {
            color: #94a3b8 !important;
        }
        .dcms-login-foot-link {
            color: #2563eb;
        }
        html.dark .dcms-login-foot-link {
            color: #60a5fa !important;
        }

        /* ── Tombol Panduan ── */
        .dcms-panduan-btn {
            background-color: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #dbeafe;
        }
        .dcms-panduan-btn:hover {
            background-color: #dbeafe;
        }
        html.dark .dcms-panduan-btn {
            background-color: rgba(37, 99, 235, 0.16) !important;
            color: #93c5fd !important;
            border-color: rgba(37, 99, 235, 0.32) !important;
        }
        html.dark .dcms-panduan-btn:hover {
            background-color: rgba(37, 99, 235, 0.28) !important;
        }

        /* ── Tombol PWA ── */
        .dcms-pwa-btn {
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            color: #0B2545;
        }
        .dcms-pwa-btn:hover {
            border-color: #2563eb;
            color: #2563eb;
        }
        html.dark .dcms-pwa-btn {
            background-color: #111a2e !important;
            border-color: #26344d !important;
            color: #e2e8f0 !important;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3) !important;
        }
        html.dark .dcms-pwa-btn:hover {
            border-color: #60a5fa !important;
            color: #60a5fa !important;
        }
        .dcms-pwa-hint {
            color: #94a3b8;
        }
        html.dark .dcms-pwa-hint {
            color: #64748b !important;
        }

        /* ── Modals (Panduan & SSO Guide) ── */
        .dcms-modal-card {
            background-color: #ffffff;
        }
        .dcms-modal-head {
            background-color: #f8fafc;
            border-bottom-color: #f1f5f9;
            color: #1e293b;
        }
        .dcms-modal-body {
            background-color: #ffffff;
            color: #475569;
        }
        .dcms-modal-foot {
            background-color: #ffffff;
            border-top-color: #f1f5f9;
        }
        html.dark .dcms-modal-card {
            background-color: #111a2e !important;
            border: 1px solid #26344d !important;
        }
        html.dark .dcms-modal-head {
            background-color: #0f1729 !important;
            border-bottom-color: #26344d !important;
            color: #f8fafc !important;
        }
        html.dark .dcms-modal-body {
            background-color: #111a2e !important;
            color: #cbd5e1 !important;
        }
        html.dark .dcms-modal-foot {
            background-color: #0f1729 !important;
            border-top-color: #26344d !important;
        }

        /* ── Login SSO Keycloak ── */
        .dcms-sso-divider {
            display: flex; align-items: center; gap: .75rem; margin: 1.5rem 0 1.25rem;
            font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: .08em;
        }
        .dcms-sso-divider::before, .dcms-sso-divider::after { content: ''; flex: 1; height: 1px; background: #e2e8f0; }
        html.dark .dcms-sso-divider { color: #64748b !important; }
        html.dark .dcms-sso-divider::before, html.dark .dcms-sso-divider::after { background: #26344d !important; }

        .dcms-sso-btn {
            display: flex; align-items: center; justify-content: center; gap: .625rem;
            width: 100%; padding: .8rem 1rem; border-radius: .5rem;
            background: #fff; color: #0B2545; border: 1.5px solid #cbd5e1;
            font-weight: 700; font-size: .875rem; letter-spacing: .01em;
            transition: border-color .2s, background-color .2s, transform .1s;
        }
        .dcms-sso-btn:hover { border-color: #0B2545; background: #f8fafc; }
        .dcms-sso-btn:active { transform: scale(.98); }
        .dcms-sso-btn:focus-visible { outline: 4px solid #bfdbfe; outline-offset: 2px; }
        .dcms-sso-btn svg { width: 1.125rem; height: 1.125rem; }

        html.dark .dcms-sso-btn {
            background: #152039 !important;
            color: #f1f5f9 !important;
            border-color: #26344d !important;
        }
        html.dark .dcms-sso-btn:hover {
            background: #1c2943 !important;
            border-color: #3b82f6 !important;
        }
        .dcms-sso-hint { margin-top: .75rem; font-size: 12px; line-height: 1.6; color: #64748b; text-align: center; }
        html.dark .dcms-sso-hint { color: #94a3b8 !important; }
        .dcms-sso-guide-link { display: inline; font-weight: 700; color: #2563eb; }
        html.dark .dcms-sso-guide-link { color: #60a5fa !important; }
        .dcms-sso-guide-link:hover { text-decoration: underline; }
        .dcms-sso-steps { counter-reset: step; list-style: none; padding: 0; margin: 0; display: grid; gap: .75rem; }
        .dcms-sso-steps li { counter-increment: step; position: relative; padding-left: 2.25rem; }
        .dcms-sso-steps li::before {
            content: counter(step); position: absolute; left: 0; top: -.05rem;
            width: 1.5rem; height: 1.5rem; border-radius: 9999px; background: #eff6ff; color: #2563eb;
            font-size: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center;
        }
        html.dark .dcms-sso-steps li::before {
            background: rgba(37, 99, 235, 0.2) !important;
            color: #93c5fd !important;
        }
        .dcms-sso-alert {
            padding: .75rem 1rem; border-radius: .5rem; border: 1px solid #fecaca;
            background: #fef2f2; color: #b91c1c; font-size: 13px; font-weight: 500;
        }
        html.dark .dcms-sso-alert {
            background: rgba(185, 28, 28, 0.15) !important;
            border-color: rgba(239, 68, 68, 0.3) !important;
            color: #fca5a5 !important;
        }

        @keyframes fade-in {
            from { opacity: 0; transform: translateY(15px) }
            to { opacity: 1; transform: translateY(0) }
        }

        .animate-fade-in {
            animation: fade-in 0.7s ease-out forwards;
        }

        body {
            margin: 0;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</div>
