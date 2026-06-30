<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $notification->judul }}</title>
    <style>
        body {
            font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #FAFAFA;
            color: #04000D;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #FFFFFF;
            border: 1px solid #EAEAEA;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
        }
        .header {
            background-color: #04000D;
            padding: 32px;
            text-align: center;
        }
        .header h1 {
            color: #DCEEB1;
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 1px;
            font-family: 'Courier New', Courier, monospace;
        }
        .content {
            padding: 40px 32px;
        }
        .content h2 {
            font-size: 20px;
            font-weight: 700;
            margin-top: 0;
            color: #04000D;
        }
        .content p {
            font-size: 15px;
            line-height: 1.6;
            color: #4A4A4A;
            margin-bottom: 24px;
        }
        .button-container {
            margin: 32px 0 16px;
            text-align: center;
        }
        .button {
            display: inline-block;
            background-color: #04000D;
            color: #DCEEB1 !important;
            text-decoration: none;
            padding: 12px 32px;
            font-size: 14px;
            font-weight: bold;
            border-radius: 12px;
            font-family: monospace;
            border: 1px solid #DCEEB1;
            transition: all 0.2s ease;
        }
        .footer {
            background-color: #F5F5F5;
            padding: 24px 32px;
            text-align: center;
            border-top: 1px solid #EAEAEA;
        }
        .footer p {
            margin: 0;
            font-size: 12px;
            color: #8A8A8A;
            line-height: 1.5;
        }
        .footer a {
            color: #04000D;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>I-FEST 2026</h1>
        </div>

        <!-- Content -->
        <div class="content">
            <h2>Halo, {{ $notification->user->name }}!</h2>
            <p>Ada informasi baru terkait keikutsertaanmu di I-FEST 2026:</p>
            
            <div style="background-color: #F9FAFB; border-left: 4px solid #04000D; padding: 16px 20px; border-radius: 4px; margin-bottom: 24px;">
                <h4 style="margin: 0 0 8px 0; font-size: 15px; color: #04000D;">{{ $notification->judul }}</h4>
                <p style="margin: 0; font-size: 14px; color: #555555; line-height: 1.5;">{{ $notification->pesan }}</p>
            </div>

            <p>Silakan kunjungi dashboard akunmu untuk informasi lebih lengkap mengenai kompetisi ini.</p>

            <!-- Button -->
            <div class="button-container">
                <a href="{{ env('FRONTEND_URL', 'http://localhost:5173') }}/dashboard" class="button">BUKA DASHBOARD</a>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Email ini dikirim secara otomatis oleh sistem I-FEST 2026.</p>
            <p style="margin-top: 8px;">&copy; 2026 HMTI - Universitas Yogyakarta. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
