<div class="font-sans" x-data="{ showTutorial: false }">

    {{-- ─── MOBILE VIEW (< 768px) ─── --}}
    <div class="md:hidden flex flex-col min-h-[100svh] bg-[#f8fafc] pb-8 fixed inset-0 overflow-y-auto overflow-x-hidden z-50">
        {{-- Header Decorative Mobile --}}
        <div class="w-full bg-gradient-to-br from-blue-600 to-blue-800 pt-12 pb-24 px-6 relative rounded-b-[2.5rem] shadow-lg shrink-0">
            <div class="absolute inset-0 overflow-hidden rounded-b-[2.5rem]">
                <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
                <div class="absolute bottom-0 -left-10 w-32 h-32 bg-white/10 rounded-full blur-xl"></div>
            </div>
            
            <div class="relative z-10 flex flex-col items-center text-center">
                <img src="{{ asset('images/logo.png') }}?v={{ @filemtime(public_path('images/logo.png')) ?: 1 }}" alt="Logo" class="h-12 w-auto mb-4 filter drop-shadow-md">
                <h1 class="text-2xl font-bold text-white mb-1 tracking-tight">Buat Akun</h1>
                <p class="text-blue-100 text-[11px] uppercase tracking-wider font-medium opacity-90">Sistem Manajemen Dokumen</p>
            </div>
        </div>

        {{-- Form Card Mobile --}}
        <div class="px-5 -mt-16 relative z-20">
            <div class="bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.08)] p-6">
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
            <p class="text-[13px] text-slate-500">
                Sudah punya akun? 
                <a href="{{ filament()->getLoginUrl() }}" class="text-blue-600 font-bold hover:underline">Masuk di sini</a>
            </p>
            <button @click="showTutorial = true" class="flex items-center gap-2 px-5 py-2.5 bg-white border border-blue-100 text-blue-600 rounded-full text-xs font-bold shadow-sm active:bg-blue-50 transition-colors">
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
    <div class="hidden md:flex min-h-screen items-center justify-center overflow-y-auto p-8 fixed inset-0"
        style="background: #f1f3f7;">
        
        <div class="relative w-full max-w-4xl flex flex-col items-center animate-fade-in mx-auto z-10 py-4">
            {{-- Section Branding --}}
            <div class="w-full text-center mb-6">
                <img src="{{ asset('images/logo.png') }}?v={{ @filemtime(public_path('images/logo.png')) ?: 1 }}" alt="Syifa Global Group Logo"
                    class="h-14 w-auto mx-auto mb-3 object-contain">
                <h1 class="text-2xl font-bold text-[#1e293b] mb-1 tracking-tight">DCMS</h1>
                <p class="text-[13px] text-[#64748b] font-medium uppercase tracking-wider">Buat Akun Baru</p>
            </div>

            {{-- Card Utama --}}
            <div class="w-full bg-white rounded-xl shadow-[0_15px_60px_-10px_rgba(0,0,0,0.08)] border border-[#e2e8f0] p-10 mb-6 z-10 relative">
                <form wire:submit.prevent="register">
                    <div class="fi-form-override">
                        {{ $this->form }}
                    </div>

                    <div class="mt-10 flex flex-row items-center justify-between gap-6 border-t border-gray-100 pt-8">
                        <div class="w-1/2 flex flex-col items-start gap-3">
                            <p class="text-[13px] text-[#64748b]">
                                Sudah punya akun?
                                <a href="{{ filament()->getLoginUrl() }}" class="text-[#2563eb] font-bold hover:underline">Masuk</a>
                            </p>
                            
                            <button @click="showTutorial = true" type="button"
                                class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-800 transition-colors group text-[13px] font-bold">
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
            
            {{-- Modal Box --}}
            <div @click.away="showTutorial = false" 
                class="bg-white w-full md:max-w-4xl rounded-t-[2rem] md:rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh] md:max-h-[90vh]">
                
                {{-- Mobile Drag Indicator --}}
                <div class="md:hidden flex justify-center pt-4 pb-2 bg-white">
                    <div class="w-12 h-1.5 bg-gray-200 rounded-full"></div>
                </div>

                <div class="px-6 py-4 md:border-b md:border-gray-100 flex justify-between items-center bg-white md:bg-gray-50/50">
                    <h3 class="text-base md:text-lg font-bold text-gray-800 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 md:w-6 md:h-6 text-blue-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                        </svg>
                        <span>Panduan <span class="hidden md:inline">Halaman Registrasi</span></span>
                    </h3>
                    <button @click="showTutorial = false" class="text-gray-400 hover:text-gray-600 p-2 rounded-full hover:bg-gray-100 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 md:w-6 md:h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <div class="p-4 md:bg-gray-50 overflow-y-auto flex-1">
                    <img src="{{ asset('images/daftar.jpg') }}" alt="Panduan Registrasi" class="w-full h-auto rounded-xl shadow-sm border border-gray-200">
                </div>
                
                <div class="px-6 py-4 md:border-t md:border-gray-100 text-right bg-white">
                    <button @click="showTutorial = false" class="w-full md:w-auto px-6 py-3 md:py-2 bg-gray-800 text-white rounded-xl md:rounded-lg font-bold text-[13px] md:text-sm hover:bg-gray-900 transition-colors">Tutup Panduan</button>
                </div>
            </div>
        </div>
    </template>

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
        .fi-form-override .fi-input-wrp {
            background-color: #f8fafc !important; border: 1px solid #e2e8f0 !important; border-radius: 8px !important; padding: 2px 4px !important;
        }
        .fi-form-override .fi-input-wrp:focus-within {
            border-color: #2563eb !important; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1) !important;
        }
        .fi-form-override .fi-input {
            color: #1e293b !important; font-size: 0.95rem !important; padding: 0.75rem !important;
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
        .fi-form-override-mobile .fi-input-wrp {
            background-color: #f8fafc !important; border: 1px solid #e2e8f0 !important; border-radius: 12px !important; overflow: hidden !important;
        }
        .fi-form-override-mobile .fi-input-wrp:focus-within {
            border-color: #2563eb !important; background-color: #ffffff !important; box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1) !important;
        }
        .fi-form-override-mobile .fi-input {
            color: #0f172a !important; font-size: 0.9rem !important; padding: 0.75rem 0.875rem !important;
        }
        
        /* Select & Choices mobile */
        .fi-form-override-mobile .choices__inner {
            background-color: transparent !important; border: none !important; padding: 0.5rem 0.875rem !important; font-size: 0.9rem !important;
        }

        /* Reset body */
        body { margin: 0; }
        button { outline: none !important; }
    </style>
</div>