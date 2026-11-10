@extends('site.layouts.app')

@section('title', ($tour->meta_title ?: $tour->title).' | '.$settings['site_title'])
@section('description', $tour->meta_description ?: ($tour->summary ?: $settings['meta_description']))
@section('og_image', $tour->cover_url)

@php
    $remaining = $tour->remaining_seats;
    $roomPrices = collect($tour->room_prices ?? [])->filter(fn ($r) => filled($r['price'] ?? null));
@endphp

@section('content')
    <section class="tour-hero" style="background-image:url('{{ $tour->cover_url }}')">
        <div class="hero-overlay"></div>
        <div class="container tour-hero-content">
            <nav class="breadcrumb">
                <a href="{{ route('home') }}">Ana Sayfa</a> /
                <a href="{{ route('tours.index', ['kategori' => $tour->category?->slug]) }}">{{ $tour->category?->name ?? 'Turlar' }}</a> /
                <span>{{ $tour->title }}</span>
            </nav>
            @if($tour->category)<span class="badge">{{ $tour->category->badge ?: $tour->category->name }}</span>@endif
            <h1>{{ $tour->title }}</h1>
            @if($tour->location)<p>📍 {{ $tour->location }}</p>@endif
        </div>
    </section>

    <section class="section">
        <div class="container tour-layout">
            <div class="tour-main">
                <div class="facts">
                    @if($tour->start_date)
                        <div><small>Tarih</small><strong>{{ $tour->date_range }}</strong></div>
                    @endif
                    @if($tour->duration_text)
                        <div><small>Süre</small><strong>{{ $tour->duration_text }}</strong></div>
                    @endif
                    @if($tour->departure_city)
                        <div><small>Hareket</small><strong>{{ $tour->departure_city }}</strong></div>
                    @endif
                    @if($tour->transport)
                        <div><small>Ulaşım</small><strong>{{ $tour->transport }}</strong></div>
                    @endif
                    @if($remaining !== null)
                        <div><small>Kontenjan</small><strong>{{ $remaining }} kişilik yer kaldı</strong></div>
                    @endif
                </div>

                @if($tour->summary)
                    <p class="lead">{{ $tour->summary }}</p>
                @endif

                @if($tour->description)
                    <div class="prose">{!! $tour->description !!}</div>
                @endif

                @if(! empty($tour->itinerary))
                    <h2 class="block-title">Tur Programı</h2>
                    <ol class="itinerary">
                        @foreach($tour->itinerary as $i => $day)
                            <li>
                                <span class="day">{{ $day['day'] ?? ($i + 1).'. Gün' }}</span>
                                <div>
                                    <h3>{{ $day['title'] ?? '' }}</h3>
                                    @if(! empty($day['description']))<p>{!! nl2br(e($day['description'])) !!}</p>@endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif

                @if(! empty($tour->hotels))
                    <h2 class="block-title">Konaklama</h2>
                    <div class="hotel-list">
                        @foreach($tour->hotels as $hotel)
                            <div class="hotel">
                                <strong>{{ $hotel['name'] ?? '' }}</strong>
                                <span>{{ $hotel['city'] ?? '' }}@if(! empty($hotel['nights'])) · {{ $hotel['nights'] }} gece @endif</span>
                                @if(! empty($hotel['stars']))<span class="stars">{{ str_repeat('★', (int) $hotel['stars']) }}</span>@endif
                                @if(! empty($hotel['note']))<small>{{ $hotel['note'] }}</small>@endif
                            </div>
                        @endforeach
                    </div>
                @endif

                @if(! empty($tour->included) || ! empty($tour->excluded))
                    <div class="inc-grid">
                        @if(! empty($tour->included))
                            <div>
                                <h2 class="block-title">Fiyata Dahil</h2>
                                <ul class="check-list">
                                    @foreach($tour->included as $item)<li>{{ is_array($item) ? ($item['item'] ?? '') : $item }}</li>@endforeach
                                </ul>
                            </div>
                        @endif
                        @if(! empty($tour->excluded))
                            <div>
                                <h2 class="block-title">Fiyata Dahil Olmayanlar</h2>
                                <ul class="cross-list">
                                    @foreach($tour->excluded as $item)<li>{{ is_array($item) ? ($item['item'] ?? '') : $item }}</li>@endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif

                @if(! empty($tour->gallery_urls))
                    <h2 class="block-title">Galeri</h2>
                    <div class="gallery">
                        @foreach($tour->gallery_urls as $img)
                            <a href="{{ $img }}" target="_blank" rel="noopener"><img src="{{ $img }}" alt="{{ $tour->title }} görsel" loading="lazy"></a>
                        @endforeach
                    </div>
                @endif
            </div>

            <aside class="tour-aside">
                <div class="price-box">
                    @if($tour->price)
                        <small>Kişi başı</small>
                        @if($tour->old_price && $tour->old_price > $tour->price)
                            <del>{{ $tour->formattedPrice($tour->old_price) }}</del>
                        @endif
                        <div class="big-price">{{ $tour->formattedPrice() }}</div>
                    @else
                        <div class="big-price">{{ $tour->price_label ?: 'Bilgi Alınız' }}</div>
                    @endif

                    @if($roomPrices->isNotEmpty())
                        <table class="room-prices">
                            @foreach($roomPrices as $room)
                                <tr>
                                    <td>{{ \App\Support\Options::ROOM_TYPES[$room['type']] ?? $room['type'] }}</td>
                                    <td>{{ $tour->formattedPrice((float) $room['price']) }}</td>
                                </tr>
                            @endforeach
                        </table>
                    @endif

                    <a href="#iletisim" class="btn btn-gold btn-block">Bilgi Al / Ön Kayıt</a>
                    <a href="https://wa.me/{{ $settings['whatsapp'] }}?text={{ rawurlencode('Merhaba, "'.$tour->title.'" turu hakkında bilgi almak istiyorum.') }}"
                       class="btn btn-whatsapp btn-block" target="_blank" rel="noopener">
                        @include('site.partials.whatsapp-icon') WhatsApp'tan Sor
                    </a>
                    <p class="muted small">TÜRSAB Belge No: {{ $settings['tursab_no'] }}</p>
                </div>
            </aside>
        </div>
    </section>

    @if($related->isNotEmpty())
        <section class="section section-white">
            <div class="container">
                <div class="section-head"><span class="eyebrow">Benzer Turlar</span><h2>Bunlar da İlginizi Çekebilir</h2></div>
                <div class="tour-grid">
                    @foreach($related as $item)
                        @include('site.partials.tour-card', ['tour' => $item])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @include('site.partials.contact', ['selectedTourId' => $tour->id])
@endsection
