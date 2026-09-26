<x-filament-widgets::widget>
    <div class="mcw-outer">

        {{-- ════════════════════════════════════════════════════════
         ACCORDION PANELS WRAPPER
         Desktop  → side-by-side flex + CSS-only hover accordion
         Mobile   → tetap vertikal (stack)
         NOTE: Zero JavaScript — pure CSS :hover + :has()
    ════════════════════════════════════════════════════════ --}}
        <div class="mcw-panels">

            {{-- ── Panel 1: Jadwal Rapat Hari Ini ────────────────── --}}
            <div class="mcw-panel">

                <div class="mcw-panel-header">
                    <div class="mcw-panel-title-row">
                        <span class="mcw-panel-icon mcw-icon-today">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="16" height="16" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                            </svg>
                        </span>
                        <h3 class="mcw-panel-title">Jadwal Rapat Hari Ini</h3>
                        <span class="mcw-badge">{{ $todayMeetings->count() }}</span>
                    </div>
                </div>

                <div class="mcw-panel-body">
                    @if ($todayMeetings->isNotEmpty())
                    <div class="mcw-table-wrap">
                        <table class="mcw-table">
                            <thead>
                                <tr>
                                    <th class="col-title">JUDUL RAPAT</th>
                                    <th class="col-time">WAKTU</th>
                                    <th class="col-loc">LOKASI</th>
                                    <th class="col-status">STATUS</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($todayMeetings as $meeting)
                                <tr onclick="window.location.href='{{ \App\Filament\Admin\Resources\Meetings\MeetingResource::getUrl('view', ['record' => $meeting->id]) }}'">
                                    <td class="col-title">
                                        <span class="mcw-cell-text" title="{{ $meeting->title }}">
                                            {{ Str::limit($meeting->title, 35) }}
                                        </span>
                                    </td>
                                    <td class="col-time">
                                        {{ \Carbon\Carbon::parse($meeting->date_time)->format('H:i') }}
                                    </td>
                                    <td class="col-loc">
                                        <span class="mcw-cell-text" title="{{ $meeting->location }}">
                                            {{ Str::limit($meeting->location, 20) }}
                                        </span>
                                    </td>
                                    <td class="col-status">
                                        @php
                                        $statusMap = [
                                        'pending' => ['label' => 'Pending', 'cls' => 'badge-warning'],
                                        'ongoing' => ['label' => 'Berlangsung', 'cls' => 'badge-primary'],
                                        'completed' => ['label' => 'Selesai', 'cls' => 'badge-gray'],
                                        'cancelled' => ['label' => 'Dibatalkan', 'cls' => 'badge-danger'],
                                        ];
                                        $s = $statusMap[$meeting->status] ?? ['label' => $meeting->status, 'cls' => 'badge-gray'];
                                        @endphp
                                        <span class="mcw-badge-status {{ $s['cls'] }}">{{ $s['label'] }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="mcw-empty">
                        <span class="mcw-empty-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="28" height="28" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </span>
                        <p class="mcw-empty-text">Tidak ada data yang ditemukan</p>
                    </div>
                    @endif
                </div>

                <div class="mcw-panel-footer">
                    <span class="mcw-footer-note">{{ $todayMeetings->count() }} rapat hari ini</span>
                </div>
            </div>

            {{-- ── Panel 2: Undangan Rapat Mendatang ─────────────── --}}
            <div class="mcw-panel">

                <div class="mcw-panel-header">
                    <div class="mcw-panel-title-row">
                        <span class="mcw-panel-icon mcw-icon-invited">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="16" height="16" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                        </span>
                        <h3 class="mcw-panel-title">Undangan Rapat Mendatang</h3>
                        <span class="mcw-badge">{{ $invitedMeetings->count() }}</span>
                    </div>
                </div>

                <div class="mcw-panel-body">
                    @if ($invitedMeetings->isNotEmpty())
                    <div class="mcw-table-wrap">
                        <table class="mcw-table">
                            <thead>
                                <tr>
                                    <th class="col-title">JUDUL RAPAT</th>
                                    <th class="col-time">WAKTU</th>
                                    <th class="col-loc">LOKASI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invitedMeetings as $meeting)
                                <tr onclick="window.location.href='{{ \App\Filament\Admin\Resources\Meetings\MeetingResource::getUrl('view', ['record' => $meeting->id]) }}'">
                                    <td class="col-title">
                                        <span class="mcw-cell-text" title="{{ $meeting->title }}">
                                            {{ Str::limit($meeting->title, 35) }}
                                        </span>
                                    </td>
                                    <td class="col-time">
                                        {{ \Carbon\Carbon::parse($meeting->date_time)->format('d M Y, H:i') }}
                                    </td>
                                    <td class="col-loc">
                                        <span class="mcw-cell-text" title="{{ $meeting->location }}">
                                            {{ Str::limit($meeting->location, 20) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="mcw-empty">
                        <span class="mcw-empty-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="28" height="28" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </span>
                        <p class="mcw-empty-text">Tidak ada data yang ditemukan</p>
                    </div>
                    @endif
                </div>

                <div class="mcw-panel-footer">
                    <span class="mcw-footer-note">{{ $invitedMeetings->count() }} undangan mendatang</span>
                </div>
            </div>

        </div>{{-- /mcw-panels --}}

    </div>{{-- /mcw-outer --}}

    {{-- ════════════════════════════════════════════════════════════════════
     STYLES — Pure CSS, zero JavaScript
     Hover accordion menggunakan CSS :hover + :has() native browser
     Tidak bergantung JS = tidak ada masalah Livewire timing/morph
════════════════════════════════════════════════════════════════════ --}}
    <style>
        /* ── Reset: pastikan parent wrapper tidak membatasi layout ─────────── */
        .mcw-outer {
            width: 100%;
            /* Paksa background putih pada wrapper luar supaya tidak mewarisi
       warna apapun dari Filament fi-wi-widget */
            background: transparent;
        }

        /* ── Panels container — mobile default: vertikal ───────────────────── */
        .mcw-panels {
            display: flex;
            flex-direction: column;
            gap: 16px;
            width: 100%;
        }

        /* ══════════════════════════════════════════════════════════════════════
   INDIVIDUAL PANEL — base style (berlaku di semua breakpoint)
══════════════════════════════════════════════════════════════════════ */
        .mcw-panel {
            background-color: #ffffff;
            border-radius: 12px;
            border: 1px solid rgba(0, 0, 0, 0.07);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06), 0 1px 2px rgba(0, 0, 0, 0.04);

            /*
     * TRANSISI LENGKAP.
     * - width       → properti utama accordion (lebih reliable dari flex-grow)
     * - box-shadow  → efek "terangkat"
     * - transform   → scale halus
     * - border-color→ border aksen biru
     * - opacity     → dim panel pasif
     */
            transition:
                width 0.40s cubic-bezier(0.4, 0, 0.2, 1),
                flex-grow 0.40s cubic-bezier(0.4, 0, 0.2, 1),
                box-shadow 0.40s cubic-bezier(0.4, 0, 0.2, 1),
                transform 0.40s cubic-bezier(0.4, 0, 0.2, 1),
                border-color 0.40s cubic-bezier(0.4, 0, 0.2, 1),
                opacity 0.40s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* ── Header ────────────────────────────────────────────────────────── */
        .mcw-panel-header {
            padding: 16px 20px 12px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
            background-color: #ffffff;
            flex-shrink: 0;
        }

        .mcw-panel-title-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .mcw-panel-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            flex-shrink: 0;
            transition: background-color 0.40s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .mcw-icon-today {
            background-color: rgba(59, 130, 246, 0.10);
            color: #3b82f6;
        }

        .mcw-icon-invited {
            background-color: rgba(139, 92, 246, 0.10);
            color: #8b5cf6;
        }

        .mcw-panel-title {
            font-size: 14px;
            font-weight: 600;
            color: #111827;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
            transition: font-size 0.40s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .mcw-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 22px;
            height: 22px;
            padding: 0 6px;
            border-radius: 99px;
            background-color: rgba(59, 130, 246, 0.12);
            color: #3b82f6;
            font-size: 11px;
            font-weight: 700;
            flex-shrink: 0;
            transition:
                background-color 0.40s cubic-bezier(0.4, 0, 0.2, 1),
                color 0.40s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* ── Body ──────────────────────────────────────────────────────────── */
        .mcw-panel-body {
            flex: 1;
            overflow: auto;
            transition: opacity 0.40s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* ── Table ─────────────────────────────────────────────────────────── */
        .mcw-table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .mcw-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            color: #374151;
        }

        .mcw-table thead tr {
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        }

        .mcw-table th {
            padding: 10px 14px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.05em;
            color: #3b82f6;
            white-space: nowrap;
            transition: padding 0.40s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .mcw-table td {
            padding: 13px 14px; /* Disesuaikan agar touch target height ~44px */
            border-bottom: 1px solid rgba(0, 0, 0, 0.04);
            vertical-align: middle;
            transition: padding 0.40s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .mcw-table tbody tr {
            cursor: pointer;
            transition: background-color 0.2s ease;
            -webkit-tap-highlight-color: transparent; /* Hilangkan kotak biru default saat tap di mobile */
        }

        .mcw-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Hover untuk Desktop */
        @media (hover: hover) {
            .mcw-table tbody tr:hover {
                background-color: rgba(0, 0, 0, 0.04);
            }
            .dark .mcw-table tbody tr:hover {
                background-color: rgba(255, 255, 255, 0.05);
            }
        }

        /* Active untuk Mobile (feedback visual saat ditap) */
        .mcw-table tbody tr:active {
            background-color: rgba(0, 0, 0, 0.08); /* Sedikit lebih gelap dari hover agar terasa dipencet */
        }
        .dark .mcw-table tbody tr:active {
            background-color: rgba(255, 255, 255, 0.08);
        }

        .mcw-cell-text {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 200px;
        }

        .col-title {
            width: 40%;
            min-width: 80px;
        }

        .col-time {
            width: 25%;
            min-width: 60px;
            white-space: nowrap;
        }

        .col-loc {
            width: 20%;
            min-width: 60px;
        }

        .col-status {
            width: 15%;
            min-width: 70px;
        }

        /* ── Status badges ─────────────────────────────────────────────────── */
        .mcw-badge-status {
            display: inline-flex;
            align-items: center;
            padding: 2px 10px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-warning {
            background-color: rgba(245, 158, 11, 0.12);
            color: #b45309;
        }

        .badge-primary {
            background-color: rgba(59, 130, 246, 0.12);
            color: #1d4ed8;
        }

        .badge-gray {
            background-color: rgba(107, 114, 128, 0.12);
            color: #4b5563;
        }

        .badge-danger {
            background-color: rgba(239, 68, 68, 0.12);
            color: #b91c1c;
        }

        /* ── Empty state ───────────────────────────────────────────────────── */
        .mcw-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            gap: 12px;
        }

        .mcw-empty-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background-color: rgba(107, 114, 128, 0.08);
            color: #9ca3af;
        }

        .mcw-empty-text {
            font-size: 13px;
            font-weight: 500;
            color: #6b7280;
            margin: 0;
        }

        /* ── Footer ────────────────────────────────────────────────────────── */
        .mcw-panel-footer {
            padding: 10px 20px;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            background-color: #f8fafc;
            flex-shrink: 0;
            transition: opacity 0.40s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .mcw-footer-note {
            font-size: 12px;
            color: #9ca3af;
        }


        /* ══════════════════════════════════════════════════════════════════════
   DESKTOP — min-width: 1024px
   Side-by-side layout + PURE CSS HOVER ACCORDION
   TIDAK ada JavaScript — menggunakan :hover dan :has() native CSS
══════════════════════════════════════════════════════════════════════ */
        @media (min-width: 1024px) {

            /* ── Container: row layout ─────────────────────────────────────── */
            .mcw-panels {
                flex-direction: row;
                gap: 20px;
                align-items: stretch;
            }

            /* ── Panel default: 50/50 menggunakan flex-grow ────────────────── */
            .mcw-panel {
                flex-grow: 1;
                flex-shrink: 1;
                flex-basis: 0%;
                min-width: 0;
                /* wajib agar panel bisa menyempit */
                position: relative;
            }

            /* ──────────────────────────────────────────────────────────────────
       HOVER ACCORDION — CSS-ONLY menggunakan :hover dan :has()

       :has() selector: didukung semua browser modern (Chrome 105+,
       Edge 105+, Safari 15.4+, Firefox 121+). Memungkinkan parent
       bereaksi terhadap hover pada salah satu child-nya.
    ────────────────────────────────────────────────────────────────── */

            /* Panel yang sedang di-hover → MEMBESAR (flex-grow naik ke 1.5) */
            .mcw-panel:hover {
                flex-grow: 1.5;
                box-shadow:
                    0 12px 40px rgba(59, 130, 246, 0.20),
                    0 4px 16px rgba(0, 0, 0, 0.10);
                transform: scale(1.02);
                border-color: rgba(59, 130, 246, 0.40);
                z-index: 2;
            }

            /* Saat ada panel yang di-hover, panel LAIN → MENGECIL (flex-grow turun ke 0.5)
       Menggunakan :has() — container bereaksi terhadap hover di salah satu child */
            .mcw-panels:has(.mcw-panel:hover) .mcw-panel:not(:hover) {
                flex-grow: 0.5;
            }

            /* Dim konten panel yang mengecil */
            .mcw-panels:has(.mcw-panel:hover) .mcw-panel:not(:hover) .mcw-panel-body,
            .mcw-panels:has(.mcw-panel:hover) .mcw-panel:not(:hover) .mcw-panel-footer {
                opacity: 0.60;
            }

            /* Padding tabel dikurangi saat panel mengecil agar konten tetap muat */
            .mcw-panels:has(.mcw-panel:hover) .mcw-panel:not(:hover) .mcw-table th,
            .mcw-panels:has(.mcw-panel:hover) .mcw-panel:not(:hover) .mcw-table td {
                padding: 8px 10px;
            }

            /* Sembunyikan kolom kurang penting saat panel menyempit */
            .mcw-panels:has(.mcw-panel:hover) .mcw-panel:not(:hover) .col-loc,
            .mcw-panels:has(.mcw-panel:hover) .mcw-panel:not(:hover) .col-status {
                display: none;
            }

            /* Badge angka menjadi solid biru saat panel aktif (di-hover) */
            .mcw-panel:hover .mcw-badge {
                background-color: #3b82f6;
                color: #ffffff;
            }

            /* Judul panel sedikit lebih besar saat aktif */
            .mcw-panel:hover .mcw-panel-title {
                font-size: 15px;
            }
        }


        /* ══════════════════════════════════════════════════════════════════════
   FALLBACK untuk browser lama yang tidak mendukung :has()
   (Firefox < 121, Chrome < 105)
   Pada browser ini, hanya panel yang di-hover yang membesar,
   tapi panel lain TIDAK mengecil (tidak ada dim effect).
   Layout side-by-side tetap terjaga.
══════════════════════════════════════════════════════════════════════ */
        @supports not selector(:has(*)) {
            @media (min-width: 1024px) {

                /* Panel yang di-hover tetap membesar — ini universal */
                .mcw-panel:hover {
                    flex-grow: 1.5;
                }

                /* Panel lain tidak bisa dikecilkan tanpa :has(),
           tapi flex tetap bekerja secara proporsional */
            }
        }
    </style>

</x-filament-widgets::widget>