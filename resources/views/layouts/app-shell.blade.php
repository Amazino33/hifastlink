<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#0a0e1a">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>HiFastLink</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700&family=Outfit:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { height: 100%; background: #0a0e1a; }
        body {
            font-family: 'Outfit', sans-serif;
            background: #0a0e1a;
            color: #e8eeff;
            min-height: 100%;
            min-height: 100dvh;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }
        /* Safe-area padding for notched phones */
        .safe-top    { padding-top: env(safe-area-inset-top, 0px); }
        .safe-bottom { padding-bottom: env(safe-area-inset-bottom, 0px); }
    </style>
</head>
<body>
    {{ $slot }}

    @livewireScripts
</body>
</html>
