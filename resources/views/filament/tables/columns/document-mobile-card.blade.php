@php
    $record = $getRecord();

    $statusLabel = match($record->status) {
        'draft'             => 'DRAFT',
        'pending_kabid'     => 'REVIEW KABID',
        'pending_direktur'  => 'REVIEW DIREKTUR',
        'approved'          => 'DISETUJUI',
        'rejected'          => 'DITOLAK',
        'archived'          => 'ARSIP',
        default             => strtoupper($record->status ?? 'DRAFT'),
    };

    $statusStyle = match($record->status) {
        'approved'                          => 'background:#ecfdf5;color:#047857;border-color:#6ee7b7;',
        'rejected'                          => 'background:#fff1f2;color:#be123c;border-color:#fca5a5;',
        'pending_kabid', 'pending_direktur' => 'background:#fffbeb;color:#b45309;border-color:#fcd34d;',
        default                             => 'background:#f8fafc;color:#475569;border-color:#cbd5e1;',
    };

    $code           = $record->code_number ?? ($record->category?->prefix ?? 'DOC');
    $categoryPrefix = $record->category?->prefix ?? '';
    $typeLabel = match($record->document_type) {
        'file'   => 'Berkas',
        'form'   => 'Formulir',
        'hybrid' => 'Gabungan',
        default  => 'Dokumen',
    };
    $deptName      = $record->department?->name ?? '—';
    $dateFormatted = $record->created_at ? $record->created_at->format('d/m/Y H:i') : '—';
    $hasProcess    = !empty(\App\Filament\Admin\Resources\Documents\Tables\DocumentsTable::getProcessOptions($record));
@endphp

<style>
    @media (min-width: 768px) {
        .dcms-mobile-card {
            display: none !important;
        }
        .dcms-desktop-title-view {
            display: flex !important;
        }
    }
    @media (max-width: 767.98px) {
        .dcms-desktop-title-view {
            display: none !important;
        }
        .dcms-mobile-card {
            display: block !important;
        }
    }
</style>

{{-- TAMPILAN DESKTOP (Sembunyi di mobile) --}}
<div class="dcms-desktop-title-view hidden md:flex flex-col gap-0.5 py-0.5">
    <div style="display:flex;align-items:center;gap:6px;">
        <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 6px;border-radius:6px;background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;font-family:ui-monospace,monospace;font-size:11px;font-weight:700;">
            {{ $code }}
        </span>
        <span style="font-size:13px;font-weight:700;color:#1e293b;">
            {{ $record->title }}
        </span>
    </div>
</div>

{{-- TAMPILAN MOBILE (Sembunyi di desktop) --}}
<div class="dcms-mobile-card md:hidden" style="width:100%;margin:4px 0;">
    <div style="width:100%;background:#ffffff;border:1px solid #cbd5e1;border-radius:14px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.05);box-sizing:border-box;">

        {{-- HEADER --}}
        <div style="background:#f0f4f9;padding:8px 12px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #e2e8f0;">
            <div style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:8px;background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;font-family:ui-monospace,monospace;font-size:11px;font-weight:700;letter-spacing:0.03em;">
                <svg style="width:12px;height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                {{ $code }}
            </div>
            <div style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:6px;border:1px solid;font-size:9px;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;{{ $statusStyle }}">{{ $statusLabel }}</div>
        </div>

        {{-- BODY --}}
        <div style="padding:12px;box-sizing:border-box;">
            <div style="margin-bottom:8px;">
                <div style="font-size:9px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;">SUBJEK / DOKUMEN</div>
                <div style="font-size:13px;font-weight:800;color:#1e293b;margin-top:2px;line-height:1.35;word-break:break-word;">{{ $record->title }}</div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px;">
                <div>
                    <div style="font-size:9px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;">KATEGORI / TIPE</div>
                    <div style="font-size:11px;font-weight:600;color:#334155;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $categoryPrefix ? $categoryPrefix.' · ' : '' }}{{ $typeLabel }}</div>
                </div>
                <div>
                    <div style="font-size:9px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;">DEPARTEMEN</div>
                    <div style="font-size:11px;font-weight:600;color:#334155;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $deptName }}</div>
                </div>
            </div>
            <div>
                <div style="font-size:9px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;">TANGGAL</div>
                <div style="font-size:11px;font-weight:500;color:#64748b;margin-top:2px;">{{ $dateFormatted }}</div>
            </div>
        </div>

        {{-- FOOTER --}}
        <div style="padding:8px 12px;background:#f8fafc;border-top:1px solid #f1f5f9;display:flex;align-items:center;justify-content:flex-end;gap:6px;flex-wrap:nowrap;white-space:nowrap;">
            @if($hasProcess)
                <button type="button" x-data="{}" x-on:click="$event.stopPropagation();$event.preventDefault();var row=$el.closest('tr');if(row){var btn=row.querySelector('[data-process-action]');if(btn){btn.click();return;}}$wire.mountTableAction('process','{{ $record->getKey() }}');" title="Proses Dokumen" style="display:inline-flex;align-items:center;justify-content:center;gap:4px;padding:4px 10px;height:30px;border-radius:9999px;border:1px solid #2563eb;color:#fff;background:#2563eb;font-size:11px;font-weight:700;cursor:pointer;position:relative;z-index:20;flex-shrink:0;">
                    <svg style="width:13px;height:13px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span>Proses</span>
                </button>
            @endif
            {{-- Bukan <a>: kolom ini sudah dibungkus link baris oleh Filament (link di dalam link merusak
                 layout kartu). Klik diteruskan ke link baris, yang membuka halaman detail. --}}
            <span title="Detail Dokumen" style="cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:4px;padding:4px 10px;height:30px;border-radius:9999px;border:1px solid #3b82f6;color:#2563eb;background:#fff;font-size:11px;font-weight:700;text-decoration:none;position:relative;z-index:20;flex-shrink:0;">
                <svg style="width:13px;height:13px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                <span>Detail</span>
            </span>

            @php
                $isBookmarked = (int) ($record->is_bookmarked ?? 0) > 0;
            @endphp
            <button type="button" x-data="{}" x-on:click="$event.stopPropagation();$event.preventDefault();$wire.mountTableAction('toggle_bookmark','{{ $record->getKey() }}');" title="{{ $isBookmarked ? 'Tersimpan' : 'Simpan Dokumen' }}" style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;padding:0;border-radius:9999px;border:1px solid {{ $isBookmarked ? '#f59e0b' : '#cbd5e1' }};color:{{ $isBookmarked ? '#b45309' : '#475569' }};background:{{ $isBookmarked ? '#fffbeb' : '#fff' }};font-size:11px;font-weight:700;cursor:pointer;position:relative;z-index:20;flex-shrink:0;">
                <svg style="width:14px;height:14px;{{ $isBookmarked ? 'fill:currentColor;' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
            </button>

            <button type="button" x-data="{}" x-on:click="$event.stopPropagation();$event.preventDefault();$wire.mountTableAction('convert','{{ $record->getKey() }}');" title="Konversi Format Dokumen" style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;padding:0;border-radius:9999px;border:1px solid #0284c7;color:#0284c7;background:#fff;font-size:11px;font-weight:700;cursor:pointer;position:relative;z-index:20;flex-shrink:0;">
                <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
            </button>
        </div>
    </div>
</div>