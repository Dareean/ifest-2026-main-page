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
    <title>Verifikasi Email I-FEST 2026</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #F0F2F5;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        table { border-collapse: collapse; }
        .container { max-width: 600px; margin: 0 auto; }
        .body-content {
            background-color: #FFFFFF;
            padding: 40px 36px;
            border-radius: 12px 12px 0 0;
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
        .otp-box {
            background-color: #F8FAFC;
            border: 2px dashed #1E3A5F;
            padding: 24px;
            margin: 24px 0;
            border-radius: 12px;
            text-align: center;
        }
        .otp-code {
            font-size: 36px;
            font-weight: 800;
            letter-spacing: 8px;
            color: #1E3A5F;
            font-family: 'Courier New', monospace;
            margin: 8px 0;
        }
        .otp-expiry {
            font-size: 12px;
            color: #94A3B8;
            margin-top: 8px;
        }
        .warning-text {
            font-size: 12.5px;
            color: #FF3D8B;
            background-color: #FFF0F0;
            padding: 12px 16px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .signature {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #E2E8F0;
        }
        .signature p { margin: 0; font-size: 13.5px; line-height: 1.7; color: #475569; }
        .signature strong { color: #1E3A5F; }
        .footer {
            background-color: #F8FAFC;
            padding: 28px 36px;
            text-align: center;
            border-radius: 0 0 12px 12px;
            border-top: 1px solid #E2E8F0;
        }
        .footer-logos-table { margin: 0 auto 12px auto; border-collapse: collapse; }
        .footer-logos-table td { padding: 0 8px; vertical-align: middle; }
        .footer p { margin: 0; font-size: 11px; color: #94A3B8; line-height: 1.6; }
        @media only screen and (max-width: 480px) {
            .body-content { padding: 28px 20px; }
            .footer { padding: 20px; }
            .otp-code { font-size: 28px; letter-spacing: 6px; }
        }
    </style>
</head>
<body>
    <table class="container" width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width:600px;margin:24px auto;">
                    <tr>
                        <td class="body-content">
                            <p class="salutation">Halo, {{ $name }},</p>
                            <p class="body-text">
                                Terima kasih telah mendaftar di <strong>Informatics Festival (I-FEST) 2026</strong> yang diselenggarakan oleh Himpunan Mahasiswa Teknik Informatika (HMTI), Universitas Tadulako.
                            </p>
                            <p class="body-text">
                                Untuk menyelesaikan pendaftaran akun, silakan masukkan kode verifikasi berikut:
                            </p>

                            <div class="otp-box">
                                <p style="font-size:13px;color:#64748B;margin:0 0 4px 0;font-weight:600;">KODE VERIFIKASI</p>
                                <div class="otp-code">{{ $otp }}</div>
                                <p class="otp-expiry">Kode berlaku hingga {{ $expiresAt }}</p>
                            </div>

                            <div class="warning-text">
                                ⚠️ Jangan bagikan kode ini kepada siapa pun, termasuk pihak yang mengaku sebagai panitia.
                            </div>

                            <p class="body-text">
                                Jika Anda tidak merasa mendaftar di I-FEST 2026, abaikan email ini.
                            </p>

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
