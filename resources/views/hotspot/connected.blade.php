<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connected!</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
            background: #f0fdf4;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
            padding: 40px 32px;
            text-align: center;
            max-width: 360px;
            width: 100%;
        }
        .icon {
            width: 72px;
            height: 72px;
            background: #dcfce7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .icon svg { width: 40px; height: 40px; color: #16a34a; }
        h1 { font-size: 22px; font-weight: 700; color: #111827; margin-bottom: 8px; }
        p  { font-size: 14px; color: #6b7280; line-height: 1.6; margin-bottom: 24px; }
        .btn {
            display: inline-block;
            background: #2563eb;
            color: #fff;
            text-decoration: none;
            font-size: 15px;
            font-weight: 600;
            padding: 12px 28px;
            border-radius: 10px;
        }
        .ssid { font-weight: 600; color: #111827; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">
            <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
            </svg>
        </div>

        <h1>You're Connected!</h1>
        <p>You now have internet access.<br>You can close this window and start browsing.</p>

        <a href="https://hifastlink.com/dashboard" class="btn">Go to Dashboard</a>
    </div>
</body>
</html>
