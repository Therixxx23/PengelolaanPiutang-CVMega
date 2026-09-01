<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>419 — Sistem Piutang</title>
    <style>
        body { font-family: Inter, sans-serif; background: #F6F7F6;
               display: flex; align-items: center;
               justify-content: center; min-height: 100vh; margin: 0; }
        .box { text-align: center; padding: 40px; }
        .code { font-size: 72px; font-weight: 700; color: #DCE2E0;
                font-family: 'IBM Plex Mono', monospace; }
        .msg { font-size: 18px; color: #1B2027; margin: 8px 0; }
        .sub { font-size: 14px; color: #5B6470; margin-bottom: 24px; }
        a { color: #0E6E66; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>
    <div class="box">
        <div class="code">419</div>
        <p class="msg">Sesi Anda telah berakhir</p>
        <p class="sub">Sesi Anda telah berakhir. Silakan muat ulang halaman.</p>
        <a href="{{ url('/') }}">← Kembali ke Dashboard</a>
    </div>
</body>
</html>