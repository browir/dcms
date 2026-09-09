<div class="fixed inset-0 min-h-screen flex items-center justify-center font-sans overflow-y-auto p-4 md:p-10"
    style="background: #f1f3f7;" x-data="{ showTutorial: false }">
    
    {{-- Container Utama --}}
    <div class="relative w-full max-w-[440px] flex flex-col items-center animate-fade-in mx-auto z-10">
        
        {{-- Section Branding --}}
        <div class="w-full text-center mb-8">
            <img src="{{ asset('images/logo.png') }}?v={{ @filemtime(public_path('images/logo.png')) ?: 1 }}" alt="Logo DCMS"
                class="h-36 w-auto mx-auto mb-4 object-contain">

            <p class="text-sm text-[#64748b] leading-relaxed max-w-sm mx-auto font-medium">
                Manajemen Rapat dan Dokumen
            </p>
        </div>

        {{-- Card Utama --}}
        <div class="w-full bg-white rounded-xl shadow-[0_15px_60px_-10px_rgba(0,0,0,0.08)] border border-[#e2e8f0] p-10 md:p-12 mb-8 z-10 relative">
            <form
                wire:submit.prevent="authenticate"
                x-ref="loginForm"
                x-on:keydown.enter="if (! ['BUTTON', 'A', 'TEXTAREA'].includes($event.target.tagName) && $event.target.type !== 'checkbox') { $event.preventDefault(); $refs.loginForm.requestSubmit() }"
                class="space-y-5"
            >
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

            <div class="mt-8 text-center border-t border-gray-100 pt-6">
                <p class="text-[13px] text-[#64748b] mb-4">
                    Belum punya akun?
                    <a href="{{ filament()->getRegistrationUrl() }}"
                        class="text-[#2563eb] font-bold hover:underline">Buat Akun</a>
                </p>

                {{-- Tombol Panduan dengan Heroicon Info --}}
                <button @click="showTutorial = true" 
                    type="button"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-700 rounded-full text-[12px] font-bold hover:bg-blue-100 transition-all group border border-blue-100">
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
                class="w-full flex items-center justify-center gap-2.5 py-3.5 px-4 bg-white border border-[#cbd5e1] text-[#0B2545] rounded-xl font-bold text-[13px] shadow-sm hover:border-[#2563eb] hover:text-[#2563eb] hover:shadow-md transition-all active:scale-[0.98]">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                </svg>
                <span x-text="isIOS ? 'Pasang Aplikasi ke Layar Utama' : 'Install Aplikasi DCMS'"></span>
            </button>
            <p class="mt-2 text-center text-[11px] text-[#94a3b8] font-medium">
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
                        class="bg-white rounded-2xl shadow-2xl max-w-sm w-full overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                            <h3 class="text-base font-bold text-gray-800">Pasang Aplikasi DCMS</h3>
                            <button @click="showHelp = false" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="p-6 text-[13px] text-gray-600 leading-relaxed">
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
                        <div class="px-6 py-4 border-t border-gray-100 text-right">
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
                class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full overflow-hidden animate-fade-in">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-blue-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                        </svg>
                        Panduan Halaman Masuk
                    </h3>
                    <button @click="showTutorial = false" class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="p-4 bg-gray-50 overflow-y-auto max-h-[80vh]">
                    <img src="{{ asset('images/masuk.jpg') }}" alt="Panduan Masuk" class="w-full h-auto rounded-xl shadow-sm border border-gray-200">
                </div>
                <div class="px-6 py-4 border-t border-gray-100 text-right">
                    <button @click="showTutorial = false" class="px-6 py-2 bg-gray-800 text-white rounded-lg font-bold hover:bg-gray-900 transition-colors">Tutup</button>
                </div>
            </div>
        </div>
    </template>

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
            theme: {
                extend: {
                    borderRadius: { 'xl': '18px' },
                }
            }
        }
    </script>

    <style>
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(15px) }
            to { opacity: 1; transform: translateY(0) }
        }

        .animate-fade-in {
            animation: fade-in 0.7s ease-out forwards;
        }

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

        .fi-form-override .fi-input-wrp {
            background-color: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 8px !important;
            padding: 2px 4px !important;
        }

        .fi-form-override .fi-input {
            color: #1e293b !important;
            font-size: 0.95rem !important;
            padding: 0.75rem !important;
        }

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

        body {
            background-color: #f1f3f7 !important;
            margin: 0;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</div>
