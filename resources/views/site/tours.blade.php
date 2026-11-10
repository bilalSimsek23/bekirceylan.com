@extends('site.layouts.app')

@section('title', ($activeCategory ? $activeCategory->name.' Turları' : 'Tur Programlarımız').' | '.$settings['site_title'])

@section('content')
    <section class="page-hero">
        <div class="container">
            <span class="eyebrow eyebrow-light">Turlarımız</span>
            <h1>{{ $activeCategory ? $activeCategory->name.' Turları' : 'Tüm Tur Programlarımız' }}</h1>
            @if($activeCategory?->description)<p>{{ $activeCategory->description }}</p>@endif
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="filters">
                <a href="{{ route('tours.index') }}" class="filter {{ $activeCategory ? '' : 'is-active' }}">Tüm Turlar</a>
                @foreach($categories as $category)
                    <a href="{{ route('tours.index', ['kategori' => $category->slug]) }}"
                       class="filter {{ $activeCategory?->is($category) ? 'is-active' : '' }}">{{ $category->name }}</a>
                @endforeach
            </div>

            <div class="tour-grid">
                @forelse($tours as $tour)
                    @include('site.partials.tour-card', ['tour' => $tour])
                @empty
                    <p class="empty">Bu kategoride şu an yayında tur bulunmuyor. Bilgi almak için bize ulaşın.</p>
                @endforelse
            </div>
        </div>
    </section>

    @include('site.partials.contact')
@endsection
