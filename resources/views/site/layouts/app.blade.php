<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $settings['site_title'].' | '.$settings['site_tagline'])</title>
    <meta name="description" content="@yield('description', $settings['meta_description'])">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', $settings['site_title'])">
    <meta property="og:description" content="@yield('description', $settings['meta_description'])">
    <meta property="og:image" content="@yield('og_image', asset('images/logo.png'))">
    <meta property="og:locale" content="tr_TR">
    <link rel="icon" href="{{ asset('images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Nunito+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v={{ filemtime(public_path('css/site.css')) }}">
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'TravelAgency',
        'name' => $settings['site_title'],
        'url' => url('/'),
        'logo' => asset('images/logo.png'),
        'telephone' => $settings['phone'],
        'email' => $settings['email'],
        'sameAs' => array_values(array_filter([$settings['instagram'], $settings['facebook'], $settings['youtube']])),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
    @stack('head')
</head>
<body>
<header class="site-header" id="anasayfa">
    <div class="container header-inner">
        <a href="{{ route('home') }}" class="logo" aria-label="{{ $settings['site_title'] }} ana sayfa">
            <img src="{{ asset('images/logo.png') }}" alt="{{ $settings['site_title'] }} logosu" width="140" height="56">
        </a>

        @if($fx && isset($fx['rates']['USD']))
            <div class="fx-box" title="Döviz satış kuru">
                <strong>1 USD = {{ number_format($fx['rates']['USD'], 4, ',', '.') }} TL</strong>
                <small>Kaynak: {{ $fx['source'] ?? '' }} {{ $fx['fetched_at'] }}</small>
            </div>
        @endif

        <button class="nav-toggle" type="button" aria-label="Menüyü aç" aria-expanded="false" data-nav-toggle>☰</button>

        <nav class="main-nav" data-nav>
            <a href="{{ route('home') }}">Ana Sayfa</a>
            <a href="{{ route('tours.index') }}">Turlarımız</a>
            <a href="{{ route('home') }}#hakkimizda">Hakkımızda</a>
            <a href="{{ route('home') }}#iletisim">İletişim</a>
            <a href="{{ route('home') }}#iletisim" class="btn btn-gold btn-sm">Bilgi Al</a>
            <a href="{{ $whatsappUrl }}" class="btn btn-whatsapp btn-sm" target="_blank" rel="noopener">
                @include('site.partials.whatsapp-icon') WhatsApp · Bilgi Al
            </a>
        </nav>
    </div>
</header>

<main>
    @yield('content')
</main>

<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <img src="{{ asset('images/logo.png') }}" alt="{{ $settings['site_title'] }}" class="footer-logo" width="150" height="60" loading="lazy">
            <p class="muted">Hac • Umre • Kültür &amp; Gezi</p>
        </div>
        <div>
            <h4>TÜRSAB Belge Bilgileri</h4>
            <p>{{ $settings['company_title'] }}<br>TÜRSAB Belge No: <strong>{{ $settings['tursab_no'] }}</strong></p>
            <a href="https://www.tursab.org.tr/tr/ddsv" target="_blank" rel="noopener" class="link-gold">Belge Sorgula →</a>
        </div>
        <div>
            <h4>İletişim</h4>
            <p>
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener">📱 WhatsApp: {{ $settings['phone'] }}</a><br>
                <a href="mailto:{{ $settings['email'] }}">✉ {{ $settings['email'] }}</a>
                @if($settings['address'])<br><span>📍 {{ $settings['address'] }}</span>@endif
            </p>
        </div>
        <div>
            <h4>Bizi Takip Edin</h4>
            <div class="socials">
                @if($settings['instagram'])<a href="{{ $settings['instagram'] }}" target="_blank" rel="noopener">Instagram</a>@endif
                @if($settings['facebook'])<a href="{{ $settings['facebook'] }}" target="_blank" rel="noopener">Facebook</a>@endif
                @if($settings['youtube'])<a href="{{ $settings['youtube'] }}" target="_blank" rel="noopener">YouTube</a>@endif
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener">WhatsApp</a>
            </div>
        </div>
    </div>
    <div class="container footer-bottom">
        <span>© {{ date('Y') }} {{ $settings['site_title'] }}. Tüm hakları saklıdır.</span>
    </div>
</footer>

<a href="{{ $whatsappUrl }}" class="whatsapp-float" target="_blank" rel="noopener" aria-label="WhatsApp ile yazın">
    @include('site.partials.whatsapp-icon')
</a>

<script src="{{ asset('js/site.js') }}?v={{ filemtime(public_path('js/site.js')) }}" defer></script>
@stack('scripts')
</body>
</html>
