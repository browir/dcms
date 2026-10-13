{{--
    Overlay loading saat rapat dibuat (CreateMeeting).

    Proses "Buat" cukup lama karena email undangan dikirim langsung (sync) ke setiap
    peserta di CreateMeeting::afterCreate(). Overlay ini muncul selama request `create`
    berjalan, hilang lagi kalau validasi gagal / error, dan berganti ke status
    "berhasil" kalau server membalas dengan redirect.
--}}
<div
    x-data="{
        state: 'idle',
        participants: 0,
        elapsed: 0,
        timer: null,
        unhook: null,

        init() {
            if (! window.Livewire) return;

            this.unhook = window.Livewire.hook('commit', ({ component, commit, succeed, fail }) => {
                if (component.id !== this.$wire.$id) return;
                if (! (commit.calls || []).some((call) => call.method === 'create')) return;

                this.start();

                succeed(({ effects }) => {
                    if (effects && effects.redirect) {
                        this.state = 'done';
                        this.stopTimer();
                    } else {
                        this.stop();
                    }
                });

                fail(() => this.stop());
            });
        },

        destroy() {
            this.stopTimer();
            if (this.unhook) this.unhook();
        },

        start() {
            try {
                const ids = this.$wire.data?.participants;
                this.participants = Array.isArray(ids) ? ids.length : Object.keys(ids || {}).length;
            } catch (e) {
                this.participants = 0;
            }

            this.elapsed = 0;
            this.state = 'loading';
            this.stopTimer();
            this.timer = setInterval(() => this.elapsed++, 1000);
        },

        stop() {
            this.stopTimer();
            this.state = 'idle';
        },

        stopTimer() {
            if (this.timer) clearInterval(this.timer);
            this.timer = null;
        },
    }"
>
    <div
        style="display: none"
        x-show="state !== 'idle'"
        x-transition.opacity.duration.250ms
        class="dcms-mcl"
        role="alertdialog"
        aria-modal="true"
        aria-live="polite"
        aria-labelledby="dcms-mcl-title"
    >
        <div class="dcms-mcl-card">
            {{-- Animasi: amplop terbang mengelilingi lingkaran --}}
            <div class="dcms-mcl-visual" x-show="state === 'loading'">
                <span class="dcms-mcl-ring"></span>
                <span class="dcms-mcl-ring dcms-mcl-ring--2"></span>
                <span class="dcms-mcl-orbit">
                    <span class="dcms-mcl-dot"></span>
                </span>
                <span class="dcms-mcl-orbit dcms-mcl-orbit--2">
                    <span class="dcms-mcl-dot"></span>
                </span>
                <span class="dcms-mcl-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                </span>
            </div>

            <div class="dcms-mcl-visual dcms-mcl-visual--done" x-show="state === 'done'">
                <span class="dcms-mcl-icon dcms-mcl-icon--done">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                </span>
            </div>

            <template x-if="state === 'loading'">
                <div>
                    <h2 id="dcms-mcl-title" class="dcms-mcl-title">Membuat rapat &amp; mengirim undangan</h2>
                    <p class="dcms-mcl-text">
                        <span x-show="participants > 0">Undangan email sedang dikirim ke <strong x-text="participants + ' peserta'"></strong>.</span>
                        <span x-show="participants === 0">Data rapat sedang disimpan.</span>
                        Proses ini bisa memakan waktu beberapa detik hingga sekitar 1 menit.
                    </p>

                    <div class="dcms-mcl-bar" aria-hidden="true"><span></span></div>

                    <div class="dcms-mcl-meta">
                        <span x-text="elapsed + ' detik'"></span>
                        <span>Jangan tutup atau muat ulang halaman ini</span>
                    </div>
                </div>
            </template>

            <template x-if="state === 'done'">
                <div>
                    <h2 id="dcms-mcl-title" class="dcms-mcl-title">Rapat berhasil dibuat</h2>
                    <p class="dcms-mcl-text">Undangan sudah dikirim. Mengalihkan halaman…</p>
                </div>
            </template>
        </div>
    </div>

    <style>
        .dcms-mcl {
            --mcl-card: #ffffff;
            --mcl-title: #0f172a;
            --mcl-text: #475569;
            --mcl-muted: #94a3b8;
            --mcl-accent: #2563eb;
            --mcl-accent-soft: rgba(37, 99, 235, 0.12);
            --mcl-success: #16a34a;
            --mcl-success-soft: rgba(22, 163, 74, 0.12);
            --mcl-track: #e2e8f0;

            position: fixed;
            inset: 0;
            z-index: 2147483000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            background: rgba(10, 20, 40, 0.55);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }

        .dark .dcms-mcl {
            --mcl-card: #111827;
            --mcl-title: #f1f5f9;
            --mcl-text: #cbd5e1;
            --mcl-muted: #64748b;
            --mcl-accent: #60a5fa;
            --mcl-accent-soft: rgba(96, 165, 250, 0.16);
            --mcl-success: #4ade80;
            --mcl-success-soft: rgba(74, 222, 128, 0.16);
            --mcl-track: #1f2937;
            background: rgba(0, 0, 0, 0.6);
        }

        .dcms-mcl-card {
            width: 100%;
            max-width: 420px;
            padding: 32px 28px 24px;
            border-radius: 20px;
            background: var(--mcl-card);
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28);
            text-align: center;
            animation: dcms-mcl-pop 0.3s cubic-bezier(0.2, 0.9, 0.3, 1.2);
        }

        .dcms-mcl-visual {
            position: relative;
            width: 104px;
            height: 104px;
            margin: 0 auto 20px;
        }

        .dcms-mcl-ring {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: var(--mcl-accent-soft);
            animation: dcms-mcl-pulse 2s ease-out infinite;
        }

        .dcms-mcl-ring--2 { animation-delay: 1s; }

        .dcms-mcl-orbit {
            position: absolute;
            inset: 6px;
            border-radius: 50%;
            border: 2px dashed var(--mcl-accent-soft);
            animation: dcms-mcl-spin 2.4s linear infinite;
        }

        .dcms-mcl-orbit--2 {
            inset: 18px;
            border: 0;
            animation-duration: 1.6s;
            animation-direction: reverse;
        }

        .dcms-mcl-dot {
            position: absolute;
            top: -5px;
            left: 50%;
            width: 10px;
            height: 10px;
            margin-left: -5px;
            border-radius: 50%;
            background: var(--mcl-accent);
            box-shadow: 0 0 10px var(--mcl-accent);
        }

        .dcms-mcl-orbit--2 .dcms-mcl-dot {
            width: 7px;
            height: 7px;
            margin-left: -3.5px;
            top: -3.5px;
            opacity: 0.7;
        }

        .dcms-mcl-icon {
            position: absolute;
            inset: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--mcl-accent);
            color: #ffffff;
            box-shadow: 0 8px 20px var(--mcl-accent-soft);
            animation: dcms-mcl-float 1.6s ease-in-out infinite;
        }

        .dcms-mcl-icon svg { width: 22px; height: 22px; }

        .dcms-mcl-visual--done .dcms-mcl-icon--done {
            inset: 18px;
            background: var(--mcl-success);
            box-shadow: 0 0 0 10px var(--mcl-success-soft);
            animation: dcms-mcl-pop 0.4s cubic-bezier(0.2, 0.9, 0.3, 1.4);
        }

        .dcms-mcl-visual--done .dcms-mcl-icon--done svg { width: 32px; height: 32px; }

        .dcms-mcl-title {
            margin: 0 0 8px;
            font-size: 18px;
            font-weight: 700;
            color: var(--mcl-title);
        }

        .dcms-mcl-text {
            margin: 0;
            font-size: 14px;
            line-height: 1.55;
            color: var(--mcl-text);
        }

        .dcms-mcl-text strong { color: var(--mcl-title); }

        .dcms-mcl-bar {
            position: relative;
            height: 6px;
            margin: 20px 0 12px;
            overflow: hidden;
            border-radius: 99px;
            background: var(--mcl-track);
        }

        .dcms-mcl-bar span {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 40%;
            border-radius: 99px;
            background: linear-gradient(90deg, transparent, var(--mcl-accent), transparent);
            animation: dcms-mcl-slide 1.3s ease-in-out infinite;
        }

        .dcms-mcl-meta {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 4px 12px;
            font-size: 12px;
            color: var(--mcl-muted);
        }

        .dcms-mcl-meta span:first-child {
            font-variant-numeric: tabular-nums;
            font-weight: 600;
        }

        @keyframes dcms-mcl-spin { to { transform: rotate(360deg); } }

        @keyframes dcms-mcl-pulse {
            0% { transform: scale(0.7); opacity: 0.9; }
            100% { transform: scale(1.35); opacity: 0; }
        }

        @keyframes dcms-mcl-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }

        @keyframes dcms-mcl-slide {
            0% { left: -40%; }
            100% { left: 100%; }
        }

        @keyframes dcms-mcl-pop {
            0% { transform: scale(0.85); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        @media (prefers-reduced-motion: reduce) {
            .dcms-mcl-card,
            .dcms-mcl-ring,
            .dcms-mcl-orbit,
            .dcms-mcl-icon,
            .dcms-mcl-bar span {
                animation-duration: 0s !important;
                animation-iteration-count: 1 !important;
            }
            .dcms-mcl-bar span { left: 0; width: 100%; opacity: 0.5; }
        }
    </style>
</div>
