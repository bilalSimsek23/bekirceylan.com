@extends('admin.print-layout')
@php use App\Support\Options; $c = $booking->customer; $t = $booking->tour; @endphp

@section('title', $booking->booking_no.' - Rezervasyon Dökümü')
@section('heading', 'Rezervasyon ve Ödeme Dökümü')
@section('subheading', 'Rezervasyon No: '.$booking->booking_no.' · '.$booking->registered_at?->format('d.m.Y'))

@push('style')
    <style>@media print { @page { size: A4 portrait; } } .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; } .grid table td:first-child { width: 40%; color: #6b7680; }</style>
@endpush

@section('content')
    <div class="grid">
        <div>
            <h2>Müşteri</h2>
            <table>
                <tr><td>Adı Soyadı</td><td><strong>{{ $c->full_name }}</strong></td></tr>
                <tr><td>TC No</td><td>{{ $c->tc_no }}</td></tr>
                <tr><td>Telefon</td><td>{{ $c->phone }}</td></tr>
                <tr><td>Pasaport</td><td>{{ $c->passport_no }} {{ $c->passport_expiry_date ? '('.$c->passport_expiry_date->format('d.m.Y').')' : '' }}</td></tr>
            </table>
        </div>
        <div>
            <h2>Tur</h2>
            <table>
                <tr><td>Tur</td><td><strong>{{ $t->title }}</strong></td></tr>
                <tr><td>Tarih</td><td>{{ $t->date_range }}</td></tr>
                <tr><td>Oda</td><td>{{ Options::ROOM_TYPES[$booking->room_type] ?? '-' }}</td></tr>
                <tr><td>Durum</td><td>{{ Options::BOOKING_STATUSES[$booking->status] ?? $booking->status }}</td></tr>
            </table>
        </div>
    </div>

    <h2>Ücret</h2>
    <table>
        <tr><td>Tur Ücreti</td><td class="right">{{ Options::money($booking->price, $booking->currency) }}</td></tr>
        @if($booking->extra_charges > 0)<tr><td>Ek Hizmetler</td><td class="right">{{ Options::money($booking->extra_charges, $booking->currency) }}</td></tr>@endif
        @if($booking->discount > 0)<tr><td>İndirim</td><td class="right">-{{ Options::money($booking->discount, $booking->currency) }}</td></tr>@endif
        <tr class="totals"><td>Toplam</td><td class="right">{{ Options::money($booking->total, $booking->currency) }}</td></tr>
    </table>

    <h2>Ödemeler</h2>
    <table>
        <thead><tr><th>Tarih</th><th>Açıklama</th><th>Yöntem</th><th class="right">Tutar</th><th class="right">Karşılık ({{ $booking->currency }})</th></tr></thead>
        @forelse($booking->transactions->sortBy('date') as $tx)
            <tr>
                <td>{{ $tx->date->format('d.m.Y') }}</td>
                <td>{{ $tx->type === 'expense' ? 'İADE – ' : '' }}{{ $tx->description }} {{ $tx->document_no ? '#'.$tx->document_no : '' }}</td>
                <td>{{ Options::PAYMENT_METHODS[$tx->method] ?? '' }}</td>
                <td class="right">{{ Options::money($tx->signed_amount, $tx->currency) }}</td>
                <td class="right">{{ Options::money(($tx->type === 'income' ? 1 : -1) * $tx->booking_amount, $booking->currency) }}</td>
            </tr>
        @empty
            <tr><td colspan="5">Henüz ödeme yok.</td></tr>
        @endforelse
        <tr class="totals"><td colspan="4">Toplam Ödenen</td><td class="right">{{ Options::money($booking->paid, $booking->currency) }}</td></tr>
        <tr class="totals"><td colspan="4">Kalan Bakiye</td><td class="right">{{ Options::money($booking->balance, $booking->currency) }}</td></tr>
    </table>

    <div class="sign"><div>Müşteri İmza</div><div>{{ \App\Models\Setting::get('company_title') }}</div></div>
@endsection
