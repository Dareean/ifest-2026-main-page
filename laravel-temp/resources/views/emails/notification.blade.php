<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $notification->judul }}</title>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #F8FAFC;
            color: #04000D;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
        }
        .header {
            background-color: #04000D;
            padding: 32px 24px;
            text-align: center;
        }
        .header img {
            height: 64px;
            width: auto;
            display: block;
            margin: 0 auto 12px auto;
        }
        .header-title {
            font-size: 14px;
            font-weight: 800;
            color: #DCEEB1;
            letter-spacing: 0.25em;
            text-transform: uppercase;
        }
        .content {
            padding: 40px 32px;
            font-family: 'Inter', -apple-system, sans-serif;
        }
        .content h2 {
            font-size: 18px;
            font-weight: 800;
            color: #04000D;
            margin-top: 0;
            margin-bottom: 18px;
        }
        .content p {
            font-size: 14px;
            line-height: 1.625;
            color: #475569;
            margin-bottom: 24px;
        }
        .info-box {
            background-color: #F8FAFC;
            border-left: 4px solid #FF3D8B;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            box-shadow: inset 0 1px 2px 0 rgba(0, 0, 0, 0.01);
        }
        .info-box h3 {
            margin: 0 0 8px 0;
            font-size: 14px;
            font-weight: 800;
            color: #04000D;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .info-box p {
            margin: 0;
            font-size: 13.5px;
            line-height: 1.6;
            color: #334155;
        }
        .button-container {
            margin: 32px 0;
            text-align: center;
        }
        .button {
            display: inline-block;
            background-color: #04000D;
            color: #DCEEB1 !important;
            text-decoration: none;
            padding: 14px 32px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.1em;
            border-radius: 12px;
            border: 2px solid #DCEEB1;
            text-transform: uppercase;
            box-shadow: 0 4px 10px rgba(4, 0, 13, 0.15);
            transition: all 0.2s ease;
        }
        .signature {
            margin-top: 32px;
            border-top: 1px solid #F1F5F9;
            padding-top: 24px;
        }
        .signature p {
            margin: 0;
            font-size: 13.5px;
            line-height: 1.6;
            color: #475569;
        }
        .footer {
            background-color: #F8FAFC;
            padding: 24px 32px;
            text-align: center;
            border-top: 1px solid #E2E8F0;
        }
        .footer p {
            margin: 0;
            font-size: 11px;
            color: #64748B;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <img src="{{ asset('logo-ifest.webp') }}" alt="I-FEST 2026" width="64" style="display:block;margin:0 auto 12px auto" />
            <div class="header-title">Informatics Festival 2026</div>
        </div>

        <!-- Content -->
        <div class="content">
            <h2>Yth. Sdr/i. {{ $notification->user->name }},</h2>
            <p>Kami menginformasikan bahwa terdapat pembaruan informasi resmi terkait keikutsertaan Anda dalam kegiatan <strong>Informatics Festival (I-FEST) 2026</strong> yang diselenggarakan oleh HMTI Universitas Tadulako.</p>
            
            <!-- Highlighted Info Box -->
            <div class="info-box">
                <h3>{{ $notification->judul }}</h3>
                <p>{{ $notification->pesan }}</p>
            </div>

            <p>Untuk melihat rincian informasi selengkapnya atau melakukan tindakan lebih lanjut, silakan masuk ke dashboard akun Anda melalui tombol di bawah ini:</p>

            <!-- Button CTA -->
            <div class="button-container">
                <a href="{{ env('FRONTEND_URL', 'http://localhost:5173') }}/dashboard" class="button">Buka Dashboard</a>
            </div>

            <!-- Signature -->
            <div class="signature">
                <p>Hormat kami,</p>
                <p style="margin-top: 6px;"><strong style="color: #04000D;">Panitia Pelaksana I-FEST 2026</strong></p>
                <p style="font-size: 12px; color: #64748B; margin-top: 2px;">Himpunan Mahasiswa Teknik Informatika (HMTI) UNTAD</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Email ini dikirimkan secara otomatis oleh sistem I-FEST 2026. Mohon untuk tidak membalas email ini secara langsung.</p>
            <p style="margin-top: 6px;">&copy; 2026 HMTI — Universitas Tadulako (UNTAD). All rights reserved.</p>
        </div>
    </div>
</body>
</html>
