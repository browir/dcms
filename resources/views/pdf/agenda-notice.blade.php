@php
    /** @var \App\Models\Agenda $agenda */
    $letter = $agenda->letterNumber;
    $logoPath = \App\Support\Branding::pdfLogoPath();
    $logo = is_file($logoPath) ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPath)) : null;
    $tanggalSurat = ($letter?->tanggal_surat ?? now())->locale('id')->isoFormat('D MMMM YYYY');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Surat Pemberitahuan {{ $letter?->nomor }}</title>
    <style>
        @page { margin: 40px 56px 60px; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11.5px; line-height: 1.55; color: #1f2937; }
        .kop { width: 100%; border-bottom: 3px double #0B2545; padding-bottom: 10px; margin-bottom: 22px; }
        .kop td { vertical-align: middle; }
        .kop-logo { width: 90px; }
        .kop-logo img { max-height: 64px; max-width: 86px; }
        .kop-nama { font-size: 16px; font-weight: bold; color: #0B2545; text-transform: uppercase; }
        .kop-unit { font-size: 11px; color: #475569; }
        .judul { text-align: center; font-weight: bold; font-size: 13px; text-decoration: underline; text-transform: uppercase; margin: 0; }
        .nomor { text-align: center; margin: 2px 0 20px; }
        .meta td { padding: 1px 0; vertical-align: top; }
        .meta td:first-child { width: 80px; }
        .isi { margin-top: 16px; white-space: pre-line; text-align: justify; }
        .ttd { margin-top: 40px; width: 240px; margin-left: auto; text-align: left; }
        .ttd .nama { margin-top: 64px; font-weight: bold; text-decoration: underline; }
        .footer { position: fixed; bottom: -36px; left: 0; right: 0; font-size: 8.5px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 4px; }
    </style>
</head>
<body>
    <table class="kop">
        <tr>
            @if ($logo)
                <td class="kop-logo"><img src="{{ $logo }}" alt=""></td>
            @endif
            <td>
                <div class="kop-nama">{{ $agenda->company?->name }}</div>
                <div class="kop-unit">{{ collect([$agenda->department?->name, $agenda->unit?->name])->filter()->implode(' · ') }}</div>
            </td>
        </tr>
    </table>

    <p class="judul">Surat Pemberitahuan</p>
    <p class="nomor">Nomor: {{ $letter?->nomor ?? '—' }}</p>

    <table class="meta">
        <tr><td>Perihal</td><td>: Pemberitahuan {{ $agenda->title }}</td></tr>
        <tr><td>Kepada</td><td>: Yth. {{ match ($agenda->audience) {
            \App\Models\Agenda::AUDIENCE_UNIT => 'Seluruh karyawan '.($agenda->unit?->name ?? 'unit'),
            \App\Models\Agenda::AUDIENCE_COMPANY => 'Seluruh karyawan '.$agenda->company?->name,
            default => 'Seluruh karyawan',
        } }}</td></tr>
        <tr><td>Di</td><td>: Tempat</td></tr>
    </table>

    <div class="isi">{{ $agenda->notice_body ?: \App\Services\AgendaAnnouncer::defaultNoticeBody($agenda) }}</div>

    <div class="ttd">
        <div>{{ $tanggalSurat }}</div>
        <div>{{ $agenda->organizerName() }}</div>
        <div class="nama">{{ $agenda->creator?->name ?? '..........................' }}</div>
    </div>

    <div class="footer">
        Dokumen dibuat melalui DCMS · {{ $letter?->nomor }} · dicetak {{ now()->locale('id')->isoFormat('D MMM YYYY HH.mm') }}
    </div>
</body>
</html>
