<x-filament-panels::page>
    <style>
        .sig-page {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            font-family: inherit;
        }
        /* Pembatas ketat ukuran SVG agar tidak membengkak di browser */
        .sig-page svg,
        .sig-card svg,
        .sig-title svg,
        .sig-icon-sm svg,
        .sig-icon-md svg,
        .sig-icon-lg svg {
            width: 1.25rem !important;
            height: 1.25rem !important;
            max-width: 1.25rem !important;
            max-height: 1.25rem !important;
            display: inline-block !important;
            vertical-align: middle !important;
            flex-shrink: 0 !important;
        }
        .sig-page .sig-icon-sm svg, .sig-icon-sm svg { width: 1rem !important; height: 1rem !important; max-width: 1rem !important; max-height: 1rem !important; }
        .sig-page .sig-icon-md svg, .sig-icon-md svg { width: 1.25rem !important; height: 1.25rem !important; max-width: 1.25rem !important; max-height: 1.25rem !important; }
        .sig-page .sig-icon-lg svg, .sig-icon-lg svg { width: 2rem !important; height: 2rem !important; max-width: 2rem !important; max-height: 2rem !important; }

        .sig-grid-main {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
            align-items: start;
        }
        @media (min-width: 1024px) {
            .sig-grid-main {
                grid-template-columns: 7fr 5fr;
            }
        }

        .sig-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.25rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .dark .sig-card {
            background: #1e293b;
            border-color: #334155;
            color: #f1f5f9;
        }

        .sig-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 0.875rem;
            border-bottom: 1px solid #f1f5f9;
            margin-bottom: 1rem;
        }
        .dark .sig-card-header {
            border-bottom-color: #334155;
        }

        .sig-title {
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #0f172a;
        }
        .dark .sig-title { color: #f8fafc; }

        .sig-desc {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.125rem;
        }
        .dark .sig-desc { color: #94a3b8; }

        /* Toolbar Canvas */
        .sig-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            margin-bottom: 0.75rem;
            font-size: 0.75rem;
        }
        .dark .sig-toolbar {
            background: #0f172a;
            border-color: #334155;
        }

        .sig-canvas-wrap {
            position: relative;
            width: 100%;
            height: 260px;
            background: #ffffff;
            border: 2px dashed #cbd5e1;
            border-radius: 0.75rem;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            touch-action: none !important;
            user-select: none;
            -webkit-user-select: none;
        }
        @media (max-width: 640px) {
            .sig-canvas-wrap {
                height: 290px;
            }
        }
        .sig-canvas-wrap canvas {
            display: block;
            width: 100%;
            height: 100%;
            cursor: crosshair;
            touch-action: none !important;
        }

        /* Tombol Mode Layar Penuh untuk Mobile */
        .sig-btn-fullscreen {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.5rem 0.875rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: #1d4ed8;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.15s;
            width: 100%;
            margin-bottom: 0.75rem;
            text-align: center;
        }
        .sig-btn-fullscreen:hover {
            background: #dbeafe;
            border-color: #93c5fd;
        }
        .dark .sig-btn-fullscreen {
            background: rgba(30, 58, 138, 0.3);
            border-color: #1e40af;
            color: #93c5fd;
        }

        /* Modal Fullscreen Canvas untuk HP */
        .sig-modal-fullscreen {
            position: fixed;
            inset: 0;
            z-index: 999999;
            background: #0f172a;
            display: flex;
            flex-direction: column;
            padding: 0.75rem;
            box-sizing: border-box;
            touch-action: none !important;
        }
        .sig-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.5rem 0.75rem;
            background: #1e293b;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .sig-modal-canvas-wrap {
            position: relative;
            flex: 1;
            width: 100%;
            min-height: 200px;
            background: #ffffff;
            border-radius: 0.75rem;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            touch-action: none !important;
        }
        .sig-modal-canvas-wrap canvas {
            display: block;
            width: 100%;
            height: 100%;
            cursor: crosshair;
            touch-action: none !important;
        }
        .sig-modal-footer {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            background: #1e293b;
            border-radius: 0.5rem;
            margin-top: 0.5rem;
            font-size: 0.75rem;
        }

        /* Gallery Grid */
        .sig-gallery {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        @media (min-width: 640px) {
            .sig-gallery { grid-template-columns: repeat(2, 1fr); }
        }
        @media (min-width: 1024px) {
            .sig-gallery { grid-template-columns: repeat(3, 1fr); }
        }

        .sig-item-card {
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1rem;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .dark .sig-item-card {
            background: #0f172a;
            border-color: #334155;
        }
        .sig-item-card.is-active {
            border-color: #10b981;
            background: rgba(16, 185, 129, 0.04);
        }

        .sig-img-box {
            width: 100%;
            height: 90px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 0.5rem;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
            background-size: 8px 8px;
        }
        .sig-img-box img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
        }
    </style>

    <div class="sig-page" 
         x-data="signaturePadHandler()" 
         x-init="initCanvas()" 
         @signature-saved.window="clearCanvas(); mode = 'draw';">

        {{-- Kartu Utama: Canvas & Formulir Pejabat --}}
        <div class="sig-grid-main">
            {{-- Kolom Kiri: Canvas Sketch Pad --}}
            <div class="sig-card">
                <div class="sig-card-header">
                    <div>
                        <div class="sig-title">
                            <span class="sig-icon-md text-primary-500">
                                <x-heroicon-o-pencil-square class="w-5 h-5 text-primary-500" style="width: 1.25rem; height: 1.25rem; max-width: 1.25rem; max-height: 1.25rem;" />
                            </span>
                            Gores Tanda Tangan Digital Saya
                        </div>
                        <div class="sig-desc">
                            Tanda tangan dibuat dan disimpan khusus untuk akun Anda sendiri.
                        </div>
                    </div>

                    {{-- Switcher Mode (Gores / Unggah) --}}
                    <div style="display: inline-flex; background: #f1f5f9; padding: 2px; border-radius: 6px;">
                        <button type="button" 
                                @click="mode = 'draw'"
                                :style="mode === 'draw' ? 'background: #ffffff; color: #2563eb; font-weight: 600; box-shadow: 0 1px 2px rgba(0,0,0,0.06);' : 'background: transparent; color: #64748b;'"
                                style="padding: 4px 10px; font-size: 11px; border: none; border-radius: 5px; cursor: pointer; transition: all 0.15s;">
                            Sketch
                        </button>
                        <button type="button" 
                                @click="mode = 'upload'"
                                :style="mode === 'upload' ? 'background: #ffffff; color: #2563eb; font-weight: 600; box-shadow: 0 1px 2px rgba(0,0,0,0.06);' : 'background: transparent; color: #64748b;'"
                                style="padding: 4px 10px; font-size: 11px; border: none; border-radius: 5px; cursor: pointer; transition: all 0.15s;">
                            Unggah Gambar
                        </button>
                    </div>
                </div>

                {{-- Mode 1: Gores Canvas --}}
                <div x-show="mode === 'draw'">
                    {{-- Tombol Buka Mode Layar Penuh (Sangat membantu untuk layar sentuh HP / Tablet) --}}
                    <button type="button" 
                            @click="openFullscreen()" 
                            class="sig-btn-fullscreen">
                        <x-heroicon-o-arrows-pointing-out style="width: 1.1rem; height: 1.1rem;" />
                        <span>Mode Layar Penuh (Leluasa untuk HP / Layar Sentuh)</span>
                    </button>

                    <div class="sig-toolbar">
                        {{-- Warna Pena --}}
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="color: #64748b; font-weight: 500;">Tinta:</span>
                            <button type="button" 
                                    @click="setColor('#111827')"
                                    :style="currentColor === '#111827' ? 'outline: 2px solid #2563eb; outline-offset: 1px;' : ''"
                                    style="width: 22px; height: 22px; border-radius: 50%; background: #111827; border: 1px solid #94a3b8; cursor: pointer;"
                                    title="Hitam"></button>
                            <button type="button" 
                                    @click="setColor('#1e3a8a')"
                                    :style="currentColor === '#1e3a8a' ? 'outline: 2px solid #2563eb; outline-offset: 1px;' : ''"
                                    style="width: 22px; height: 22px; border-radius: 50%; background: #1e3a8a; border: 1px solid #94a3b8; cursor: pointer;"
                                    title="Biru Dokumen"></button>
                        </div>

                        {{-- Ketebalan Garis --}}
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <span style="color: #64748b; font-weight: 500;">Tebal:</span>
                            <button type="button" 
                                    @click="setLineWidth(1.5)"
                                    :style="currentWidth === 1.5 ? 'background: #dbeafe; color: #1d4ed8; font-weight: bold; border-color: #3b82f6;' : 'background: #ffffff; color: #475569; border-color: #cbd5e1;'"
                                    style="padding: 2px 8px; font-size: 11px; border-radius: 4px; border: 1px solid; cursor: pointer;">
                                Halus
                            </button>
                            <button type="button" 
                                    @click="setLineWidth(2.5)"
                                    :style="currentWidth === 2.5 ? 'background: #dbeafe; color: #1d4ed8; font-weight: bold; border-color: #3b82f6;' : 'background: #ffffff; color: #475569; border-color: #cbd5e1;'"
                                    style="padding: 2px 8px; font-size: 11px; border-radius: 4px; border: 1px solid; cursor: pointer;">
                                Sedang
                            </button>
                            <button type="button" 
                                    @click="setLineWidth(4)"
                                    :style="currentWidth === 4 ? 'background: #dbeafe; color: #1d4ed8; font-weight: bold; border-color: #3b82f6;' : 'background: #ffffff; color: #475569; border-color: #cbd5e1;'"
                                    style="padding: 2px 8px; font-size: 11px; border-radius: 4px; border: 1px solid; cursor: pointer;">
                                Tebal
                            </button>
                        </div>

                        {{-- Undo & Clear --}}
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <button type="button" 
                                    @click="undo()"
                                    :disabled="history.length === 0"
                                    class="sig-icon-sm"
                                    style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; font-size: 11px; border-radius: 4px; border: 1px solid #cbd5e1; background: #ffffff; color: #334155; cursor: pointer;">
                                <x-heroicon-m-arrow-uturn-left class="w-4 h-4" style="width: 1rem; height: 1rem;" />
                                Undo
                            </button>
                            <button type="button" 
                                    @click="clearCanvas()"
                                    class="sig-icon-sm"
                                    style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; font-size: 11px; border-radius: 4px; border: 1px solid #fca5a5; background: #fef2f2; color: #b91c1c; cursor: pointer;">
                                <x-heroicon-m-trash class="w-4 h-4" style="width: 1rem; height: 1rem;" />
                                Bersihkan
                            </button>
                        </div>
                    </div>

                    {{-- Canvas Drawing Area --}}
                    <div class="sig-canvas-wrap">
                        <canvas x-ref="canvas"
                                @pointerdown="startDrawing($event)"
                                @pointermove="draw($event)"
                                @pointerup="stopDrawing($event)"
                                @pointercancel="stopDrawing($event)"></canvas>

                        <div x-show="history.length === 0" 
                             style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; pointer-events: none; color: #94a3b8; user-select: none;">
                            <span class="sig-icon-lg" style="opacity: 0.35; margin-bottom: 4px;">
                                <x-heroicon-o-finger-print class="w-8 h-8" style="width: 2rem; height: 2rem;" />
                            </span>
                            <span style="font-size: 13px; font-weight: 500; opacity: 0.7; text-align: center; padding: 0 1rem;">
                                Goreskan tanda tangan Anda dengan mouse atau jari sentuh
                            </span>
                            <div style="width: 70%; border-bottom: 1px dashed #cbd5e1; margin-top: 24px;"></div>
                        </div>
                    </div>
                </div>

                {{-- Mode 2: Unggah File Gambar --}}
                <div x-show="mode === 'upload'" style="margin-top: 0.5rem;">
                    <div style="border: 2px dashed #cbd5e1; border-radius: 0.75rem; padding: 2.5rem 1.5rem; text-align: center; background: #f8fafc;">
                        <span class="sig-icon-lg" style="display: block; margin: 0 auto 0.75rem; color: #94a3b8;">
                            <x-heroicon-o-arrow-up-tray class="w-8 h-8" style="width: 2rem; height: 2rem;" />
                        </span>
                        <div style="font-size: 0.875rem; color: #475569;">
                            <label for="signature-upload-input" style="color: #2563eb; font-weight: 600; cursor: pointer;">
                                <span>Pilih berkas gambar tanda tangan</span>
                                <input id="signature-upload-input" wire:model="uploaded_file" type="file" accept="image/png,image/jpeg,image/webp" style="display: none;">
                            </label>
                        </div>
                        <p style="font-size: 0.75rem; color: #64748b; margin-top: 0.5rem;">
                            Format disarankan: PNG transparan (maks. 3MB).
                        </p>
                    </div>

                    @if ($uploaded_file)
                        <div style="margin-top: 0.75rem; padding: 0.75rem 1rem; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 0.5rem; display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <img src="{{ $uploaded_file->temporaryUrl() }}" alt="Preview" style="height: 44px; width: auto; object-fit: contain; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 4px; padding: 2px;">
                                <div>
                                    <div style="font-size: 0.75rem; font-weight: 600; color: #065f46;">Berkas Siap Disimpan</div>
                                    <div style="font-size: 0.75rem; color: #64748b;">{{ $uploaded_file->getClientOriginalName() }}</div>
                                </div>
                            </div>
                            <button type="button" wire:click="$set('uploaded_file', null)" style="background: none; border: none; color: #ef4444; font-size: 0.75rem; font-weight: 600; cursor: pointer;">
                                Batal
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Kolom Kanan: Form Pejabat --}}
            <div class="sig-card">
                <div class="sig-card-header">
                    <div>
                        <div class="sig-title">
                            <span class="sig-icon-md text-primary-500">
                                <x-heroicon-o-identification class="w-5 h-5 text-primary-500" style="width: 1.25rem; height: 1.25rem;" />
                            </span>
                            Data Pejabat Penanda Tangan
                        </div>
                        <div class="sig-desc">
                            Identitas ini akan terpasang pada tanda tangan di surat resmi.
                        </div>
                    </div>
                </div>

                <form wire:submit.prevent="saveSignature" class="space-y-4">
                    {{ $this->form }}

                    <div style="padding-top: 1rem; border-top: 1px solid #f1f5f9; margin-top: 1rem;">
                        <x-filament::button type="submit" icon="heroicon-m-check" class="w-full">
                            Simpan Tanda Tangan Saya
                        </x-filament::button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal / Overlay Mode Layar Penuh (Mobile Friendly Canvas) --}}
        <div x-show="isFullscreen" 
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="sig-modal-fullscreen">
            
            {{-- Header Fullscreen Modal --}}
            <div class="sig-modal-header">
                <div>
                    <div style="font-size: 0.875rem; font-weight: 700; color: #f8fafc; display: flex; align-items: center; gap: 0.5rem;">
                        <x-heroicon-o-pencil-square style="width: 1.1rem; height: 1.1rem; color: #60a5fa;" />
                        <span>Mode Layar Penuh Tanda Tangan</span>
                    </div>
                    <div style="font-size: 0.7rem; color: #94a3b8;">
                        💡 Tips: Putar HP ke posisi miring (Landscape) jika membutuhkan area tanda tangan yang lebih luas.
                    </div>
                </div>
                
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <button type="button" 
                            @click="closeFullscreen(false)"
                            style="padding: 0.375rem 0.75rem; font-size: 0.75rem; font-weight: 600; color: #cbd5e1; background: #334155; border: none; border-radius: 0.375rem; cursor: pointer;">
                        Batal
                    </button>
                    <button type="button" 
                            @click="closeFullscreen(true)"
                            style="padding: 0.375rem 0.875rem; font-size: 0.75rem; font-weight: 700; color: #ffffff; background: #059669; border: none; border-radius: 0.375rem; cursor: pointer;">
                        Gunakan Tanda Tangan
                    </button>
                </div>
            </div>

            {{-- Fullscreen Canvas Area --}}
            <div class="sig-modal-canvas-wrap">
                <canvas x-ref="fsCanvas"
                        @pointerdown="startDrawingFs($event)"
                        @pointermove="drawFs($event)"
                        @pointerup="stopDrawingFs($event)"
                        @pointercancel="stopDrawingFs($event)"></canvas>

                <div x-show="fsHistory.length === 0" 
                     style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; pointer-events: none; color: #94a3b8; user-select: none;">
                    <span class="sig-icon-lg" style="opacity: 0.4; margin-bottom: 4px;">
                        <x-heroicon-o-finger-print />
                    </span>
                    <span style="font-size: 14px; font-weight: 600; opacity: 0.75;">Goreskan tanda tangan Anda dengan jari di layar ini</span>
                    <div style="width: 80%; border-bottom: 1px dashed #cbd5e1; margin-top: 24px;"></div>
                </div>
            </div>

            {{-- Toolbar Footer Fullscreen --}}
            <div class="sig-modal-footer">
                {{-- Tinta & Tebal --}}
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="color: #94a3b8; font-weight: 500;">Warna:</span>
                        <button type="button" 
                                @click="setColor('#111827')"
                                :style="currentColor === '#111827' ? 'outline: 2px solid #60a5fa; outline-offset: 1px;' : ''"
                                style="width: 24px; height: 24px; border-radius: 50%; background: #111827; border: 1px solid #94a3b8; cursor: pointer;"></button>
                        <button type="button" 
                                @click="setColor('#1e3a8a')"
                                :style="currentColor === '#1e3a8a' ? 'outline: 2px solid #60a5fa; outline-offset: 1px;' : ''"
                                style="width: 24px; height: 24px; border-radius: 50%; background: #1e3a8a; border: 1px solid #94a3b8; cursor: pointer;"></button>
                    </div>

                    <div style="display: flex; align-items: center; gap: 4px;">
                        <span style="color: #94a3b8; font-weight: 500;">Tebal:</span>
                        <button type="button" 
                                @click="setLineWidth(1.5)"
                                :style="currentWidth === 1.5 ? 'background: #2563eb; color: #fff; font-weight: bold;' : 'background: #334155; color: #cbd5e1;'"
                                style="padding: 3px 8px; font-size: 11px; border-radius: 4px; border: none; cursor: pointer;">
                            Halus
                        </button>
                        <button type="button" 
                                @click="setLineWidth(2.5)"
                                :style="currentWidth === 2.5 ? 'background: #2563eb; color: #fff; font-weight: bold;' : 'background: #334155; color: #cbd5e1;'"
                                style="padding: 3px 8px; font-size: 11px; border-radius: 4px; border: none; cursor: pointer;">
                            Sedang
                        </button>
                        <button type="button" 
                                @click="setLineWidth(4)"
                                :style="currentWidth === 4 ? 'background: #2563eb; color: #fff; font-weight: bold;' : 'background: #334155; color: #cbd5e1;'"
                                style="padding: 3px 8px; font-size: 11px; border-radius: 4px; border: none; cursor: pointer;">
                            Tebal
                        </button>
                    </div>
                </div>

                {{-- Undo & Clear --}}
                <div style="display: flex; align-items: center; gap: 8px;">
                    <button type="button" 
                            @click="undoFs()"
                            :disabled="fsHistory.length === 0"
                            class="sig-icon-sm"
                            style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; font-size: 11px; border-radius: 4px; border: 1px solid #475569; background: #334155; color: #f8fafc; cursor: pointer;">
                        <x-heroicon-m-arrow-uturn-left class="w-4 h-4" style="width: 1rem; height: 1rem;" />
                        Undo
                    </button>
                    <button type="button" 
                            @click="clearFsCanvas()"
                            class="sig-icon-sm"
                            style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; font-size: 11px; border-radius: 4px; border: 1px solid #f87171; background: #7f1d1d; color: #fee2e2; cursor: pointer;">
                        <x-heroicon-m-trash class="w-4 h-4" style="width: 1rem; height: 1rem;" />
                        Bersihkan
                    </button>
                </div>
            </div>
        </div>

        {{-- Kartu Riwayat / Galeri Tanda Tangan Tersimpan --}}
        <div class="sig-card">
            <div class="sig-card-header">
                <div>
                    <div class="sig-title">
                        <span class="sig-icon-md text-primary-500">
                            <x-heroicon-o-queue-list class="w-5 h-5 text-primary-500" style="width: 1.25rem; height: 1.25rem;" />
                        </span>
                        Daftar Tanda Tangan Saya Tersimpan
                    </div>
                    <div class="sig-desc">
                        Hanya akun Anda yang dapat melihat dan membubuhkan tanda tangan ini pada dokumen.
                    </div>
                </div>
                <span style="font-size: 0.75rem; background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 9999px; font-weight: 600;">
                    Total: {{ count($this->signatures) }} tanda tangan milik Anda
                </span>
            </div>

            @if (count($this->signatures) === 0)
                <div style="text-align: center; padding: 2.5rem 1rem;">
                    <span class="sig-icon-lg" style="color: #cbd5e1; display: block; margin: 0 auto 0.5rem;">
                        <x-heroicon-o-document-text class="w-8 h-8" style="width: 2rem; height: 2rem;" />
                    </span>
                    <p style="font-size: 0.875rem; font-weight: 500; color: #64748b;">Belum ada tanda tangan digital yang tersimpan pada akun Anda</p>
                    <p style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">Gunakan canvas di atas untuk membuat dan menyimpan tanda tangan digital pertama Anda.</p>
                </div>
            @else
                <div class="sig-gallery">
                    @foreach ($this->signatures as $sig)
                        <div class="sig-item-card {{ $sig->is_active ? 'is-active' : '' }}">
                            <div>
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                                    @if ($sig->is_active)
                                        <span class="sig-icon-sm" style="display: inline-flex; align-items: center; gap: 4px; font-size: 0.7rem; font-weight: 600; color: #065f46; background: #d1fae5; border: 1px solid #6ee7b7; padding: 2px 8px; border-radius: 9999px;">
                                            <x-heroicon-m-check-circle class="w-3.5 h-3.5" style="width: 0.875rem; height: 0.875rem;" />
                                            Aktif Utama
                                        </span>
                                    @else
                                        <span style="display: inline-flex; font-size: 0.7rem; font-weight: 500; color: #64748b; background: #f1f5f9; padding: 2px 8px; border-radius: 9999px;">
                                            Tersimpan
                                        </span>
                                    @endif

                                    <span style="font-size: 0.7rem; color: #94a3b8;">
                                        {{ $sig->created_at?->translatedFormat('d M Y') }}
                                    </span>
                                </div>

                                {{-- Kotak Gambar Tanda Tangan --}}
                                <div class="sig-img-box">
                                    @if ($sig->signature_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($sig->signature_path))
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($sig->signature_path) }}" 
                                             alt="Tanda Tangan {{ $sig->name }}">
                                    @else
                                        <span style="font-size: 0.75rem; color: #94a3b8; font-style: italic;">File tanda tangan tidak ditemukan</span>
                                    @endif
                                </div>

                                {{-- Identitas Pejabat --}}
                                <div>
                                    <div style="font-size: 0.875rem; font-weight: 700; color: #0f172a;" class="dark:text-white">
                                        {{ $sig->name }}
                                    </div>
                                    <div style="font-size: 0.75rem; font-weight: 600; color: #2563eb;">
                                        {{ $sig->position }}
                                    </div>
                                    @if ($sig->nip)
                                        <div style="font-size: 0.75rem; color: #64748b;">
                                            NIP. {{ $sig->nip }}
                                        </div>
                                    @endif
                                    @if ($sig->company)
                                        <div style="font-size: 0.7rem; color: #94a3b8; margin-top: 2px;">
                                            {{ $sig->company->name }}
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Tombol Aksi --}}
                            <div style="margin-top: 0.875rem; padding-top: 0.625rem; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
                                @if (! $sig->is_active)
                                    <button type="button" 
                                            wire:click="setActiveSignature({{ $sig->id }})"
                                            class="sig-icon-sm"
                                            style="display: inline-flex; align-items: center; gap: 4px; background: none; border: none; font-size: 0.75rem; font-weight: 600; color: #2563eb; cursor: pointer;">
                                        <x-heroicon-m-check class="w-3.5 h-3.5" style="width: 0.875rem; height: 0.875rem;" />
                                        Jadikan Aktif
                                    </button>
                                @else
                                    <span style="font-size: 0.75rem; color: #059669; font-weight: 500;">Sedang Digunakan</span>
                                @endif

                                <button type="button" 
                                        wire:click="deleteSignature({{ $sig->id }})"
                                        wire:confirm="Yakin ingin menghapus tanda tangan Anda ini?"
                                        class="sig-icon-sm"
                                        style="display: inline-flex; align-items: center; gap: 4px; background: none; border: none; font-size: 0.75rem; color: #ef4444; cursor: pointer;">
                                    <x-heroicon-m-trash class="w-3.5 h-3.5" style="width: 0.875rem; height: 0.875rem;" />
                                    Hapus
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Script Alpine.js Canvas --}}
    <script>
        function signaturePadHandler() {
            return {
                mode: 'draw',
                isFullscreen: false,
                isDrawing: false,
                canvas: null,
                ctx: null,
                fsCanvas: null,
                fsCtx: null,
                currentColor: '#111827',
                currentWidth: 2.5,
                history: [],
                fsHistory: [],
                lastX: 0,
                lastY: 0,
                
                initCanvas() {
                    this.$nextTick(() => {
                        this.canvas = this.$refs.canvas;
                        if (!this.canvas) return;
                        
                        this.resizeCanvas();
                        this.preventTouchScroll(this.canvas);
                        
                        if (window.ResizeObserver) {
                            const observer = new ResizeObserver(() => {
                                if (this.history.length === 0) {
                                    this.resizeCanvas();
                                }
                            });
                            observer.observe(this.canvas.parentElement);
                        }
                    });
                },

                preventTouchScroll(el) {
                    if (!el) return;
                    el.addEventListener('touchstart', (e) => {
                        if (e.target === el) e.preventDefault();
                    }, { passive: false });
                    el.addEventListener('touchmove', (e) => {
                        if (e.target === el) e.preventDefault();
                    }, { passive: false });
                    el.addEventListener('touchend', (e) => {
                        if (e.target === el) e.preventDefault();
                    }, { passive: false });
                },

                resizeCanvas() {
                    if (!this.canvas) return;
                    const rect = this.canvas.getBoundingClientRect();
                    if (rect.width === 0 || rect.height === 0) return;

                    const dpr = window.devicePixelRatio || 1;
                    this.canvas.width = Math.round(rect.width * dpr);
                    this.canvas.height = Math.round(rect.height * dpr);

                    this.ctx = this.canvas.getContext('2d', { willReadFrequently: true });
                    this.ctx.lineCap = 'round';
                    this.ctx.lineJoin = 'round';
                },

                openFullscreen() {
                    this.isFullscreen = true;
                    this.$nextTick(() => {
                        this.fsCanvas = this.$refs.fsCanvas;
                        if (!this.fsCanvas) return;

                        const rect = this.fsCanvas.getBoundingClientRect();
                        const dpr = window.devicePixelRatio || 1;
                        this.fsCanvas.width = Math.round(rect.width * dpr);
                        this.fsCanvas.height = Math.round(rect.height * dpr);

                        this.fsCtx = this.fsCanvas.getContext('2d', { willReadFrequently: true });
                        this.fsCtx.lineCap = 'round';
                        this.fsCtx.lineJoin = 'round';
                        this.preventTouchScroll(this.fsCanvas);

                        // Jika canvas utama sudah punya goresan, salin ke canvas fullscreen
                        if (this.history.length > 0 && this.canvas) {
                            this.fsCtx.drawImage(this.canvas, 0, 0, this.fsCanvas.width, this.fsCanvas.height);
                            this.fsHistory = [this.fsCtx.getImageData(0, 0, this.fsCanvas.width, this.fsCanvas.height)];
                        } else {
                            this.fsHistory = [];
                        }
                    });
                },

                closeFullscreen(apply) {
                    if (apply && this.fsCanvas && this.canvas) {
                        // Salin goresan dari fullscreen ke canvas utama
                        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                        this.ctx.drawImage(this.fsCanvas, 0, 0, this.canvas.width, this.canvas.height);
                        this.history.push(this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height));
                        this.syncToBase64();
                    }
                    this.isFullscreen = false;
                },

                setColor(color) {
                    this.currentColor = color;
                },

                setLineWidth(width) {
                    this.currentWidth = width;
                },

                getCoordinates(e, targetCanvas) {
                    const canvas = targetCanvas || this.canvas;
                    const rect = canvas.getBoundingClientRect();
                    const scaleX = canvas.width / rect.width;
                    const scaleY = canvas.height / rect.height;
                    return {
                        x: (e.clientX - rect.left) * scaleX,
                        y: (e.clientY - rect.top) * scaleY,
                        dpr: scaleX
                    };
                },

                // Goresan pada kanvas inline
                startDrawing(e) {
                    if (e.button && e.button !== 0) return;
                    this.isDrawing = true;

                    if (e.target && e.target.setPointerCapture && e.pointerId) {
                        try { e.target.setPointerCapture(e.pointerId); } catch(err) {}
                    }

                    this.history.push(this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height));

                    const coords = this.getCoordinates(e, this.canvas);
                    this.lastX = coords.x;
                    this.lastY = coords.y;

                    const strokeWidth = this.currentWidth * coords.dpr;

                    this.ctx.beginPath();
                    this.ctx.arc(coords.x, coords.y, strokeWidth / 2, 0, Math.PI * 2);
                    this.ctx.fillStyle = this.currentColor;
                    this.ctx.fill();

                    this.ctx.beginPath();
                    this.ctx.strokeStyle = this.currentColor;
                    this.ctx.lineWidth = strokeWidth;
                    this.ctx.moveTo(coords.x, coords.y);
                },

                draw(e) {
                    if (!this.isDrawing) return;
                    const coords = this.getCoordinates(e, this.canvas);

                    const midX = (this.lastX + coords.x) / 2;
                    const midY = (this.lastY + coords.y) / 2;

                    this.ctx.quadraticCurveTo(this.lastX, this.lastY, midX, midY);
                    this.ctx.stroke();

                    this.lastX = coords.x;
                    this.lastY = coords.y;
                },

                stopDrawing(e) {
                    if (!this.isDrawing) return;
                    this.isDrawing = false;

                    if (e && e.target && e.target.releasePointerCapture && e.pointerId) {
                        try { e.target.releasePointerCapture(e.pointerId); } catch(err) {}
                    }

                    this.ctx.closePath();
                    this.syncToBase64();
                },

                undo() {
                    if (this.history.length === 0) return;
                    const lastState = this.history.pop();
                    this.ctx.putImageData(lastState, 0, 0);
                    this.syncToBase64();
                },

                clearCanvas() {
                    if (!this.canvas || !this.ctx) return;
                    this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                    this.history = [];
                    @this.set('signature_data', null);
                },

                syncToBase64() {
                    if (!this.canvas) return;
                    const dataUrl = this.canvas.toDataURL('image/png');
                    @this.set('signature_data', dataUrl);
                },

                // Goresan pada kanvas fullscreen (khusus mobile)
                startDrawingFs(e) {
                    if (e.button && e.button !== 0) return;
                    this.isDrawing = true;

                    if (e.target && e.target.setPointerCapture && e.pointerId) {
                        try { e.target.setPointerCapture(e.pointerId); } catch(err) {}
                    }

                    this.fsHistory.push(this.fsCtx.getImageData(0, 0, this.fsCanvas.width, this.fsCanvas.height));

                    const coords = this.getCoordinates(e, this.fsCanvas);
                    this.lastX = coords.x;
                    this.lastY = coords.y;

                    const strokeWidth = this.currentWidth * coords.dpr;

                    this.fsCtx.beginPath();
                    this.fsCtx.arc(coords.x, coords.y, strokeWidth / 2, 0, Math.PI * 2);
                    this.fsCtx.fillStyle = this.currentColor;
                    this.fsCtx.fill();

                    this.fsCtx.beginPath();
                    this.fsCtx.strokeStyle = this.currentColor;
                    this.fsCtx.lineWidth = strokeWidth;
                    this.fsCtx.moveTo(coords.x, coords.y);
                },

                drawFs(e) {
                    if (!this.isDrawing) return;
                    const coords = this.getCoordinates(e, this.fsCanvas);

                    const midX = (this.lastX + coords.x) / 2;
                    const midY = (this.lastY + coords.y) / 2;

                    this.fsCtx.quadraticCurveTo(this.lastX, this.lastY, midX, midY);
                    this.fsCtx.stroke();

                    this.lastX = coords.x;
                    this.lastY = coords.y;
                },

                stopDrawingFs(e) {
                    if (!this.isDrawing) return;
                    this.isDrawing = false;

                    if (e && e.target && e.target.releasePointerCapture && e.pointerId) {
                        try { e.target.releasePointerCapture(e.pointerId); } catch(err) {}
                    }

                    this.fsCtx.closePath();
                },

                undoFs() {
                    if (this.fsHistory.length === 0) return;
                    const lastState = this.fsHistory.pop();
                    this.fsCtx.putImageData(lastState, 0, 0);
                },

                clearFsCanvas() {
                    if (!this.fsCanvas || !this.fsCtx) return;
                    this.fsCtx.clearRect(0, 0, this.fsCanvas.width, this.fsCanvas.height);
                    this.fsHistory = [];
                }
            };
        }
    </script>
</x-filament-panels::page>
