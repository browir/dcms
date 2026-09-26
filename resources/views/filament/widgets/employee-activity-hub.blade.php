<x-filament-widgets::widget>
    @php
        $todayLoad = $todayMeetingsCount + $documentsNeedActionCount + $unreadNotificationsCount;
        $focusTone = match (true) {
            $todayLoad >= 8 => ['label' => 'Padat', 'class' => 'eah-chip--rose'],
            $todayLoad >= 4 => ['label' => 'Aktif', 'class' => 'eah-chip--amber'],
            default => ['label' => 'Terkendali', 'class' => 'eah-chip--emerald'],
        };
    @endphp

    <div class="eah-shell" x-data="{ tab: 'focus' }">
        <div class="eah-hero eah-animate" style="--eah-delay: 0ms;">
            {{-- Particle network canvas — desktop only, pauses when tab hidden --}}
            <canvas 
                class="eah-particle-canvas" 
                aria-hidden="true"
                x-data="{
                    init() {
                        if (window.matchMedia && window.matchMedia('(max-width: 767px)').matches) return;
                        const canvas = this.$el;
                        const ctx = canvas.getContext('2d');
                        if (!ctx) return;
                        
                        let W = 0, H = 0, animId = null, particles = [];

                        const CFG = {
                            particleCount : 65,
                            speedMin      : 0.15,
                            speedMax      : 0.35,
                            dotRadius     : 2.0,
                            dotOpacity    : 0.85,
                            lineOpacity   : 0.35,
                            connectDist   : 140,
                            mouseDist     : 180, // Interactivity distance for mouse
                            dotColor      : '255, 255, 255', // Pure white for maximum contrast on blue
                            lineColor     : '255, 255, 255',
                        };

                        const mouse = { x: null, y: null };

                        const resize = () => {
                            const hero = canvas.parentElement;
                            if (!hero) return;
                            const rect = hero.getBoundingClientRect();
                            const dpr = window.devicePixelRatio || 1;
                            W = rect.width;
                            H = rect.height;
                            canvas.width = W * dpr;
                            canvas.height = H * dpr;
                            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
                        };

                        const mkParticle = () => {
                            const angle = Math.random() * Math.PI * 2;
                            const speed = CFG.speedMin + Math.random() * (CFG.speedMax - CFG.speedMin);
                            return {
                                x: Math.random() * W,
                                y: Math.random() * H,
                                vx: Math.cos(angle) * speed,
                                vy: Math.sin(angle) * speed,
                            };
                        };

                        const initParticles = () => {
                            particles = [];
                            for (let i = 0; i < CFG.particleCount; i++) particles.push(mkParticle());
                        };

                        const draw = () => {
                            ctx.clearRect(0, 0, W, H);
                            
                            // 1. Update and draw particles
                            for (const p of particles) {
                                p.x += p.vx; p.y += p.vy;
                                if (p.x < 0) { p.x = 0; p.vx *= -1; }
                                else if (p.x > W) { p.x = W; p.vx *= -1; }
                                
                                if (p.y < 0) { p.y = 0; p.vy *= -1; }
                                else if (p.y > H) { p.y = H; p.vy *= -1; }
                            }
                            
                            // 2. Draw connections between particles
                            const dist2 = CFG.connectDist * CFG.connectDist;
                            const mouseDist2 = CFG.mouseDist * CFG.mouseDist;

                            // Draw lines between particles
                            for (let i = 0; i < particles.length; i++) {
                                for (let j = i + 1; j < particles.length; j++) {
                                    const dx = particles[i].x - particles[j].x;
                                    const dy = particles[i].y - particles[j].y;
                                    const d2 = dx * dx + dy * dy;
                                    
                                    if (d2 < dist2) {
                                        const fade = 1 - Math.sqrt(d2) / CFG.connectDist;
                                        ctx.beginPath();
                                        ctx.strokeStyle = 'rgba(' + CFG.lineColor + ', ' + (CFG.lineOpacity * fade).toFixed(3) + ')';
                                        ctx.lineWidth = 0.8;
                                        ctx.moveTo(particles[i].x, particles[i].y);
                                        ctx.lineTo(particles[j].x, particles[j].y);
                                        ctx.stroke();
                                    }
                                }

                                // Interactive: Draw line from particle to mouse if nearby
                                if (mouse.x !== null && mouse.y !== null) {
                                    const mdx = particles[i].x - mouse.x;
                                    const mdy = particles[i].y - mouse.y;
                                    const md2 = mdx * mdx + mdy * mdy;

                                    if (md2 < mouseDist2) {
                                        // Mouse connection is slightly stronger/more visible
                                        const mFade = 1 - Math.sqrt(md2) / CFG.mouseDist;
                                        ctx.beginPath();
                                        ctx.strokeStyle = 'rgba(' + CFG.dotColor + ', ' + (CFG.lineOpacity * 1.5 * mFade).toFixed(3) + ')';
                                        ctx.lineWidth = 1.0;
                                        ctx.moveTo(particles[i].x, particles[i].y);
                                        ctx.lineTo(mouse.x, mouse.y);
                                        ctx.stroke();
                                    }
                                }
                            }
                            
                            // 3. Draw dot heads
                            ctx.fillStyle = 'rgba(' + CFG.dotColor + ', ' + CFG.dotOpacity + ')';
                            for (const p of particles) {
                                ctx.beginPath();
                                ctx.arc(p.x, p.y, CFG.dotRadius, 0, Math.PI * 2);
                                ctx.fill();
                            }
                        };

                        const loop = () => {
                            draw();
                            animId = requestAnimationFrame(loop);
                        };

                        const start = () => { if (!animId) animId = requestAnimationFrame(loop); };
                        const stop = () => { if (animId) { cancelAnimationFrame(animId); animId = null; } };

                        document.addEventListener('visibilitychange', () => document.hidden ? stop() : start());

                        // Setup mouse interaction
                        const hero = canvas.parentElement;
                        if (hero) {
                            hero.addEventListener('mousemove', (e) => {
                                const rect = hero.getBoundingClientRect();
                                mouse.x = e.clientX - rect.left;
                                mouse.y = e.clientY - rect.top;
                            });
                            hero.addEventListener('mouseleave', () => {
                                mouse.x = null;
                                mouse.y = null;
                            });
                        }

                        // Use setTimeout to ensure DOM layout is completely painted before taking measurements
                        setTimeout(() => {
                            resize();
                            initParticles();
                            start();
                        }, 50);

                        let resizeTimer = null;
                        const ro = new ResizeObserver(() => {
                            clearTimeout(resizeTimer);
                            resizeTimer = setTimeout(() => {
                                if (window.matchMedia('(max-width: 767px)').matches) { stop(); return; }
                                stop(); resize(); initParticles(); start();
                            }, 150);
                        });
                        if (hero) ro.observe(hero);

                        if (typeof this.$cleanup === 'function') {
                            this.$cleanup(() => ro.disconnect());
                        }
                    }
                }"
            ></canvas>

            <div class="eah-hero-content">
                {{-- "Dasbor" label replaces the Filament page header --}}
                <div class="eah-page-label">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:inline;vertical-align:middle;opacity:0.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                    </svg>
                    Dasbor
                </div>
                <h2 class="eah-title">{{ $greeting }}, {{ $userName }}</h2>
                <p class="eah-subtitle">{{ $subtitle }}</p>

                <div class="eah-meta">
                    <span class="eah-chip {{ $focusTone['class'] }}">{{ $focusTone['label'] }}</span>
                    <span class="eah-meta-text">{{ $organizationPath }}</span>
                </div>
            </div>

            <div class="eah-next-card">
                {{-- Calendar icon badge --}}
                <div class="eah-next-card-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="rgba(147,197,253,0.95)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </div>

                <div class="eah-next-label">
                    Agenda terdekat
                </div>

                @if ($nextMeeting)
                    <a href="{{ $nextMeeting['url'] }}" wire:navigate class="eah-next-link">
                        <div class="eah-next-title">{{ $nextMeeting['title'] }}</div>
                        <div class="eah-next-time">{{ $nextMeeting['when'] }}</div>
                        <div class="eah-next-location">{{ $nextMeeting['location'] }}</div>
                    </a>
                @else
                    <div class="eah-next-empty">
                        Belum ada agenda rapat mendatang.
                    </div>
                @endif
            </div>
        </div>

        {{-- Stat Cards — uniform white, single teal accent --}}
        <div class="eah-stats">
            <div class="eah-stat eah-animate" style="--eah-delay: 70ms;">
                <div class="eah-stat-bar"></div>
                <div class="eah-stat-icon">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="eah-icon" />
                </div>
                <div class="eah-stat-content">
                    <div class="eah-stat-value">{{ $todayMeetingsCount }}</div>
                    <div class="eah-stat-label">Agenda hari ini</div>
                </div>
            </div>

            <div class="eah-stat eah-animate" style="--eah-delay: 140ms;">
                <div class="eah-stat-bar"></div>
                <div class="eah-stat-icon">
                    <x-filament::icon icon="heroicon-o-clipboard-document-list" class="eah-icon" />
                </div>
                <div class="eah-stat-content">
                    <div class="eah-stat-value">{{ $documentsNeedActionCount }}</div>
                    <div class="eah-stat-label">Perlu tindakan</div>
                </div>
            </div>

            <div class="eah-stat eah-animate" style="--eah-delay: 210ms;">
                <div class="eah-stat-bar"></div>
                <div class="eah-stat-icon">
                    <x-filament::icon icon="heroicon-o-bell" class="eah-icon" />
                </div>
                <div class="eah-stat-content">
                    <div class="eah-stat-value">{{ $unreadNotificationsCount }}</div>
                    <div class="eah-stat-label">Notifikasi belum dibaca</div>
                </div>
            </div>
        </div>

    </div>

    <style>
        [x-cloak] { display: none !important; }

        /* ─── SHELL ──────────────────────────────────────────────── */
        .eah-shell {
            display: grid;
            gap: 1rem;
        }

        /* ─── ENTRY ANIMATION ────────────────────────────────────── */
        .eah-hero,
        .eah-stat,
        .eah-panel {
            opacity: 0;
            transform: translateY(14px);
            animation: eah-rise 0.55s cubic-bezier(0.22, 1, 0.36, 1) forwards;
            animation-delay: var(--eah-delay, 0ms);
        }

        @keyframes eah-rise {
            to { opacity: 1; transform: translateY(0); }
        }

        /* ─── HERO ─────────────────────────────────────────────────
           Single hero — replaces the Filament page header bar.
           Navy/Blue theme with richer gradient + particle network.
        ──────────────────────────────────────────────────────────── */
        .eah-hero {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(280px, 0.85fr);
            gap: 1.5rem;
            padding: 2.25rem 2.5rem;
            border-radius: 1.15rem;
            background:
                radial-gradient(ellipse at top right, rgba(147,197,253,0.18) 0%, transparent 50%),
                radial-gradient(ellipse at bottom left, rgba(59,130,246,0.22) 0%, transparent 55%),
                radial-gradient(circle at 70% 50%, rgba(99,102,241,0.12) 0%, transparent 40%),
                linear-gradient(150deg, #0c1445 0%, #1d3a8a 40%, #2563eb 75%, #1e40af 100%);
            color: #fff;
            position: relative;
            overflow: hidden;
            box-shadow:
                0 12px 40px -10px rgba(37,99,235,0.55),
                0 4px 16px -4px rgba(0,0,0,0.2),
                inset 0 1px 0 rgba(255,255,255,0.12),
                inset 0 -1px 0 rgba(0,0,0,0.1);
        }

        /* Shimmer sweep */
        .eah-hero::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(110deg, transparent 20%, rgba(255,255,255,0.06) 50%, transparent 80%);
            transform: translateX(-120%);
            animation: eah-shimmer 9s linear infinite;
            pointer-events: none;
            z-index: 0;
        }

        @keyframes eah-shimmer {
            to { transform: translateX(120%); }
        }

        /* Text layers sit above particle canvas */
        .eah-hero-content {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding-right: 0.5rem;
        }

        /* ─── PARTICLE NETWORK CANVAS ─────────────────────────────
           Placed only inside .eah-hero (hero banner).
           Mobile: hidden entirely via media query.
        ──────────────────────────────────────────────────────────── */
        .eah-particle-canvas {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            pointer-events: none;
            opacity: 1;
        }

        /* Hide particle canvas on mobile — do NOT remove this rule */
        @media (max-width: 767px) {
            .eah-particle-canvas { display: none !important; }
        }

        /* ─── HERO TEXT ──────────────────────────────────────────── */
        .eah-page-label {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            /* Blue accent — sky blue for breadcrumb label in blue theme */
            color: rgba(147, 197, 253, 0.9);
            margin-bottom: 0.65rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .eah-title {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1.1;
            margin: 0;
            letter-spacing: -0.02em;
            text-shadow:
                0 2px 12px rgba(0,0,0,0.25),
                0 0 40px rgba(96,165,250,0.2);
        }

        .eah-subtitle {
            max-width: 58ch;
            margin-top: 0.65rem;
            color: rgba(255,255,255,0.8);
            line-height: 1.65;
            font-size: 0.93rem;
        }

        .eah-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
            align-items: center;
            margin-top: 1.25rem;
        }

        .eah-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.3rem 0.85rem;
            border-radius: 999px;
            font-size: 0.73rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        /* Blue-themed chips */
        .eah-chip--rose    { background: rgba(254,205,211,0.18); color: #fecdd3; border: 1px solid rgba(254,205,211,0.28); }
        .eah-chip--amber   { background: rgba(253,230,138,0.18); color: #fde68a; border: 1px solid rgba(253,230,138,0.28); }
        /* "Padat" badge — sky blue tone (consistent with blue theme) */
        .eah-chip--emerald { background: rgba(147,197,253,0.2);  color: #bfdbfe; border: 1px solid rgba(147,197,253,0.3); }

        .eah-meta-text {
            color: rgba(255,255,255,0.72);
            font-size: 0.86rem;
        }

        /* ─── NEXT MEETING CARD (inside hero) ────────────────────── */
        .eah-next-card {
            border-radius: 1rem;
            padding: 1.35rem 1.5rem;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.18);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            z-index: 2;
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.15),
                0 4px 20px -6px rgba(0,0,0,0.2);
            transition: background 0.3s ease, transform 0.25s cubic-bezier(0.34,1.56,0.64,1);
        }

        .eah-next-card:hover {
            background: rgba(255,255,255,0.16);
            transform: translateY(-2px);
        }

        /* Calendar icon at top of next-meeting card */
        .eah-next-card-icon {
            width: 2rem;
            height: 2rem;
            border-radius: 0.55rem;
            background: rgba(147,197,253,0.2);
            border: 1px solid rgba(147,197,253,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.8rem;
        }

        .eah-next-label {
            font-size: 0.67rem;
            font-weight: 700;
            /* Sky blue label for next-meeting card in blue theme */
            color: rgba(147, 197, 253, 0.9);
            margin-bottom: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .eah-next-link {
            color: #fff;
            text-decoration: none;
            display: grid;
            gap: 0.3rem;
            transition: transform 0.25s cubic-bezier(0.34,1.56,0.64,1);
        }

        .eah-next-link:hover { transform: translateY(-2px); }

        .eah-next-title {
            font-size: 1rem;
            font-weight: 700;
            line-height: 1.4;
        }

        .eah-next-time,
        .eah-next-location,
        .eah-next-empty {
            color: rgba(255,255,255,0.76);
            line-height: 1.5;
            font-size: 0.86rem;
        }

        /* ─── STAT CARDS ─────────────────────────────────────────────
           Uniform white — same card system as all other widgets.
           ONE teal accent bar on top; no per-card color competition.
        ──────────────────────────────────────────────────────────── */
        .eah-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .eah-stat {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: #fff;
            border-radius: 1rem;
            padding: 1rem 1.5rem 1rem 1rem;
            border: 1px solid rgba(0,0,0,0.07);
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
            transition: transform 0.28s cubic-bezier(0.16,1,0.3,1), box-shadow 0.28s ease, border-color 0.28s ease;
        }

        .eah-stat:hover {
            transform: translateY(-4px);
            border-color: rgba(37,99,235,0.22);
            box-shadow: 0 0 0 3px rgba(37,99,235,0.06), 0 8px 24px rgba(37,99,235,0.12);
        }

        /* Top teal accent bar — same teal across all three */
        .eah-stat-bar {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #2563eb, #3b82f6);
            border-radius: 0 0 3px 3px;
        }

        .eah-stat-icon {
            flex-shrink: 0;
            width: 3rem;
            height: 3rem;
            border-radius: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(37,99,235,0.1);
            color: #2563eb;
            transition: transform 0.35s cubic-bezier(0.34,1.56,0.64,1);
        }

        .eah-stat:hover .eah-stat-icon {
            transform: scale(1.12) rotate(6deg);
        }

        .eah-icon {
            width: 1.45rem;
            height: 1.45rem;
        }

        .eah-stat-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .eah-stat-value {
            font-size: 1.7rem;
            font-weight: 900;
            line-height: 1.1;
            letter-spacing: -0.04em;
            color: #0f172a;
        }

        .eah-stat-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #64748b;
            margin-top: 0.18rem;
        }

        /* ─── PANELS / LISTS (kept for backward compat) ──────────── */
        .eah-grid {
            display: grid;
            grid-template-columns: minmax(0,1.7fr) minmax(280px,0.95fr);
            gap: 1rem;
        }

        .eah-panel {
            background: #fff;
            border-radius: 1rem;
            padding: 1.2rem;
            border: 1px solid rgba(0,0,0,0.07);
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.05);
            transition: transform 0.25s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.25s ease;
        }

        .eah-panel:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px -8px rgba(37,99,235,0.25);
        }

        .eah-panel-head {
            display: flex;
            gap: 1rem;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .eah-panel-title {
            font-size: 0.95rem;
            font-weight: 800;
            color: #0f172a;
        }

        .eah-panel-desc {
            margin-top: 0.2rem;
            color: #64748b;
            font-size: 0.88rem;
            line-height: 1.5;
        }

        /* ─── TABS ───────────────────────────────────────────────── */
        .eah-tabs {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.28rem;
            border-radius: 999px;
            background: #f0fdfa;
            border: 1px solid rgba(37,99,235,0.15);
        }

        .eah-tab {
            border: none;
            background: transparent;
            color: #475569;
            font-weight: 700;
            font-size: 0.82rem;
            padding: 0.5rem 0.85rem;
            border-radius: 999px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .eah-tab--active {
            background: #fff;
            color: #2563eb;
            box-shadow: 0 2px 8px -2px rgba(37,99,235,0.3);
        }

        /* ─── LIST ITEMS ─────────────────────────────────────────── */
        .eah-list { display: grid; gap: 0.7rem; }
        .eah-tab-stage { min-height: 10rem; }

        .eah-item,
        .eah-action {
            opacity: 0;
            transform: translateY(10px);
            animation: eah-rise 0.5s cubic-bezier(0.22,1,0.36,1) forwards;
            animation-delay: var(--eah-delay, 0ms);
        }

        .eah-item {
            display: flex;
            justify-content: space-between;
            gap: 0.9rem;
            align-items: center;
            padding: 0.85rem 1rem;
            border-radius: 0.75rem;
            text-decoration: none;
            color: inherit;
            background: #fafafa;
            border: 1px solid rgba(0,0,0,0.06);
            transition: transform 0.22s cubic-bezier(0.34,1.56,0.64,1), border-color 0.22s ease, box-shadow 0.22s ease;
        }

        .eah-item:hover,
        .eah-action:hover {
            transform: translateY(-2px);
            border-color: rgba(37,99,235,0.22);
            box-shadow: 0 4px 16px -8px rgba(37,99,235,0.3);
        }

        .eah-item-main { min-width: 0; }

        .eah-item-title,
        .eah-action-title,
        .eah-note-title {
            font-weight: 700;
            color: #0f172a;
        }

        .eah-item-meta-line,
        .eah-action-desc,
        .eah-note-text,
        .eah-empty-text {
            margin-top: 0.25rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            color: #64748b;
            font-size: 0.83rem;
            line-height: 1.5;
        }

        .eah-item-code {
            display: inline-flex;
            align-items: center;
            padding: 0.15rem 0.4rem;
            border-radius: 999px;
            background: rgba(37,99,235,0.1);
            color: #2563eb;
            font-size: 0.72rem;
            font-weight: 700;
        }

        /* ─── BADGES ─────────────────────────────────────────────── */
        .eah-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
        }

        .eah-badge--amber, .eah-badge--warning { background: #fef3c7; color: #92400e; }
        .eah-badge--rose                        { background: #ffe4e6; color: #be123c; }
        .eah-badge--sky, .eah-badge--info       { background: #e0f2fe; color: #0369a1; }
        .eah-badge--slate, .eah-badge--gray     { background: #e2e8f0; color: #334155; }
        .eah-badge--emerald, .eah-badge--success{ background: #dcfce7; color: #166534; }

        /* ─── EMPTY STATE ────────────────────────────────────────── */
        .eah-empty {
            border-radius: 0.75rem;
            padding: 1.2rem;
            background: #fafafa;
            border: 1px dashed rgba(0,0,0,0.1);
        }

        .eah-empty-title { font-weight: 700; color: #0f172a; }

        /* ─── ACTIONS ────────────────────────────────────────────── */
        .eah-action-list { display: grid; gap: 0.7rem; }

        .eah-action-list--horizontal {
            grid-template-columns: repeat(auto-fit, minmax(260px,1fr));
        }

        .eah-action {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.85rem 1.25rem 0.85rem 0.85rem;
            border-radius: 1rem;
            text-decoration: none;
            background: #fff;
            border: 1px solid rgba(0,0,0,0.07);
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            transition: all 0.28s cubic-bezier(0.16,1,0.3,1);
            color: inherit;
        }

        .eah-action:hover {
            transform: translateY(-3px);
            border-color: rgba(37,99,235,0.22);
            box-shadow: 0 6px 20px -6px rgba(37,99,235,0.2);
        }

        .eah-action-icon {
            flex-shrink: 0;
            width: 3rem;
            height: 3rem;
            border-radius: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.35s cubic-bezier(0.34,1.56,0.64,1);
        }

        .eah-action:hover .eah-action-icon { transform: scale(1.1) rotate(6deg); }

        /* All action icons → teal (single accent) */
        .eah-action--indigo .eah-action-icon,
        .eah-action--blue   .eah-action-icon {
            background: rgba(37,99,235,0.12);
            color: #2563eb;
        }

        .eah-action--emerald .eah-action-icon {
            background: rgba(5,150,105,0.12);
            color: #059669;
        }

        .eah-action--amber .eah-action-icon {
            background: rgba(217,119,6,0.1);
            color: #d97706;
        }

        .eah-action--rose .eah-action-icon {
            background: rgba(225,29,72,0.1);
            color: #e11d48;
        }

        .eah-action-text { min-width: 0; display: grid; gap: 0.2rem; }

        /* ─── NOTE ───────────────────────────────────────────────── */
        .eah-note {
            margin-top: 1rem;
            padding: 0.9rem 1rem;
            border-radius: 0.85rem;
            background: linear-gradient(135deg, #0f172a, #1e3a8a);
            color: #fff;
        }

        .eah-note-title { color: #fff; }
        .eah-note-text  { color: rgba(255,255,255,0.8); }

        /* ─── PANEL TRANSITIONS ──────────────────────────────────── */
        .eah-panel-enter       { transition: opacity 0.18s ease, transform 0.22s cubic-bezier(0.22,1,0.36,1); }
        .eah-panel-enter-from,
        .eah-panel-leave-to    { opacity: 0; transform: translateY(8px); }
        .eah-panel-enter-to,
        .eah-panel-leave-from  { opacity: 1; transform: translateY(0); }
        .eah-panel-leave       { transition: opacity 0.14s ease, transform 0.14s ease; }

        /* ─── TABLET GRID COLLAPSE (NOT mobile) ──────────────────── */
        @media (max-width: 1024px) {
            .eah-hero,
            .eah-grid,
            .eah-stats {
                grid-template-columns: 1fr;
            }
        }

        /* ═══ MOBILE OVERRIDES — DO NOT MODIFY ══════════════════════ */
        .eah-grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; align-items: start; }

        @media (max-width: 900px) {
            .eah-grid-2col { grid-template-columns: 1fr; }
        }

        @media (max-width: 767.98px) {
            .eah-hero {
                padding: 0.9rem 1rem !important;
                gap: 0.75rem !important;
                border-radius: 1rem !important;
            }

            .eah-kicker {
                font-size: 0.65rem !important;
                margin-bottom: 0.2rem !important;
            }

            .eah-title {
                font-size: 1.2rem !important;
                line-height: 1.2 !important;
            }

            .eah-subtitle {
                font-size: 0.8rem !important;
                margin-top: 0.3rem !important;
                line-height: 1.4 !important;
            }

            .eah-meta {
                margin-top: 0.5rem !important;
                gap: 0.4rem !important;
            }

            .eah-chip {
                padding: 0.2rem 0.6rem !important;
                font-size: 0.7rem !important;
            }

            .eah-meta-text {
                font-size: 0.78rem !important;
            }

            .eah-next-card {
                padding: 0.65rem 0.85rem !important;
                border-radius: 0.8rem !important;
            }

            .eah-next-label {
                font-size: 0.65rem !important;
                margin-bottom: 0.3rem !important;
            }

            .eah-next-title {
                font-size: 0.88rem !important;
            }

            .eah-next-time,
            .eah-next-location,
            .eah-next-empty {
                font-size: 0.76rem !important;
                line-height: 1.35 !important;
            }

            .eah-stat {
                padding: 0.5rem 1.25rem 0.5rem 0.5rem !important;
                border-radius: 999px !important;
                gap: 0.85rem !important;
            }

            .eah-stat-icon {
                width: 2.8rem !important;
                height: 2.8rem !important;
                border-radius: 50% !important;
            }

            .eah-stat-value {
                font-size: 1.35rem !important;
            }

            .eah-stat-label {
                font-size: 0.75rem !important;
                margin-top: 0.1rem !important;
            }

            .eah-icon {
                width: 1.25rem !important;
                height: 1.25rem !important;
            }

            .eah-panel {
                padding: 0.9rem !important;
                border-radius: 0.9rem !important;
            }

            .eah-panel-head {
                flex-direction: column;
            }

            .eah-tabs {
                width: 100%;
                justify-content: space-between;
            }

            .eah-tab {
                flex: 1;
                text-align: center;
                padding: 0.4rem 0.6rem !important;
                font-size: 0.75rem !important;
            }

            .eah-item,
            .eah-action {
                flex-direction: column;
                align-items: flex-start;
                padding: 0.7rem 0.85rem !important;
            }
        }
        /* ══════════════════════════════════════════════════════════ */
    </style>

</x-filament-widgets::widget>
