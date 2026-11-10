@php use App\Support\Options; @endphp
<x-filament-panels::page>
    <style>
        .fr-bar { display:flex; flex-wrap:wrap; gap:.5rem; align-items:end; }
        .fr-bar label { font-size:.75rem; display:flex; flex-direction:column; gap:.25rem; color:rgb(107 114 128); }
        .fr-bar input { border:1px solid rgb(209 213 219); border-radius:.5rem; padding:.4rem .6rem; background:transparent; color:inherit; }
        .fr-chip { border:1px solid rgb(209 213 219); border-radius:999px; padding:.35rem .9rem; font-size:.8rem; cursor:pointer; background:transparent; color:inherit; }
        .fr-chip:hover { border-color:#c9a15c; }
        .fr-cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:1rem; }
        .fr-card { border-radius:.75rem; padding:1rem 1.25rem; border:1px solid rgba(127,127,127,.2); }
        .fr-card small { display:block; font-size:.75rem; opacity:.7; }
        .fr-card strong { font-size:1.4rem; }
        .fr-pos { color:#16a34a; } .fr-neg { color:#dc2626; }
        .fr-table { width:100%; border-collapse:collapse; font-size:.85rem; }
        .fr-table th { text-align:left; font-weight:600; padding:.5rem; border-bottom:1px solid rgba(127,127,127,.3); font-size:.75rem; text-transform:uppercase; opacity:.75; }
        .fr-table td { padding:.5rem; border-bottom:1px solid rgba(127,127,127,.12); }
        .fr-table .r { text-align:right; white-space:nowrap; }
        .fr-grid2 { display:grid; grid-template-columns:repeat(auto-fit,minmax(360px,1fr)); gap:1.5rem; }
        .fr-bar-fill { height:8px; border-radius:4px; background:#c9a15c; }
        .fr-scroll { overflow-x:auto; }
    </style>

    <div class="fr-bar">
        <label>Başlangıç <input type="date" wire:model.live="from"></label>
        <label>Bitiş <input type="date" wire:model.live="until"></label>
        <button class="fr-chip" wire:click="setPeriod('month')">Bu Ay</button>
        <button class="fr-chip" wire:click="setPeriod('last_month')">Geçen Ay</button>
        <button class="fr-chip" wire:click="setPeriod('year')">Bu Yıl</button>
        <button class="fr-chip" wire:click="setPeriod('all')">Tümü</button>
    </div>

    <div class="fr-cards">
        <div class="fr-card"><small>Dönem Geliri (TL)</small><strong class="fr-pos">{{ Options::money($income, 'TRY') }}</strong></div>
        <div class="fr-card"><small>Dönem Gideri (TL)</small><strong class="fr-neg">{{ Options::money($expense, 'TRY') }}</strong></div>
        <div class="fr-card"><small>Net (TL)</small><strong class="{{ $income - $expense >= 0 ? 'fr-pos' : 'fr-neg' }}">{{ Options::money($income - $expense, 'TRY') }}</strong></div>
        <div class="fr-card"><small>Müşteri Alacakları</small>
            <strong>{{ $receivableTotals->map(fn ($v, $c) => Options::money($v, $c, 0))->implode(' + ') ?: '0' }}</strong>
        </div>
    </div>
    <p style="font-size:.75rem;opacity:.6;margin-top:-.5rem">Virmanlar hariçtir. Dövizli hareketler işlem kurundan TL'ye çevrilmiştir.</p>

    <div class="fr-grid2">
        <x-filament::section heading="Kasa & Banka Bakiyeleri">
            <table class="fr-table">
                @foreach($accounts as $a)
                    <tr><td>{{ $a->name }}</td><td class="r"><strong>{{ Options::money($a->balance, $a->currency) }}</strong></td></tr>
                @endforeach
            </table>
        </x-filament::section>

        <x-filament::section heading="Aylık Gelir / Gider (TL)">
            <table class="fr-table">
                <thead><tr><th>Ay</th><th class="r">Gelir</th><th class="r">Gider</th><th class="r">Net</th></tr></thead>
                @forelse($monthly as $ym => $m)
                    <tr>
                        <td>{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $ym)->translatedFormat('F Y') }}</td>
                        <td class="r fr-pos">{{ Options::money($m['income'], 'TRY', 0) }}</td>
                        <td class="r fr-neg">{{ Options::money($m['expense'], 'TRY', 0) }}</td>
                        <td class="r"><strong>{{ Options::money($m['income'] - $m['expense'], 'TRY', 0) }}</strong></td>
                    </tr>
                @empty
                    <tr><td colspan="4">Bu dönemde hareket yok.</td></tr>
                @endforelse
            </table>
        </x-filament::section>
    </div>

    <x-filament::section heading="Kategori Dağılımı">
        @php $max = max(1, $byCategory->max('total')); @endphp
        <table class="fr-table">
            <thead><tr><th>Kategori</th><th>Tip</th><th style="width:35%"></th><th class="r">Adet</th><th class="r">Tutar (TL)</th></tr></thead>
            @forelse($byCategory as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td class="{{ $row['type'] === 'income' ? 'fr-pos' : 'fr-neg' }}">{{ $row['type'] === 'income' ? 'Gelir' : 'Gider' }}</td>
                    <td><div class="fr-bar-fill" style="width:{{ round($row['total'] / $max * 100) }}%; {{ $row['type'] === 'expense' ? 'background:#102b40' : '' }}"></div></td>
                    <td class="r">{{ $row['count'] }}</td>
                    <td class="r">{{ Options::money($row['total'], 'TRY') }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Bu dönemde hareket yok.</td></tr>
            @endforelse
        </table>
    </x-filament::section>

    <x-filament::section heading="Tur Bazlı Kâr / Zarar" description="Tüm zamanlar · tahsilatlar ve tura bağlı giderler (TL karşılığı)">
        <div class="fr-scroll">
        <table class="fr-table">
            <thead><tr><th>Tur</th><th class="r">Yolcu</th><th class="r">Satış</th><th class="r">Kalan Alacak</th><th class="r">Net Tahsilat (TL)</th><th class="r">Maliyet (TL)</th><th class="r">Kâr (TL)</th></tr></thead>
            @forelse($tours as $row)
                <tr>
                    <td><a href="{{ \App\Filament\Resources\Tours\TourResource::getUrl('edit', ['record' => $row['tour']]) }}" style="text-decoration:underline">{{ $row['tour']->title }}</a>
                        <div style="font-size:.7rem;opacity:.6">{{ $row['tour']->start_date?->format('d.m.Y') }}</div></td>
                    <td class="r">{{ $row['pax'] }}</td>
                    <td class="r">{{ Options::money($row['sales'], $row['tour']->currency, 0) }}</td>
                    <td class="r {{ $row['receivable'] > 0 ? 'fr-neg' : '' }}">{{ Options::money($row['receivable'], $row['tour']->currency, 0) }}</td>
                    <td class="r">{{ Options::money($row['income'], 'TRY', 0) }}</td>
                    <td class="r">{{ Options::money($row['cost'], 'TRY', 0) }}</td>
                    <td class="r"><strong class="{{ $row['profit'] >= 0 ? 'fr-pos' : 'fr-neg' }}">{{ Options::money($row['profit'], 'TRY', 0) }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="7">Henüz veri yok.</td></tr>
            @endforelse
        </table>
        </div>
    </x-filament::section>

    <x-filament::section heading="Müşteri Alacakları (Açık Bakiyeler)">
        <div class="fr-scroll">
        <table class="fr-table">
            <thead><tr><th>Müşteri</th><th>Telefon</th><th>Tur</th><th class="r">Toplam</th><th class="r">Ödenen</th><th class="r">Kalan</th></tr></thead>
            @forelse($receivables as $b)
                <tr>
                    <td><a href="{{ \App\Filament\Resources\Bookings\BookingResource::getUrl('edit', ['record' => $b]) }}" style="text-decoration:underline">{{ $b->customer?->full_name }}</a></td>
                    <td>{{ $b->customer?->phone }}</td>
                    <td>{{ $b->tour?->title }} <span style="opacity:.6">{{ $b->tour?->start_date?->format('d.m.Y') }}</span></td>
                    <td class="r">{{ Options::money($b->total, $b->currency) }}</td>
                    <td class="r">{{ Options::money($b->paid, $b->currency) }}</td>
                    <td class="r fr-neg"><strong>{{ Options::money($b->balance, $b->currency) }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="6">Açık bakiye yok. 🎉</td></tr>
            @endforelse
        </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
