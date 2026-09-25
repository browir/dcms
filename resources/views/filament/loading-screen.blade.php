{{--
    DCMS Intro Screen — "Particles Forming DCMS" Animation
    =========================================================
    Filosofi: Partikel tersebar acak (data terpisah) bergerak menyusun diri
    membentuk huruf "DCMS" (data terhimpun jadi satu sistem terkelola).

    Muncul SEKALI PER SESI browser (sessionStorage). Akan muncul kembali setiap kali
    browser/tab ditutup dan dibuka ulang.
    Key: 'dcms_intro_v2'

    Timeline:
      0.0 – 0.5s  : Partikel muncul tersebar acak + garis penghubung
      0.5 – 1.6s  : Partikel bergerak membentuk huruf (easing out, staggered)
      1.6 – 2.2s  : Huruf sempurna, glow muncul, satu pulse halus
      2.2 – 2.8s  : Fade-out + scale-up, halaman utama fade-in (cross-fade)

    Tidak menyentuh canvas eah-particle-canvas di dashboard sama sekali.
--}}

{{-- ── Deteksi flag SINKRON sebelum browser paint ──────────────────────────── --}}
<script>
(function () {
    'use strict';
    var FLAG_KEY = 'dcms_intro_v2';
    var seen = false;
    try { seen = !!sessionStorage.getItem(FLAG_KEY); } catch (e) { seen = true; }
    if (seen) {
        // Sudah pernah lihat — inject display:none SEBELUM browser paint overlay
        document.addEventListener('DOMContentLoaded', function () {
            var el = document.getElementById('dcms-intro-screen');
            if (el) el.style.cssText = 'display:none!important';
        }, { once: true });
        // Juga sembunyikan secara inline via script yang berjalan parser-blocking
        // (script ini di head/body.start jadi akan berjalan sebelum element di-parse)
        window.__dcmsIntroSeen = true;
    } else {
        window.__dcmsIntroSeen = false;
    }
})();
</script>

{{-- ── Overlay HTML ─────────────────────────────────────────────────────────── --}}
<div id="dcms-intro-screen" aria-hidden="true" role="presentation">
    {{-- Canvas utama untuk seluruh animasi partikel --}}
    <canvas id="dcms-intro-canvas"></canvas>

    {{-- Tombol skip kecil di pojok kanan bawah --}}
    <button
        id="dcms-intro-skip"
        type="button"
        aria-label="Lewati intro"
        onclick="window.__dcmsIntroSkip && window.__dcmsIntroSkip()"
    >Lewati ›</button>
</div>

{{-- ── CSS ─────────────────────────────────────────────────────────────────── --}}
<style>
#dcms-intro-screen {
    position: fixed;
    inset: 0;
    z-index: 2147483647; /* max z-index */
    background: #0a1428;
    overflow: hidden;
    cursor: pointer;

    /* Overlay awal: invisible, akan di-fade-in oleh JS */
    opacity: 0;
    transition: opacity 0.35s ease, transform 0.55s cubic-bezier(0.4, 0, 0.2, 1);
}

#dcms-intro-screen.dcms-intro-visible {
    opacity: 1;
}

/* State keluar: fade-out + scale-up */
#dcms-intro-screen.dcms-intro-exiting {
    opacity: 0 !important;
    transform: scale(1.05);
    pointer-events: none;
    transition: opacity 0.55s cubic-bezier(0.4, 0, 0.2, 1),
                transform 0.55s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

#dcms-intro-canvas {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    display: block;
}

#dcms-intro-skip {
    position: absolute;
    bottom: 28px;
    right: 28px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.18);
    color: rgba(255, 255, 255, 0.55);
    font-family: 'Inter', system-ui, sans-serif;
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 0.06em;
    padding: 6px 14px;
    border-radius: 99px;
    cursor: pointer;
    transition: color 0.2s, background 0.2s, border-color 0.2s;
    z-index: 10;
    -webkit-tap-highlight-color: transparent;
}

#dcms-intro-skip:hover {
    background: rgba(255, 255, 255, 0.14);
    color: rgba(255, 255, 255, 0.85);
    border-color: rgba(255, 255, 255, 0.3);
}
</style>

{{-- ── JavaScript: Animasi Utama ───────────────────────────────────────────── --}}
<script>
(function () {
    'use strict';

    /* ── KONFIGURASI ───────────────────────────────────────────────── */
    var FLAG_KEY      = 'dcms_intro_v2';
    var TOTAL_MS      = 2800;   /* total durasi animasi sebelum dismiss */
    var FADE_OUT_MS   = 550;    /* durasi fade-out overlay */
    var SCATTER_END   = 0.18;   /* fraksi waktu fase tersebar (0–18%) */
    var GATHER_END    = 0.60;   /* fraksi waktu fase bergerak (18–60%) */
    var HOLD_END      = 0.80;   /* fraksi waktu fase hold/pulse (60–80%) */
    /* 80–100% = fade-out */

    var isMobile      = window.innerWidth < 768 || ('ontouchstart' in window);
    var PARTICLE_COUNT_DESKTOP = 160;
    var PARTICLE_COUNT_MOBILE  = 80;
    var PARTICLE_COUNT = isMobile ? PARTICLE_COUNT_MOBILE : PARTICLE_COUNT_DESKTOP;

    /* ── GUARD: skip jika sudah pernah tampil ──────────────────────── */
    if (window.__dcmsIntroSeen) {
        var _el = document.getElementById('dcms-intro-screen');
        if (_el) _el.style.cssText = 'display:none!important';
        return;
    }

    /* ── ELEMENT REFS ──────────────────────────────────────────────── */
    var overlay = document.getElementById('dcms-intro-screen');
    var canvas  = document.getElementById('dcms-intro-canvas');
    if (!overlay || !canvas) return;

    var ctx = canvas.getContext('2d');
    if (!ctx) return;

    /* ── STATE ─────────────────────────────────────────────────────── */
    var W, H;
    var particles    = [];
    var textPoints   = [];   /* koordinat pixel dari huruf "DCMS" */
    var animId       = null;
    var startTime    = null;
    var dismissed    = false;
    var glowOpacity  = 0;
    var glowRadius   = 0;

    /* ── FUNGSI DISMISS ────────────────────────────────────────────── */
    function dismiss() {
        if (dismissed) return;
        dismissed = true;

        /* Tandai sessionStorage — tidak akan muncul lagi dalam sesi ini, tapi muncul lagi saat browser/tab dibuka ulang */
        try { sessionStorage.setItem(FLAG_KEY, '1'); } catch (e) {}

        /* Stop animation loop */
        if (animId) { cancelAnimationFrame(animId); animId = null; }

        /* Trigger CSS exit transition */
        overlay.classList.add('dcms-intro-exiting');

        /* Hapus dari DOM setelah transisi selesai */
        setTimeout(function () {
            if (overlay && overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
        }, FADE_OUT_MS + 50);
    }

    /* ── SKIP HANDLER ──────────────────────────────────────────────── */
    window.__dcmsIntroSkip = dismiss;

    /* Klik di mana saja = skip */
    overlay.addEventListener('click', function (e) {
        /* Abaikan klik pada tombol skip itu sendiri (sudah ada onclick) */
        if (e.target && e.target.id === 'dcms-intro-skip') return;
        dismiss();
    });

    /* Fallback timeout mutlak */
    setTimeout(dismiss, TOTAL_MS + FADE_OUT_MS + 500);

    /* ── RESIZE CANVAS ─────────────────────────────────────────────── */
    function resizeCanvas() {
        W = canvas.width  = window.innerWidth;
        H = canvas.height = window.innerHeight;
    }

    /* ── BUILD TEXT POINT MAP ──────────────────────────────────────── */
    /*
     * Render "DCMS" ke offscreen canvas tersembunyi,
     * scan pixel data untuk mengambil koordinat yang terisi (alpha > 100),
     * sample setiap N pixel untuk mendapat ~PARTICLE_COUNT titik target.
     */
    function buildTextPoints() {
        var offW = Math.min(W, 900);
        var offH = Math.round(offW * 0.22);

        /* Font size proporsional terhadap lebar layar */
        var fontSize = isMobile
            ? Math.round(W * 0.22)   /* mobile: ~22% lebar layar */
            : Math.round(Math.min(W * 0.15, 180)); /* desktop: max 180px */

        var off = document.createElement('canvas');
        off.width  = offW;
        off.height = offH + fontSize * 0.4;

        var octx = off.getContext('2d');
        octx.clearRect(0, 0, off.width, off.height);

        /* Font bold dengan letter-spacing yang lebar */
        octx.font        = 'bold ' + fontSize + 'px "Inter", "Segoe UI", system-ui, sans-serif';
        octx.fillStyle   = '#ffffff';
        octx.textAlign   = 'center';
        octx.textBaseline = 'middle';
        octx.fillText('DCMS', off.width / 2, off.height / 2);

        /* Scan pixels */
        var imageData = octx.getImageData(0, 0, off.width, off.height);
        var data      = imageData.data;
        var raw       = [];

        /* Step sampling: lebih besar = lebih sedikit titik */
        var step = isMobile ? 4 : 3;

        for (var py = 0; py < off.height; py += step) {
            for (var px = 0; px < off.width; px += step) {
                var idx = (py * off.width + px) * 4;
                if (data[idx + 3] > 100) {   /* alpha > 100 = pixel huruf */
                    raw.push({ x: px, y: py });
                }
            }
        }

        /* Jika raw terlalu banyak, subsample lebih lanjut */
        if (raw.length > PARTICLE_COUNT * 1.5) {
            var step2 = Math.ceil(raw.length / PARTICLE_COUNT);
            var filtered = [];
            for (var i = 0; i < raw.length; i += step2) filtered.push(raw[i]);
            raw = filtered;
        }

        /* Scale koordinat dari offscreen canvas ke ukuran layar sebenarnya */
        var scaleX = W / off.width;
        var scaleY = H / off.height;

        /* Hitung offset supaya huruf terpusat di layar */
        var offsetX = (W - off.width * scaleX) / 2;  /* = 0 karena sudah full width */
        var offsetY = (H - off.height * scaleY) / 2;

        textPoints = raw.map(function (p) {
            return {
                x: offsetX + p.x * scaleX,
                y: offsetY + p.y * scaleY,
            };
        });
    }

    /* ── INIT PARTICLES ────────────────────────────────────────────── */
    function initParticles() {
        particles = [];

        /* Pastikan textPoints sudah ada */
        if (textPoints.length === 0) buildTextPoints();

        var count = Math.max(textPoints.length, PARTICLE_COUNT);

        for (var i = 0; i < count; i++) {
            /* Posisi awal: acak di seluruh layar */
            var ix = Math.random() * W;
            var iy = Math.random() * H;

            /* Kecepatan awal untuk fase scatter */
            var angle = Math.random() * Math.PI * 2;
            var speed = 0.2 + Math.random() * 0.4;

            /* Target: koordinat dari peta huruf (wrap around jika partikel > textPoints) */
            var tp = textPoints[i % textPoints.length];

            /* Stagger delay: partikel yang lebih jauh dari target mulai bergerak lebih lambat */
            var dist = Math.hypot(tp.x - ix, tp.y - iy);
            var delay = (dist / Math.max(W, H)) * 0.25; /* 0 – 0.25 fraksi waktu stagger */

            particles.push({
                x  : ix,
                y  : iy,
                vx : Math.cos(angle) * speed,
                vy : Math.sin(angle) * speed,
                tx : tp.x,
                ty : tp.y,
                delay: delay,          /* stagger delay (fraksi waktu global) */
                size : 1.5 + Math.random() * 1.2,
                alpha: 0,              /* fade-in awal */
            });
        }
    }

    /* ── EASING FUNCTIONS ──────────────────────────────────────────── */
    function easeOutCubic(t) {
        return 1 - Math.pow(1 - t, 3);
    }
    function easeInOutCubic(t) {
        return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
    }

    /* ── DRAW FRAME ────────────────────────────────────────────────── */
    function drawFrame(elapsed) {
        var t = elapsed / TOTAL_MS;              /* 0 → 1, progres global */
        t = Math.min(t, 1);

        ctx.clearRect(0, 0, W, H);

        /* ── Background: navy gradient dengan sedikit gradient-shift ── */
        var grad = ctx.createLinearGradient(0, 0, W, H);
        var shift = Math.sin(elapsed * 0.0003) * 0.03;
        grad.addColorStop(0,   'hsl(215,' + Math.round(70 + shift * 100) + '%,' + Math.round(11 + shift * 20) + '%)');
        grad.addColorStop(1,   'hsl(220,' + Math.round(75 + shift * 100) + '%,' + Math.round(16 + shift * 20) + '%)');
        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, W, H);

        /* ── Radial glow background ──────────────────────────────────── */
        var cx = W / 2;
        var cy = H / 2;

        if (t < SCATTER_END) {
            /* Fase scatter: glow sangat lebar, sangat redup */
            glowRadius  = Math.min(W, H) * 0.8;
            glowOpacity = 0.04;
        } else if (t < GATHER_END) {
            /* Fase gather: glow mengecil dan menguat */
            var gp = (t - SCATTER_END) / (GATHER_END - SCATTER_END);
            gp = easeOutCubic(gp);
            glowRadius  = Math.min(W, H) * (0.8 - gp * 0.55);
            glowOpacity = 0.04 + gp * 0.18;
        } else if (t < HOLD_END) {
            /* Fase hold: glow terfokus kuat di belakang huruf */
            var hp = (t - GATHER_END) / (HOLD_END - GATHER_END);
            /* Satu pulse halus */
            var pulse = Math.sin(hp * Math.PI) * 0.06;
            glowRadius  = Math.min(W, H) * (0.25 + pulse);
            glowOpacity = 0.22 + pulse * 0.5;
        } else {
            /* Fade-out phase: glow mulai memudar */
            var fp = (t - HOLD_END) / (1 - HOLD_END);
            glowOpacity = 0.22 * (1 - fp);
            glowRadius  = Math.min(W, H) * 0.25;
        }

        /* Gambar radial glow */
        if (glowOpacity > 0) {
            var radGrad = ctx.createRadialGradient(cx, cy, 0, cx, cy, glowRadius);
            radGrad.addColorStop(0,   'rgba(59, 130, 246, ' + glowOpacity.toFixed(3) + ')');
            radGrad.addColorStop(0.5, 'rgba(59, 130, 246, ' + (glowOpacity * 0.4).toFixed(3) + ')');
            radGrad.addColorStop(1,   'rgba(59, 130, 246, 0)');
            ctx.fillStyle = radGrad;
            ctx.beginPath();
            ctx.arc(cx, cy, glowRadius, 0, Math.PI * 2);
            ctx.fill();
        }

        /* ── Hitung progres gathering per-partikel ───────────────────── */
        var connectDist  = 110;
        var connectDist2 = connectDist * connectDist;

        /* Opacity garis penghubung (memudar saat partikel mendekat huruf) */
        var lineBaseAlpha;
        if (t < SCATTER_END) {
            lineBaseAlpha = easeOutCubic(t / SCATTER_END) * 0.30;
        } else if (t < GATHER_END) {
            var fadeP = (t - SCATTER_END) / (GATHER_END - SCATTER_END);
            lineBaseAlpha = 0.30 * (1 - easeOutCubic(fadeP));
        } else {
            lineBaseAlpha = 0;
        }

        /* ── Gambar garis penghubung antar partikel ──────────────────── */
        if (lineBaseAlpha > 0.005) {
            for (var i = 0; i < particles.length; i++) {
                for (var j = i + 1; j < particles.length; j++) {
                    var dx = particles[i].x - particles[j].x;
                    var dy = particles[i].y - particles[j].y;
                    var d2 = dx * dx + dy * dy;
                    if (d2 < connectDist2) {
                        var fade = 1 - Math.sqrt(d2) / connectDist;
                        ctx.beginPath();
                        ctx.strokeStyle = 'rgba(255,255,255,' + (lineBaseAlpha * fade).toFixed(3) + ')';
                        ctx.lineWidth   = 0.7;
                        ctx.moveTo(particles[i].x, particles[i].y);
                        ctx.lineTo(particles[j].x, particles[j].y);
                        ctx.stroke();
                    }
                }
            }
        }

        /* ── Update & gambar partikel ────────────────────────────────── */
        for (var pi = 0; pi < particles.length; pi++) {
            var p = particles[pi];

            /* Phase scatter: partikel melayang bebas */
            if (t < SCATTER_END) {
                p.x += p.vx;
                p.y += p.vy;
                /* Bounce dari tepi */
                if (p.x < 0 || p.x > W) p.vx *= -1;
                if (p.y < 0 || p.y > H) p.vy *= -1;
                /* Fade-in partikel */
                p.alpha = Math.min(p.alpha + 0.04, 0.75);
            }
            /* Phase gather: partikel bergerak menuju target */
            else if (t < GATHER_END) {
                /* Waktu efektif gather untuk partikel ini (memperhitungkan stagger delay) */
                var gatherT     = (t - SCATTER_END) / (GATHER_END - SCATTER_END);
                var adjustedT   = Math.max(0, gatherT - p.delay) / (1 - p.delay);
                adjustedT       = Math.min(adjustedT, 1);
                var eased       = easeOutCubic(adjustedT);

                /* Gerak menuju target menggunakan lerp */
                p.x += (p.tx - p.x) * (0.08 + eased * 0.06);
                p.y += (p.ty - p.y) * (0.08 + eased * 0.06);

                /* Fade ke opacity penuh seiring mendekat */
                p.alpha = 0.75 + eased * 0.25;
            }
            /* Phase hold & fade-out: diam di posisi target */
            else {
                p.x += (p.tx - p.x) * 0.15;
                p.y += (p.ty - p.y) * 0.15;

                if (t < HOLD_END) {
                    p.alpha = 1;
                } else {
                    /* Fade-out partikel */
                    var fop = (t - HOLD_END) / (1 - HOLD_END);
                    p.alpha = 1 - easeOutCubic(fop);
                }
            }

            /* Gambar partikel */
            if (p.alpha > 0.01) {
                /* Glow halus di sekitar partikel saat fase hold */
                if (t > GATHER_END && t < HOLD_END) {
                    var hfrac = (t - GATHER_END) / (HOLD_END - GATHER_END);
                    var gSize = p.size * (1 + easeInOutCubic(hfrac) * 3);
                    var gAlpha = p.alpha * easeInOutCubic(hfrac) * 0.3;
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, gSize, 0, Math.PI * 2);
                    ctx.fillStyle = 'rgba(100, 170, 255,' + gAlpha.toFixed(3) + ')';
                    ctx.fill();
                }

                /* Titik utama partikel */
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);

                /* Warna gradient: dari putih (scatter) ke biru-putih (hold) */
                if (t < GATHER_END) {
                    ctx.fillStyle = 'rgba(255,255,255,' + p.alpha.toFixed(3) + ')';
                } else {
                    /* Fade ke warna biru-putih saat membentuk huruf */
                    var cf = Math.min((t - GATHER_END) / (HOLD_END - GATHER_END), 1);
                    var r  = Math.round(255 - cf * (255 - 135));
                    var g  = Math.round(255 - cf * (255 - 185));
                    var b  = 255;
                    ctx.fillStyle = 'rgba(' + r + ',' + g + ',' + b + ',' + p.alpha.toFixed(3) + ')';
                }
                ctx.fill();
            }
        }

        /* ── Glow text overlay saat huruf terbentuk (fase hold) ──────── */
        if (t > GATHER_END * 0.85 && t < 1) {
            var textAlpha;
            if (t < GATHER_END) {
                textAlpha = (t - GATHER_END * 0.85) / (GATHER_END * 0.15) * 0.15;
            } else if (t < HOLD_END) {
                var hf2 = (t - GATHER_END) / (HOLD_END - GATHER_END);
                var pulse2 = Math.sin(hf2 * Math.PI) * 0.15;
                textAlpha = 0.15 + easeOutCubic(hf2) * 0.35 + pulse2;
            } else {
                var fo2 = (t - HOLD_END) / (1 - HOLD_END);
                textAlpha = (0.50) * (1 - easeOutCubic(fo2));
            }

            if (textAlpha > 0.01) {
                /* Font size identik dengan offscreen canvas */
                var fs = isMobile
                    ? Math.round(W * 0.22)
                    : Math.round(Math.min(W * 0.15, 180));

                ctx.save();
                ctx.font        = 'bold ' + fs + 'px "Inter", "Segoe UI", system-ui, sans-serif';
                ctx.textAlign   = 'center';
                ctx.textBaseline = 'middle';

                /* Text glow via shadowBlur */
                ctx.shadowColor   = 'rgba(100, 180, 255, ' + (textAlpha * 0.9).toFixed(3) + ')';
                ctx.shadowBlur    = 40;

                /* Gradient fill: biru muda → putih */
                var tGrad = ctx.createLinearGradient(cx - fs * 2, cy, cx + fs * 2, cy);
                tGrad.addColorStop(0,   'rgba(100, 170, 255, ' + textAlpha.toFixed(3) + ')');
                tGrad.addColorStop(0.5, 'rgba(220, 235, 255, ' + textAlpha.toFixed(3) + ')');
                tGrad.addColorStop(1,   'rgba(100, 170, 255, ' + textAlpha.toFixed(3) + ')');
                ctx.fillStyle = tGrad;

                ctx.fillText('DCMS', cx, cy);
                ctx.shadowBlur = 0;
                ctx.restore();
            }
        }

        /* ── Auto-dismiss saat animasi selesai ──────────────────────── */
        if (t >= 1) {
            dismiss();
        }
    }

    /* ── ANIMATION LOOP ────────────────────────────────────────────── */
    function loop(timestamp) {
        if (dismissed) return;
        if (!startTime) startTime = timestamp;

        var elapsed = timestamp - startTime;
        drawFrame(elapsed);

        if (elapsed < TOTAL_MS) {
            animId = requestAnimationFrame(loop);
        } else {
            dismiss();
        }
    }

    /* ── INITIALIZE ────────────────────────────────────────────────── */
    function init() {
        resizeCanvas();
        buildTextPoints();
        initParticles();

        /* Fade-in overlay */
        overlay.classList.add('dcms-intro-visible');

        /* Mulai animasi satu frame setelah render awal */
        animId = requestAnimationFrame(loop);
    }

    /* Pause saat tab tersembunyi — hemat baterai */
    var pausedAt = null;
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            if (animId) { cancelAnimationFrame(animId); animId = null; }
            pausedAt = performance.now();
        } else {
            if (pausedAt && startTime) {
                /* Kompensasi waktu yang terlewat saat tab tersembunyi */
                startTime += (performance.now() - pausedAt);
                pausedAt = null;
            }
            if (!dismissed && !animId) {
                animId = requestAnimationFrame(loop);
            }
        }
    });

    /* Resize handler */
    var resizeTimer = null;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            if (dismissed) return;
            resizeCanvas();
            buildTextPoints();
            /* Re-assign target ke partikel yang ada */
            for (var i = 0; i < particles.length; i++) {
                var tp = textPoints[i % textPoints.length];
                particles[i].tx = tp.x;
                particles[i].ty = tp.y;
            }
        }, 150);
    });

    /* Mulai saat DOM siap */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        /* Tunda satu microtask agar browser sempat render HTML dulu */
        Promise.resolve().then(init);
    }
})();
</script>
