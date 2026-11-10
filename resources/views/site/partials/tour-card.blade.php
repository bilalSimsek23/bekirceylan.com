@php
    $remaining = $tour->remaining_seats;
@endphp
<article class="tour-card" data-category="{{ $tour->category?->slug }}">
    <a href="{{ route('tours.show', $tour) }}" class="tour-card-media">
        <img src="{{ $tour->cover_url }}" alt="{{ $tour->title }}" loading="lazy">
        @if($tour->category)
            <span class="badge">{{ $tour->category->badge ?: $tour->category->name }}</span>
        @endif
        @if($remaining !== null && $remaining <= 5 && $remaining > 0)
            <span class="badge badge-alert">Son {{ $remaining }} kişi!</span>
        @elseif($remaining === 0)
            <span class="badge badge-alert">Kontenjan Doldu</span>
        @endif
    </a>
    <div class="tour-card-body">
        <h3><a href="{{ route('tours.show', $tour) }}">{{ $tour->title }}</a></h3>
        @if($tour->location)
            <p class="tour-location">📍 {{ $tour->location }}</p>
        @endif
        @if($tour->summary)
            <p class="tour-summary">{{ $tour->summary }}</p>
        @endif
        @php
            $meta = array_filter([
                $tour->duration_text,
                $remaining !== null ? $remaining.' kişilik yer kaldı' : null,
            ]);
        @endphp
        @if($tour->start_date || $meta)
            <p class="tour-meta">
                @if($tour->start_date)<strong>{{ $tour->date_range }}</strong>{{ $meta ? ' · ' : '' }}@endif{{ implode(' · ', $meta) }}
            </p>
        @endif
        <div class="tour-card-footer">
            <div class="price">
                @if($tour->price)
                    @if($tour->old_price && $tour->old_price > $tour->price)
                        <del>{{ $tour->formattedPrice($tour->old_price) }}</del>
                    @endif
                    <strong>{{ $tour->formattedPrice() }}</strong>
                @else
                    <strong class="price-label">{{ $tour->price_label ?: 'Bilgi Alınız' }}</strong>
                @endif
            </div>
            <a href="{{ route('home') }}#iletisim" class="btn btn-gold btn-sm" data-inquire="{{ $tour->id }}">Bilgi Al →</a>
        </div>
    </div>
</article>
