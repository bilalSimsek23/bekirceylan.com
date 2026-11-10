<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #172a36; margin: 24px; font-size: 12px; }
        header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #c9a15c; padding-bottom: 12px; margin-bottom: 18px; }
        header img { height: 54px; }
        h1 { font-size: 20px; margin: 0; color: #102b40; }
        h2 { font-size: 14px; color: #102b40; margin: 22px 0 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d9d4ca; padding: 5px 6px; text-align: left; vertical-align: top; }
        th { background: #102b40; color: #fff; font-weight: 600; font-size: 11px; }
        tr:nth-child(even) td { background: #faf8f3; }
        .right { text-align: right; }
        .meta { color: #6b7680; }
        .warn { color: #b3261e; font-weight: 700; }
        .totals td { font-weight: 700; background: #f3efe6 !important; }
        .toolbar { margin-bottom: 16px; }
        .toolbar button, .toolbar a { padding: 8px 14px; border-radius: 6px; border: 0; background: #102b40; color: #fff; cursor: pointer; text-decoration: none; font-size: 13px; margin-right: 6px; }
        .sign { display: flex; justify-content: space-between; margin-top: 60px; }
        .sign div { width: 40%; border-top: 1px solid #999; text-align: center; padding-top: 6px; }
        @media print { .toolbar { display: none; } body { margin: 0; } @page { size: A4 landscape; margin: 10mm; } }
    </style>
    @stack('style')
</head>
<body>
<div class="toolbar">
    <button onclick="window.print()">🖨 Yazdır / PDF</button>
    @yield('toolbar')
</div>
<header>
    <div>
        <h1>@yield('heading')</h1>
        <div class="meta">@yield('subheading')</div>
    </div>
    <img src="{{ asset('images/logo.png') }}" alt="logo">
</header>
@yield('content')
<p class="meta" style="margin-top:24px">{{ \App\Models\Setting::get('company_title') }} · TÜRSAB Belge No: {{ \App\Models\Setting::get('tursab_no') }} · {{ \App\Models\Setting::get('phone') }} · Çıktı: {{ now()->format('d.m.Y H:i') }}</p>
</body>
</html>
