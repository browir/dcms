@php
use Illuminate\Support\Facades\Auth;

$user = Auth::user();
$panelPath = request()->segment(1) ?: 'admin';

// Build user avatar initials
$name = $user?->name ?? 'User';
$initials = collect(explode(' ', $name))->take(2)->map(fn($w) => strtoupper(substr($w, 0, 1)))->join('');

// Menu mengikuti navigasi Filament (sama dengan navbar desktop), jadi otomatis hanya berisi
// halaman yang boleh diakses user. Item yang sudah ada di bar bawah tidak diulang.
$bottomBar = \App\Support\MobileNavigation::bottomBar();
$menuGroups = \App\Support\MobileNavigation::menuGroups(collect($bottomBar)->pluck('url')->all());
@endphp

{{-- ══════════════════════════════════════════════════════════
     DCMS MOBILE MENU OVERLAY  (with rich animations)
══════════════════════════════════════════════════════════ --}}
<div id="dcms-mobile-menu-overlay"
     class="dcms-menu-overlay"
     aria-hidden="true"
     role="dialog"
     aria-modal="true"
     aria-label="Menu Navigasi"
     onclick="if(event.target===this)dcmsCloseMenu()">

    {{-- Drawer Sheet --}}
    <div class="dcms-menu-sheet" role="document">

        {{-- Handle Bar --}}
        <div class="dcms-menu-handle"></div>

        {{-- User Profile Header --}}
        <div class="dcms-menu-header">
            <div class="dcms-menu-avatar">{{ $initials }}</div>
            <div class="dcms-menu-user-info">
                <div class="dcms-menu-user-name">{{ $user?->name ?? 'Pengguna' }}</div>
                <div class="dcms-menu-user-email">{{ $user?->email ?? '' }}</div>
            </div>
            <button class="dcms-menu-close-btn" onclick="dcmsCloseMenu()" aria-label="Tutup menu">
                <span class="dcms-close-x dcms-close-x-top"></span>
                <span class="dcms-close-x dcms-close-x-bot"></span>
            </button>
        </div>

        {{-- Menu Scrollable Body --}}
        <div class="dcms-menu-body">
            @php $globalItemIndex = 0; @endphp
            @foreach ($menuGroups as $group)
                @php $items = array_values(array_filter($group['items'])); @endphp
                @if (!empty($items))
                <div class="dcms-menu-group">
                    <div class="dcms-menu-group-label">{{ strtoupper($group['label']) }}</div>
                    <div class="dcms-menu-grid">
                        @foreach ($items as $item)
                        @php $delay = $globalItemIndex * 28; $globalItemIndex++; @endphp
                        <a href="{{ $item['url'] }}"
                           class="dcms-menu-item"
                           style="--item-delay: {{ $delay }}ms"
                           onclick="dcmsHandleItemClick(event, this)"
                           aria-label="{{ $item['label'] }}">
                            <div class="dcms-menu-item-icon" style="background: {{ $item['color'] }}18; color: {{ $item['color'] }}">
                                @include('filament.mobile-menu-icon', ['icon' => $item['icon'], 'color' => $item['color']])
                            </div>
                            <span class="dcms-menu-item-label">{{ $item['label'] }}</span>
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif
            @endforeach

            {{-- Akun Group --}}
            @php $delay = $globalItemIndex * 28; @endphp
            <div class="dcms-menu-group">
                <div class="dcms-menu-group-label">AKUN</div>
                <div class="dcms-menu-grid">
                    <a href="{{ url("/{$panelPath}/profile") }}"
                       class="dcms-menu-item"
                       style="--item-delay: {{ $delay }}ms"
                       onclick="dcmsHandleItemClick(event, this)">
                        <div class="dcms-menu-item-icon" style="background: #f1f5f9; color: #475569">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                        </div>
                        <span class="dcms-menu-item-label">Profil</span>
                    </a>
                    <form method="POST" action="{{ url('/admin/logout') }}" style="display:contents">
                        @csrf
                        <button type="submit" class="dcms-menu-item dcms-menu-logout"
                                style="--item-delay: {{ $delay + 28 }}ms">
                            <div class="dcms-menu-item-icon" style="background: #fef2f2; color: #dc2626">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75"/></svg>
                            </div>
                            <span class="dcms-menu-item-label">Keluar</span>
                        </button>
                    </form>
                </div>
            </div>

            <div style="height: 40px"></div>
        </div>
    </div>
</div>

<style>
/* ══════════════════════════════════════════════════════════
   DCMS MOBILE MENU — Animated iOS-style App Grid Drawer
══════════════════════════════════════════════════════════ */

/* ── Keyframes ─────────────────────────────────────────── */
@keyframes dcms-fade-in {
    from { opacity: 0; }
    to   { opacity: 1; }
}

@keyframes dcms-item-pop {
    0%   { opacity: 0; transform: scale(0.55) translateY(12px); }
    65%  { opacity: 1; transform: scale(1.06) translateY(-3px); }
    82%  { transform: scale(0.97) translateY(1px); }
    100% { opacity: 1; transform: scale(1) translateY(0); }
}

@keyframes dcms-header-slide {
    from { opacity: 0; transform: translateY(-10px); }
    to   { opacity: 1; transform: translateY(0); }
}

@keyframes dcms-label-slide {
    from { opacity: 0; transform: translateY(4px); }
    to   { opacity: 1; transform: translateY(0); }
}

@keyframes dcms-icon-bounce {
    0%   { transform: scale(1); }
    40%  { transform: scale(0.88); }
    70%  { transform: scale(1.12); }
    100% { transform: scale(1); }
}

@keyframes dcms-ripple {
    0%   { transform: scale(0); opacity: 0.35; }
    100% { transform: scale(3.5); opacity: 0; }
}

@keyframes dcms-handle-wag {
    0%, 100% { transform: scaleX(1); }
    25% { transform: scaleX(0.6); }
    50% { transform: scaleX(1.2); }
    75% { transform: scaleX(0.85); }
}

/* ── Overlay backdrop ──────────────────────────────────── */
.dcms-menu-overlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 99999999;
    background: rgba(15, 23, 42, 0);
    backdrop-filter: blur(0px);
    -webkit-backdrop-filter: blur(0px);
    align-items: flex-end;
    justify-content: center;
    transition:
        background 0.32s ease,
        backdrop-filter 0.32s ease;
}

.dcms-menu-overlay.dcms-open {
    display: flex;
    background: rgba(15, 23, 42, 0.5);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
}

.dcms-menu-overlay.dcms-animating-out {
    background: rgba(15, 23, 42, 0) !important;
    backdrop-filter: blur(0px) !important;
    -webkit-backdrop-filter: blur(0px) !important;
}

/* ── Sheet (bottom drawer) ─────────────────────────────── */
.dcms-menu-sheet {
    background: #ffffff;
    width: 100%;
    max-width: 480px;
    max-height: 92vh;
    border-radius: 24px 24px 0 0;
    display: flex;
    flex-direction: column;
    transform: translateY(100%);
    transition: transform 0.42s cubic-bezier(0.22, 1, 0.36, 1);
    overflow: hidden;
    will-change: transform;
    /* Subtle top shadow */
    box-shadow: 0 -8px 40px rgba(0,0,0,0.15), 0 -2px 8px rgba(0,0,0,0.06);
}

.dcms-menu-overlay.dcms-open .dcms-menu-sheet {
    transform: translateY(0);
}

.dcms-menu-overlay.dcms-animating-out .dcms-menu-sheet {
    transform: translateY(100%);
    transition: transform 0.32s cubic-bezier(0.55, 0, 1, 0.45);
}

/* ── Handle Bar ────────────────────────────────────────── */
.dcms-menu-handle {
    width: 40px;
    height: 4px;
    background: #cbd5e1;
    border-radius: 2px;
    margin: 10px auto 0;
    flex-shrink: 0;
    transform-origin: center;
}

.dcms-menu-overlay.dcms-open .dcms-menu-handle {
    animation: dcms-handle-wag 0.5s ease 0.1s both;
}

/* ── Header ────────────────────────────────────────────── */
.dcms-menu-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px 16px;
    border-bottom: 1px solid #f1f5f9;
    flex-shrink: 0;
    opacity: 0;
}

.dcms-menu-overlay.dcms-open .dcms-menu-header {
    animation: dcms-header-slide 0.35s cubic-bezier(0.22, 1, 0.36, 1) 0.12s both;
}

.dcms-menu-avatar {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    background: linear-gradient(135deg, #1e3a8a, #3b82f6);
    color: #fff;
    font-size: 16px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    letter-spacing: 0.5px;
    box-shadow: 0 4px 12px rgba(59,130,246,0.35);
}

.dcms-menu-user-info { flex: 1; min-width: 0; }

.dcms-menu-user-name {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dcms-menu-user-email {
    font-size: 12px;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: 1px;
}

/* ── Close Button (animated X) ─────────────────────────── */
.dcms-menu-close-btn {
    width: 32px;
    height: 32px;
    border: none;
    background: #f1f5f9;
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 5px;
    cursor: pointer;
    flex-shrink: 0;
    -webkit-tap-highlight-color: transparent;
    transition: background 0.15s ease, transform 0.15s ease;
    position: relative;
    overflow: hidden;
    padding: 0;
}

.dcms-menu-close-btn:active {
    transform: scale(0.9);
    background: #e2e8f0;
}

.dcms-close-x {
    display: block;
    width: 14px;
    height: 2px;
    background: #64748b;
    border-radius: 1px;
    transition: transform 0.25s cubic-bezier(0.22, 1, 0.36, 1);
    position: absolute;
}

.dcms-close-x-top { transform: rotate(45deg); }
.dcms-close-x-bot { transform: rotate(-45deg); }

/* ── Body ──────────────────────────────────────────────── */
.dcms-menu-body {
    overflow-y: auto;
    flex: 1;
    padding: 8px 0;
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
}

/* ── Groups ────────────────────────────────────────────── */
.dcms-menu-group {
    padding: 12px 20px 4px;
}

.dcms-menu-group-label {
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: 0.09em;
    color: #94a3b8;
    margin-bottom: 10px;
    padding-left: 2px;
}

/* ── Grid ──────────────────────────────────────────────── */
.dcms-menu-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    margin-bottom: 8px;
}

/* ── Menu Item ─────────────────────────────────────────── */
.dcms-menu-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 10px 4px 8px;
    border-radius: 16px;
    text-decoration: none;
    cursor: pointer;
    background: transparent;
    border: none;
    -webkit-tap-highlight-color: transparent;
    font-family: inherit;
    position: relative;
    overflow: hidden;
    /* Hidden by default; animated in when menu opens */
    opacity: 0;
    will-change: transform, opacity;
}

/* Staggered entrance animation via CSS variable --item-delay */
.dcms-menu-overlay.dcms-open .dcms-menu-item {
    animation: dcms-item-pop 0.48s cubic-bezier(0.22, 1, 0.36, 1) calc(0.18s + var(--item-delay, 0ms)) both;
}

/* Icon hover/focus glow */
.dcms-menu-item:focus-visible .dcms-menu-item-icon,
.dcms-menu-item:hover .dcms-menu-item-icon {
    filter: brightness(1.08) saturate(1.2);
    transform: scale(1.05);
}

/* Tap press effect */
.dcms-menu-item:active .dcms-menu-item-icon {
    animation: dcms-icon-bounce 0.3s ease forwards;
}

/* ── Icon ──────────────────────────────────────────────── */
.dcms-menu-item-icon {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: transform 0.18s cubic-bezier(0.22, 1, 0.36, 1),
                filter 0.18s ease;
    position: relative;
}

.dcms-menu-item-icon svg {
    width: 24px;
    height: 24px;
}

/* ── Label ─────────────────────────────────────────────── */
.dcms-menu-item-label {
    font-size: 10px;
    font-weight: 500;
    color: #334155;
    text-align: center;
    line-height: 1.25;
    /* Label panjang ("Tambah Dokumen", "Pusat Pengingat") turun ke 2 baris, tidak terpotong */
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    overflow-wrap: anywhere;
    max-width: 76px;
    min-height: 2.5em;
    transition: color 0.15s;
}

.dcms-menu-item:hover .dcms-menu-item-label {
    color: #0f172a;
    font-weight: 600;
}

/* ── Ripple ────────────────────────────────────────────── */
.dcms-ripple-circle {
    position: absolute;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.12);
    pointer-events: none;
    width: 40px;
    height: 40px;
    transform: scale(0);
    animation: dcms-ripple 0.5s ease-out forwards;
}

/* ── Logout button reset ───────────────────────────────── */
.dcms-menu-logout {
    width: 100%;
}
</style>

<script>
/* ═══════════════════════════════════════════════════════
   DCMS MOBILE MENU — Animation Controller
═══════════════════════════════════════════════════════ */

function dcmsOpenMenu() {
    const overlay = document.getElementById('dcms-mobile-menu-overlay');
    if (!overlay) return;

    // Reset item states so animation re-plays every time
    overlay.querySelectorAll('.dcms-menu-item').forEach(el => {
        el.style.animation = 'none';
        el.getBoundingClientRect(); // force reflow
        el.style.animation = '';
    });

    overlay.classList.remove('dcms-animating-out');
    overlay.classList.add('dcms-open');
    document.body.style.overflow = 'hidden';
    overlay.removeAttribute('aria-hidden');

    // Animate bottom bar hamburger → active state
    const menuBtn = document.querySelector('.dcms-mbb-menu-btn');
    if (menuBtn) menuBtn.classList.add('dcms-mbb-menu-open');
}

function dcmsCloseMenu() {
    const overlay = document.getElementById('dcms-mobile-menu-overlay');
    if (!overlay) return;

    overlay.classList.add('dcms-animating-out');

    const menuBtn = document.querySelector('.dcms-mbb-menu-btn');
    if (menuBtn) menuBtn.classList.remove('dcms-mbb-menu-open');

    setTimeout(() => {
        overlay.classList.remove('dcms-open', 'dcms-animating-out');
        document.body.style.overflow = '';
        overlay.setAttribute('aria-hidden', 'true');
    }, 320);
}

function dcmsHandleItemClick(e, el) {
    // Create ripple at tap position
    const rect = el.getBoundingClientRect();
    const touch = e.touches ? e.touches[0] : e;
    const x = (touch.clientX - rect.left) - 20;
    const y = (touch.clientY - rect.top)  - 20;

    const ripple = document.createElement('span');
    ripple.className = 'dcms-ripple-circle';
    ripple.style.left = x + 'px';
    ripple.style.top  = y + 'px';
    el.appendChild(ripple);

    setTimeout(() => ripple.remove(), 500);

    // Bounce the icon
    const icon = el.querySelector('.dcms-menu-item-icon');
    if (icon) {
        icon.style.animation = 'none';
        icon.getBoundingClientRect();
        icon.style.animation = 'dcms-icon-bounce 0.28s ease forwards';
    }

    // Delay navigation slightly for animation feel
    const href = el.getAttribute('href');
    if (href && href !== '#') {
        e.preventDefault();
        setTimeout(() => {
            dcmsCloseMenu();
            setTimeout(() => { window.location.href = href; }, 150);
        }, 80);
    }
}

// Close on ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') dcmsCloseMenu();
});
</script>
