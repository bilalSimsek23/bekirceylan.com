@extends('site.layouts.app')

@section('content')
    {{-- HERO / SLIDER --}}
    <section class="hero" data-slider>
        @forelse($slides as $i => $slide)
            <div class="hero-slide {{ $i === 0 ? 'is-active' : '' }}" style="background-image:url('{{ $slide->image_url }}')">
                <div class="hero-overlay"></div>
                <div class="container hero-content">
                    @if($slide->eyebrow)<span class="eyebrow eyebrow-light">{{ $slide->eyebrow }}</span>@endif
                    <h1>{{ $slide->title }}</h1>
                    @if($slide->subtitle)<p>{{ $slide->subtitle }}</p>@endif
                    <div class="hero-actions">
                        @if($slide->primary_button_text)
                            <a href="{{ $slide->primary_button_url ?: '#turlar' }}" class="btn btn-gold">{{ $slide->primary_button_text }} →</a>
                        @endif
                        @if($slide->secondary_button_text)
                            <a href="{{ $slide->secondary_button_url ?: '#iletisim' }}" class="btn btn-outline-light">{{ $slide->secondary_button_text }}</a>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="hero-slide is-active">
                <div class="hero-overlay"></div>
                <div class="container hero-content">
                    <span class="eyebrow eyebrow-light">Hac • Umre • Kültür Turları</span>
                    <h1>{{ $settings['site_tagline'] }}</h1>
                    <div class="hero-actions"><a href="#turlar" class="btn btn-gold">Turlarımızı Keşfedin →</a></div>
                </div>
            </div>
        @endforelse
        @if($slides->count() > 1)
            <button class="hero-arrow prev" type="button" data-slide-prev aria-label="Önceki">‹</button>
            <button class="hero-arrow next" type="button" data-slide-next aria-label="Sonraki">›</button>
        @endif
    </section>

    {{-- TURLAR --}}
    <section class="section" id="turlar">
        <div class="container">
            <div class="section-head">
                <span class="eyebrow">{{ $settings['tours_eyebrow'] }}</span>
                <h2>{{ $settings['tours_title'] }}</h2>
                <p>{{ $settings['tours_subtitle'] }}</p>
            </div>

            <div class="filters" role="tablist">
                <button type="button" class="filter is-active" data-filter="all">Tüm Turlar</button>
                @foreach($categories as $category)
                    <button type="button" class="filter" data-filter="{{ $category->slug }}">{{ $category->name }}</button>
                @endforeach
            </div>

            <div class="tour-grid" data-tour-grid>
                @forelse($tours as $tour)
                    @include('site.partials.tour-card', ['tour' => $tour])
                @empty
                    <p class="empty">Yeni tur programları çok yakında.</p>
                @endforelse
            </div>
            <p class="empty" data-filter-empty hidden>Bu kategoride şu an yayında tur bulunmuyor. Bilgi almak için bize ulaşın.</p>

            <div class="center"><a href="{{ route('tours.index') }}" class="btn btn-navy">Tüm Turları Gör →</a></div>
        </div>
    </section>

    {{-- HAKKIMIZDA --}}
    <section class="section section-white" id="hakkimizda">
        <div class="container">
            <div class="section-head">
                <span class="eyebrow">{{ $settings['about_eyebrow'] }}</span>
                <h2>{{ $settings['about_title'] }}</h2>
                <p>{{ $settings['about_text'] }}</p>
            </div>
            <div class="feature-grid">
                <div class="feature">
                    <span class="feature-icon">✦</span>
                    <h3>Özenli Planlama</h3>
                    <p>Tur programlarını kolayca inceleyin ve size uygun rotayı seçin.</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">☼</span>
                    <h3>Farklı Rotalar</h3>
                    <p>Hac, umre, kültür ve gezi seçeneklerini tek noktada keşfedin.</p>
                </div>
                <div class="feature">
                    <span class="feature-icon">♡</span>
                    <h3>Kolay İletişim</h3>
                    <p>Sorularınızı ve taleplerinizi iletişim kanallarımızdan iletin.</p>
                </div>
            </div>
            <div class="trust-strip">
                <div><strong>TÜRSAB</strong><span>Belge No: {{ $settings['tursab_no'] }}</span></div>
                <div><strong>Güvenli</strong><span>Lisanslı seyahat acentesi</span></div>
                <div><strong>7/24</strong><span>WhatsApp destek</span></div>
            </div>
            <div class="center"><a href="#iletisim" class="btn btn-gold">İletişime Geçin</a></div>
        </div>
    </section>

    @include('site.partials.contact')
@endsection
