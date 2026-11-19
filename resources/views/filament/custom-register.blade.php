<div class="font-sans" x-data="{ showTutorial: false }">

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
            <svg class="w-5 h-5 hidden dark:block text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
            </svg>
            <svg class="w-5 h-5 block dark:hidden text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
            </svg>
        </button>
    </div>

    {{-- ─── MOBILE VIEW (< 768px) ─── --}}
    <div class="md:hidden flex flex-col min-h-[100svh] dcms-reg-mobile-bg pb-8 fixed inset-0 overflow-y-auto overflow-x-hidden z-50">
        {{-- Header Decorative Mobile --}}
        <div class="w-full bg-gradient-to-br from-blue-600 to-blue-800 pt-12 pb-24 px-6 relative rounded-b-[2.5rem] shadow-lg shrink-0">
            <div class="absolute inset-0 overflow-hidden rounded-b-[2.5rem]">
                <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
                <div class="absolute bottom-0 -left-10 w-32 h-32 bg-white/10 rounded-full blur-xl"></div>
            </div>
            
            <div class="relative z-10 flex flex-col items-center text-center">
                <div class="dcms-logo-wrapper inline-flex items-center justify-center p-2 rounded-xl mb-3">
                    <img src="{{ \App\Support\Branding::logoUrl() }}" alt="Logo" class="h-10 w-auto object-contain">
                </div>
                <h1 class="text-2xl font-bold text-white mb-1 tracking-tight">Buat Akun</h1>
                <p class="text-blue-100 text-[11px] uppercase tracking-wider font-medium opacity-90">Sistem Manajemen Dokumen</p>
            </div>
        </div>

        {{-- Form Card Mobile --}}
        <div class="px-5 -mt-16 relative z-20">
            <div class="dcms-login-card rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.08)] p-6">
                <form wire:submit.prevent="register">
                    <div class="fi-form-override-mobile space-y-4">
                        {{ $this->form }}
                    </div>

                    <div class="mt-8">
                        <button type="submit" class="w-full py-3.5 bg-blue-600 text-white rounded-xl font-bold text-[13px] tracking-wide shadow-md shadow-blue-500/30 active:scale-95 transition-all flex justify-center items-center gap-2">
                            Daftar Sekarang
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Mobile Footer Links --}}
        <div class="mt-8 px-6 flex flex-col items-center gap-5 relative z-20">
            <p class="text-[13px] dcms-brand-sub">
                Sudah punya akun? 
                <a href="{{ filament()->getLoginUrl() }}" class="text-blue-600 dark:text-blue-400 font-bold hover:underline">Masuk di sini</a>
            </p>
            <button @click="showTutorial = true" class="dcms-panduan-btn flex items-center gap-2 px-5 py-2.5 rounded-full text-xs font-bold shadow-sm transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Panduan Pendaftaran
            </button>
        </div>
        <div class="mt-8 text-center text-[10px] text-slate-400 font-medium">
            &copy; {{ date('Y') }} Syifa Global Group
        </div>
    </div>

    {{-- ─── DESKTOP VIEW (>= 768px) ─── --}}
    <div class="dcms-login-screen hidden md:flex min-h-screen items-center justify-center overflow-y-auto p-8 fixed inset-0">
        
        <div class="relative w-full max-w-4xl flex flex-col items-center animate-fade-in mx-auto z-10 py-4">
            {{-- Section Branding --}}
            <div class="w-full text-center mb-6">
                <div class="dcms-logo-wrapper inline-flex items-center justify-center p-2.5 rounded-2xl mb-2">
                    <img src="{{ \App\Support\Branding::logoUrl() }}" alt="Syifa Global Group Logo"
                        class="h-12 w-auto object-contain">
                </div>
                <h1 class="dcms-login-card-title text-2xl font-bold mb-1 tracking-tight">DCMS</h1>
                <p class="dcms-brand-sub text-[13px] font-medium uppercase tracking-wider">Buat Akun Baru</p>
            </div>

            {{-- Card Utama --}}
            <div class="dcms-login-card w-full rounded-xl shadow-[0_15px_60px_-10px_rgba(0,0,0,0.08)] p-10 mb-6 z-10 relative">
                <form wire:submit.prevent="register">
                    <div class="fi-form-override">
                        {{ $this->form }}
                    </div>

                    <div class="dcms-login-divider mt-10 flex flex-row items-center justify-between gap-6 pt-8">
                        <div class="w-1/2 flex flex-col items-start gap-3">
                            <p class="dcms-login-foot-text text-[13px]">
                                Sudah punya akun?
                                <a href="{{ filament()->getLoginUrl() }}" class="dcms-login-foot-link font-bold hover:underline">Masuk</a>
                            </p>
                            
                            <button @click="showTutorial = true" type="button"
                                class="inline-flex items-center gap-2 text-blue-600 dark:text-blue-400 hover:text-blue-800 transition-colors group text-[13px] font-bold">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 group-hover:scale-110 transition-transform">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                                </svg>
                                Panduan Pendaftaran
                            </button>
                        </div>
                        <div class="w-1/2">
                            <button type="submit"
                                class="w-full py-4 bg-[#2563eb] text-white rounded-lg font-bold text-base transition-colors duration-200 hover:bg-[#1d4ed8] active:scale-[0.98] focus:outline-none focus:ring-4 focus:ring-blue-200 tracking-wide uppercase">
                                Daftar Sekarang
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="w-full text-center text-[12px] text-[#94a3b8] font-medium tracking-wide">
                &copy; {{ date('Y') }} Syifa Global Group.
            </div>
        </div>
    </div>

    {{-- Modal Tutorial (Desktop & Mobile) --}}
    <template x-teleport="body">
        <div x-show="showTutorial" 
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[100] flex items-end md:items-center justify-center p-0 md:p-4 bg-gray-900/60 backdrop-blur-sm"
            @keydown.escape.window="showTutorial = false"
            style="display: none;">
            
            <div @click.away="showTutorial = false" 
                class="dcms-modal-card w-full md:max-w-4xl rounded-t-[2rem] md:rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh] md:max-h-[90vh]">
                
                {{-- Handle drag bar untuk tampilan mobile --}}
                <div class="md:hidden flex justify-center pt-4 pb-2 dcms-modal-head">
                    <div class="w-12 h-1.5 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                </div>

                <div class="dcms-modal-head px-6 py-4 md:border-b flex justify-between items-center">
                    <h3 class="text-base md:text-lg font-bold flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 md:w-6 md:h-6 text-blue-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                        </svg>
                        Panduan Pendaftaran Akun
                    </h3>
                    <button @click="showTutorial = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-2 rounded-full hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 md:w-6 md:h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="dcms-modal-body p-4 overflow-y-auto flex-1">
                    <img src="{{ asset('images/daftar.jpg') }}" alt="Panduan Pendaftaran" class="w-full h-auto rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                </div>
                <div class="dcms-modal-foot px-6 py-4 md:border-t text-right">
                    <button @click="showTutorial = false" class="w-full md:w-auto px-6 py-3 md:py-2 bg-gray-800 text-white rounded-xl md:rounded-lg font-bold text-[13px] md:text-sm hover:bg-gray-900 transition-colors">Tutup Panduan</button>
                </div>
            </div>
        </div>
    </template>

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
        /* ─── THEME & BACKGROUNDS ─── */
        .dcms-login-screen { background-color: #f1f3f7; }
        html.dark .dcms-login-screen {
            background: #070d1a !important;
            background: linear-gradient(160deg, #070d1a 0%, #0b1530 55%, #0e1b3d 100%) !important;
        }

        .dcms-reg-mobile-bg { background-color: #f8fafc; }
        html.dark .dcms-reg-mobile-bg { background-color: #070d1a !important; }

        .dcms-login-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
        }
        html.dark .dcms-login-card {
            background-color: #111a2e !important;
            border: 1px solid #26344d !important;
            box-shadow: 0 1px 0 rgba(255, 255, 255, 0.04) inset, 0 25px 60px -15px rgba(0, 0, 0, 0.75) !important;
        }

        .dcms-login-card-title { color: #1e293b; }
        html.dark .dcms-login-card-title { color: #f8fafc !important; }
        .dcms-brand-sub { color: #64748b; }
        html.dark .dcms-brand-sub { color: #94a3b8 !important; }

        .dcms-logo-wrapper { background: transparent; }
        html.dark .dcms-logo-wrapper {
            background: rgba(255, 255, 255, 0.96) !important;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.45) !important;
        }

        .dcms-login-divider { border-top: 1px solid #f1f5f9; }
        html.dark .dcms-login-divider { border-top-color: #1e293b !important; }
        .dcms-login-foot-text { color: #64748b; }
        html.dark .dcms-login-foot-text { color: #94a3b8 !important; }
        .dcms-login-foot-link { color: #2563eb; }
        html.dark .dcms-login-foot-link { color: #60a5fa !important; }

        .dcms-theme-toggle-btn {
            background-color: rgba(255, 255, 255, 0.9);
            border: 1px solid #cbd5e1;
            color: #475569;
        }
        .dcms-theme-toggle-btn:hover { background-color: #ffffff; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        html.dark .dcms-theme-toggle-btn {
            background-color: #111a2e !important;
            border-color: #26344d !important;
            color: #fcd34d !important;
        }

        .dcms-panduan-btn { background-color: #ffffff; border: 1px solid #dbeafe; color: #2563eb; }
        html.dark .dcms-panduan-btn {
            background-color: #111a2e !important;
            border-color: #26344d !important;
            color: #93c5fd !important;
        }

        .dcms-modal-card { background-color: #ffffff; }
        .dcms-modal-head { background-color: #f8fafc; border-bottom: 1px solid #f1f5f9; color: #1e293b; }
        .dcms-modal-body { background-color: #ffffff; }
        .dcms-modal-foot { background-color: #ffffff; border-top: 1px solid #f1f5f9; }
        html.dark .dcms-modal-card { background-color: #111a2e !important; border: 1px solid #26344d !important; }
        html.dark .dcms-modal-head { background-color: #0f1729 !important; border-color: #26344d !important; color: #f8fafc !important; }
        html.dark .dcms-modal-body { background-color: #111a2e !important; }
        html.dark .dcms-modal-foot { background-color: #0f1729 !important; border-color: #26344d !important; }

        @keyframes fade-in { from { opacity: 0; transform: translateY(15px) } to { opacity: 1; transform: translateY(0) } }
        .animate-fade-in { animation: fade-in 0.7s ease-out forwards; }

        /* ─── DESKTOP FORM OVERRIDES ─── */
        .fi-form-override .fi-grid {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 1.25rem !important;
        }
        .fi-form-override .fi-fo-field-wrp,
        .fi-form-override .fi-section-content { border: none !important; }
        .fi-form-override label {
            color: #475569 !important; font-size: 0.75rem !important; font-weight: 700 !important;
            text-transform: uppercase !important; letter-spacing: 0.05em !important; margin-bottom: 0.3rem !important; display: block;
        }
        html.dark .fi-form-override label { color: #94a3b8 !important; }

        .fi-form-override .fi-input-wrp {
            background-color: #f8fafc !important; border: 1px solid #e2e8f0 !important; border-radius: 8px !important; padding: 2px 4px !important;
        }
        html.dark .fi-form-override .fi-input-wrp {
            background-color: #0f1729 !important; border-color: #26344d !important;
        }
        html.dark .fi-form-override .fi-input-wrp:focus-within {
            border-color: #3b82f6 !important; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25) !important;
        }
        .fi-form-override .fi-input {
            color: #1e293b !important; font-size: 0.95rem !important; padding: 0.75rem !important;
        }
        html.dark .fi-form-override .fi-input {
            color: #f1f5f9 !important; background-color: transparent !important;
        }

        /* ─── MOBILE FORM OVERRIDES ─── */
        .fi-form-override-mobile .fi-grid {
            display: flex !important; flex-direction: column !important; gap: 1rem !important;
        }
        .fi-form-override-mobile .fi-fo-field-wrp,
        .fi-form-override-mobile .fi-section-content { border: none !important; padding: 0 !important; }
        
        .fi-form-override-mobile label {
            color: #475569 !important; font-size: 0.7rem !important; font-weight: 700 !important;
            text-transform: uppercase !important; letter-spacing: 0.05em !important; margin-bottom: 0.3rem !important; display: block;
        }
        html.dark .fi-form-override-mobile label { color: #94a3b8 !important; }

        .fi-form-override-mobile .fi-input-wrp {
            background-color: #f8fafc !important; border: 1px solid #e2e8f0 !important; border-radius: 12px !important; overflow: hidden !important;
        }
        html.dark .fi-form-override-mobile .fi-input-wrp {
            background-color: #0f1729 !important; border-color: #26344d !important;
        }
        .fi-form-override-mobile .fi-input {
            color: #0f172a !important; font-size: 0.9rem !important; padding: 0.75rem 0.875rem !important;
        }
        html.dark .fi-form-override-mobile .fi-input {
            color: #f1f5f9 !important; background-color: transparent !important;
        }

        body { margin: 0; }
        button { outline: none !important; }
    </style>
</div>
