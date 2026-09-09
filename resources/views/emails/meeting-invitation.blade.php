<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Undangan Rapat - DCMS</title>
    <style>
        /* ===== FORCE LIGHT MODE - semua email client ===== */
        :root { color-scheme: light only; }

        /* Apple Mail, iOS Mail, macOS Mail */
        @media (prefers-color-scheme: dark) {
            .body-wrap { background-color: #f1f5f9 !important; }
            .email-container { background-color: #ffffff !important; border-color: #e2e8f0 !important; }
            .body-td { background-color: #ffffff !important; }
            .footer-td { background-color: #f8fafc !important; }
            .detail-card { background-color: #f8fafc !important; border-color: #e2e8f0 !important; }
            .agenda-box { background-color: #ffffff !important; border-color: #e2e8f0 !important; }
            .divider-td { border-color: #e2e8f0 !important; }
            .logo-wrap { background-color: #ffffff !important; }

            /* Paksa semua teks kembali ke warna light mode */
            .txt-dark { color: #1e293b !important; }
            .txt-body { color: #475569 !important; }
            .txt-muted { color: #94a3b8 !important; }
            .txt-hint { color: #94a3b8 !important; }
            .txt-blue { color: #1e40af !important; }
            .txt-sub { color: #64748b !important; }
            .txt-footer { color: #475569 !important; }
            .txt-copyright { color: #cbd5e1 !important; }
        }

        /* ===== MOBILE ===== */
        @media only screen and (max-width: 620px) {
            .email-wrapper { padding: 16px 8px !important; }
            .email-container { width: 100% !important; border-radius: 8px !important; }
            .body-td { padding: 24px 20px !important; }
            .header-td { padding: 28px 20px !important; }
            .detail-card { width: 100% !important; }
            .footer-td { padding: 24px 20px !important; }
            .btn-cta {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
                text-align: center !important;
                padding: 14px 20px !important;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: 'Segoe UI', Helvetica, Arial, sans-serif; line-height: 1.6;">

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" class="email-wrapper body-wrap" style="background-color: #f1f5f9; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" class="email-container" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0;">

                    {{-- ===== HEADER ===== --}}
                    <tr>
                        <td align="center" class="header-td" style="background-color: #0b2545; padding: 36px 32px;">

                            {{-- Logo: background putih dipaksa agar tidak ikut dark mode --}}
                            <div class="logo-wrap" style="display: inline-block; background-color: #ffffff; border-radius: 10px; padding: 10px 20px; margin-bottom: 20px;">
                                <img src="{{ config('app.url') . '/images/logo.png?v=' . (@filemtime(public_path('images/logo.png')) ?: 1) }}"
                                     alt="Logo Syifa Global Group"
                                     style="max-height: 44px; width: auto; display: block;">
                            </div>

                            <div style="width: 48px; height: 2px; background-color: #3b82f6; margin: 0 auto 16px; border-radius: 2px;"></div>

                            <p style="margin: 0 0 8px 0; color: #93c5fd; font-size: 11px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase;">
                                DCMS &mdash; Document Control
                            </p>
                            <h1 style="margin: 0; color: #ffffff; font-size: 22px; font-weight: 700; letter-spacing: 0.3px;">
                                Undangan Rapat
                            </h1>

                        </td>
                    </tr>

                    {{-- ===== BODY ===== --}}
                    <tr>
                        <td class="body-td" style="padding: 36px 32px; background-color: #ffffff;">

                            <p style="margin: 0 0 6px 0; font-size: 16px; font-weight: 700; color: #1e293b;" class="txt-dark">Halo,</p>
                            <p style="margin: 0 0 28px 0; font-size: 14px; color: #475569; line-height: 1.7;" class="txt-body">
                                Anda diundang untuk menghadiri rapat penting yang telah dijadwalkan dalam sistem
                                <strong style="color: #1e293b;" class="txt-dark">DCMS Syifa Global Group</strong>.
                            </p>

                            {{-- ===== DETAIL CARD ===== --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom: 28px;">
                                <tr>
                                    <td align="center">
                                        <table role="presentation" width="480" cellspacing="0" cellpadding="0" border="0" class="detail-card"
                                               style="background-color: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0; border-left: 3px solid #3b82f6;">
                                            <tr>
                                                <td style="padding: 24px;">

                                                    {{-- Judul Rapat --}}
                                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom: 18px;">
                                                        <tr>
                                                            <td>
                                                                <p style="margin: 0 0 4px 0; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;" class="txt-muted">
                                                                    Judul Rapat
                                                                </p>
                                                                <p style="margin: 0; font-size: 16px; font-weight: 700; color: #1e40af; line-height: 1.4;" class="txt-blue">
                                                                    {{ $meeting->title }}
                                                                </p>
                                                            </td>
                                                        </tr>
                                                    </table>

                                                    {{-- Divider --}}
                                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom: 18px;">
                                                        <tr>
                                                            <td class="divider-td" style="border-top: 1px solid #e2e8f0; font-size: 0; line-height: 0;">&nbsp;</td>
                                                        </tr>
                                                    </table>

                                                    {{-- Waktu --}}
                                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom: 18px;">
                                                        <tr>
                                                            <td>
                                                                <p style="margin: 0 0 4px 0; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;" class="txt-muted">
                                                                    Waktu
                                                                </p>
                                                                <p style="margin: 0; font-size: 14px; font-weight: 700; color: #1e293b; line-height: 1.5;" class="txt-dark">
                                                                    {{ $meeting->date_time->translatedFormat('l, d F Y') }}
                                                                </p>
                                                                <p style="margin: 2px 0 0 0; font-size: 13px; color: #64748b;" class="txt-sub">
                                                                    Pukul {{ $meeting->date_time->format('H:i') }} WITA
                                                                </p>
                                                            </td>
                                                        </tr>
                                                    </table>

                                                    {{-- Lokasi --}}
                                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom: 18px;">
                                                        <tr>
                                                            <td>
                                                                <p style="margin: 0 0 4px 0; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;" class="txt-muted">
                                                                    Lokasi
                                                                </p>
                                                                <p style="margin: 0; font-size: 14px; color: #1e293b; line-height: 1.5;" class="txt-dark">
                                                                    {{ $meeting->location ?? 'Akan ditentukan kemudian' }}
                                                                </p>
                                                            </td>
                                                        </tr>
                                                    </table>

                                                    {{-- Agenda --}}
                                                    @if($meeting->agenda)
                                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                                        <tr>
                                                            <td class="divider-td" style="border-top: 1px solid #e2e8f0; padding-top: 18px;">
                                                                <p style="margin: 0 0 8px 0; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;" class="txt-muted">
                                                                    Agenda Utama
                                                                </p>
                                                                <div class="agenda-box" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 16px; font-size: 13px; color: #475569; line-height: 1.7;">
                                                                    {!! nl2br(e($meeting->agenda)) !!}
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                    @endif

                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            {{-- ===== END DETAIL CARD ===== --}}

                            <p style="font-size: 13px; color: #94a3b8; text-align: center; margin: 0 0 24px 0;" class="txt-hint">
                                Harap konfirmasi kehadiran Anda melalui portal admin.
                            </p>

                            {{-- CTA Button --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="center" style="text-align: center;">
                                        <a href="{{ url('/admin/meetings/' . $meeting->id) }}"
                                           class="btn-cta"
                                           style="display: inline-block; background-color: #1e40af; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 700; padding: 13px 36px; border-radius: 8px; letter-spacing: 0.3px; mso-padding-alt: 13px 36px; text-align: center;">
                                            Lihat Detail Rapat &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    {{-- ===== FOOTER ===== --}}
                    <tr>
                        <td class="footer-td" style="background-color: #f8fafc; padding: 28px 32px; text-align: center; border-top: 1px solid #e2e8f0;">
                            <p style="margin: 0 0 4px 0; font-size: 13px; font-weight: 700; color: #475569;" class="txt-footer">
                                Syifa Global Group
                            </p>
                            <p style="margin: 0 0 20px 0; font-size: 12px; color: #94a3b8; line-height: 1.6;" class="txt-hint">
                                JL. R.O Ulin No. 93, Kec Banjarbaru Selatan<br>
                                Kota Banjarbaru, Kalimantan Selatan, 70712
                            </p>
                            <div style="border-top: 1px dashed #e2e8f0; padding-top: 16px;">
                                <p style="margin: 0; font-size: 11px; color: #cbd5e1; letter-spacing: 0.5px; text-transform: uppercase;" class="txt-copyright">
                                    &copy; {{ date('Y') }} DCMS &mdash; Document Control Management System
                                </p>
                            </div>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>