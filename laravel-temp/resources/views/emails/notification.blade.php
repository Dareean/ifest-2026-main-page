@php
    $isLocal = config('app.env') === 'local' || \Illuminate\Support\Str::contains(config('app.url'), 'loca.lt');
    $untadUrl = $isLocal 
        ? 'https://raw.githubusercontent.com/Dareean/ifest-2026-main-page/main/src/assets/logo_utama/logo_utama_png/logo_untad.png' 
        : asset('logo-untad.png');
    $hmtiUrl = $isLocal 
        ? 'https://raw.githubusercontent.com/Dareean/ifest-2026-main-page/main/src/assets/logo_utama/logo_utama_png/HMTI%20LOGO.png' 
        : asset('logo-hmti.png');
    $ifestUrl = $isLocal 
        ? 'https://raw.githubusercontent.com/Dareean/ifest-2026-main-page/main/src/assets/logo_utama/logo_utama_png/Logo-IFEST-2026.png' 
        : asset('logo-ifest.png');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $notification->judul }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #F0F2F5;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        table {
            border-collapse: collapse;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        .header {
            background-color: #1E3A5F;
            padding: 36px 32px 28px;
            text-align: center;
            border-radius: 12px 12px 0 0;
        }
        .header-logos-table {
            margin: 0 auto 16px auto;
            border-collapse: collapse;
        }
        .header-logos-table td {
            padding: 0 12px;
            vertical-align: middle;
        }
        .header-title {
            font-size: 13px;
            font-weight: 600;
            color: #94A3B8;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .body-content {
            background-color: #FFFFFF;
            padding: 40px 36px;
        }
        .salutation {
            font-size: 15px;
            font-weight: 600;
            color: #1E293B;
            margin: 0 0 6px 0;
        }
        .body-text {
            font-size: 14px;
            line-height: 1.7;
            color: #475569;
            margin: 0 0 20px 0;
        }
        .info-box {
            background-color: #F8FAFC;
            border-left: 4px solid #FF3D8B;
            padding: 16px 20px;
            margin: 24px 0;
            border-radius: 6px;
        }
        .info-box h3 {
            margin: 0 0 6px 0;
            font-size: 14px;
            font-weight: 700;
            color: #1E3A5F;
        }
        .info-box p {
            margin: 0;
            font-size: 13.5px;
            line-height: 1.6;
            color: #475569;
        }
        .button-wrap {
            text-align: center;
            margin: 32px 0;
        }
        .button {
            display: inline-block;
            background-color: #1E3A5F;
            color: #FFFFFF !important;
            text-decoration: none;
            padding: 13px 36px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 8px;
            letter-spacing: 0.03em;
        }
        .signature {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #E2E8F0;
        }
        .signature p {
            margin: 0;
            font-size: 13.5px;
            line-height: 1.7;
            color: #475569;
        }
        .signature strong {
            color: #1E3A5F;
        }
        .footer {
            background-color: #F8FAFC;
            padding: 28px 36px;
            text-align: center;
            border-radius: 0 0 12px 12px;
            border-top: 1px solid #E2E8F0;
        }
        .footer-logos-table {
            margin: 0 auto 12px auto;
            border-collapse: collapse;
        }
        .footer-logos-table td {
            padding: 0 8px;
            vertical-align: middle;
        }
        .footer p {
            margin: 0;
            font-size: 11px;
            color: #94A3B8;
            line-height: 1.6;
        }
        @@media only screen and (max-width: 480px) {
            .header { padding: 28px 20px 22px; }
            .body-content { padding: 28px 20px; }
            .footer { padding: 20px; }
        }
    </style>
</head>
<body>
    <table class="container" width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width:600px;margin:24px auto;">
                    <tr>
                        <td class="header" align="center">
                            <div class="header-title">Informatics Festival 2026</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="body-content">
                            <p class="salutation">Yth. {{ $notification->user->name }},</p>
                            <p class="body-text">
                                Kami informasikan bahwa terdapat pembaruan resmi terkait keikutsertaan Anda dalam kegiatan <strong>Informatics Festival (I-FEST) 2026</strong> yang diselenggarakan oleh Himpunan Mahasiswa Teknik Informatika (HMTI), Universitas Tadulako.
                            </p>

                            <div class="info-box">
                                <h3>{{ $notification->judul }}</h3>
                                <p>{{ $notification->pesan }}</p>
                            </div>

                            <p class="body-text">
                                Silakan buka dashboard akun Anda untuk melihat rincian selengkapnya atau melakukan tindakan lebih lanjut.
                            </p>

                            <div class="button-wrap">
                                <a href="{{ env('FRONTEND_URL', 'http://localhost:5173') }}/dashboard" class="button">Buka Dashboard</a>
                            </div>

                            <div class="signature">
                                <p>Hormat kami,</p>
                                <p style="margin-top:4px;"><strong>Panitia Pelaksana I-FEST 2026</strong></p>
                                <p style="font-size:12px;color:#64748B;margin-top:2px;">HMTI — Universitas Tadulako</p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="footer" align="center">
                            <table border="0" cellpadding="0" cellspacing="0" align="center" class="footer-logos-table">
                                <tr>
                                    <td style="opacity:0.6;">
                                        <img src="{{ $untadUrl }}" alt="UNTAD" width="27" height="28" style="width:27px;height:28px;display:block;border:0;outline:none;opacity:0.6;" />
                                    </td>
                                    <td style="opacity:0.6;">
                                        <img src="{{ $hmtiUrl }}" alt="HMTI" width="28" height="28" style="width:28px;height:28px;display:block;border:0;outline:none;opacity:0.6;" />
                                    </td>
                                    <td style="opacity:0.6;">
                                        <img src="{{ $ifestUrl }}" alt="I-FEST 2026" width="50" height="28" style="width:50px;height:28px;display:block;border:0;outline:none;opacity:0.6;" />
                                    </td>
                                </tr>
                            </table>
                            <p>Email ini dikirim secara otomatis oleh sistem I-FEST 2026. Mohon tidak membalas email ini.</p>
                            <p style="margin-top:4px;">&copy; 2026 HMTI — Universitas Tadulako. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>