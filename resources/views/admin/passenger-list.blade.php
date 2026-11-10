@extends('admin.print-layout')
@php use App\Support\Options; @endphp

@section('title', $tour->title.' - Yolcu Listesi')
@section('heading', $tour->title.' – Yolcu Listesi')
@section('subheading', ($tour->date_range ?? 'Tarih belirtilmedi').' · '.$bookings->count().' yolcu'.($tour->capacity ? ' / '.$tour->capacity.' kontenjan' : ''))
@section('toolbar')
    <a href="{{ route('admin.export.passengers.csv', $tour) }}">⬇ Excel (CSV)</a>
@endsection

@section('content')
    <table>
        <thead>
        <tr>
            <th>#</th><th>Rez. No</th><th>Adı Soyadı</th><th>C.</th><th>Doğum T.</th><th>TC No</th>
            <th>Pasaport</th><th>Pas. Bitiş</th><th>Telefon</th><th>Oda</th><th>Grup</th><th>Vize</th>
            <th class="right">Kalan</th><th>Not / Sağlık</th>
        </tr>
        </thead>
        <tbody>
        @foreach($bookings as $i => $b)
            @php $c = $b->customer; @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $b->booking_no }}{{ $b->status === 'pending' ? ' (Ön)' : '' }}</td>
                <td><strong>{{ mb_strtoupper($c->last_name, 'UTF-8') }}</strong> {{ $c->first_name }}</td>
                <td>{{ $c->gender === 'male' ? 'E' : ($c->gender === 'female' ? 'K' : '') }}</td>
                <td>{{ $c->birth_date?->format('d.m.Y') }}</td>
                <td>{{ $c->tc_no }}</td>
                <td>{{ $c->passport_no }}</td>
                <td class="{{ $c->passportExpiresSoon($tour->end_date ?? $tour->start_date) ? 'warn' : '' }}">{{ $c->passport_expiry_date?->format('d.m.Y') }}</td>
                <td>{{ $c->phone }}</td>
                <td>{{ Options::ROOM_TYPES[$b->room_type] ?? '' }}</td>
                <td>{{ $b->group_code }}</td>
                <td>{{ Options::VISA_STATUSES[$b->visa_status] ?? '' }}</td>
                <td class="right {{ $b->balance > 0 ? 'warn' : '' }}">{{ Options::money($b->balance, $b->currency) }}</td>
                <td>{{ trim(($c->needs_wheelchair ? 'Tekerlekli sandalye. ' : '').$c->health_notes.' '.$b->notes) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    @php $rooms = $bookings->groupBy('room_type'); @endphp
    <h2>Oda Dağılımı</h2>
    <table style="width:auto">
        @foreach($rooms as $type => $items)
            <tr><td>{{ Options::ROOM_TYPES[$type] ?? 'Belirtilmemiş' }}</td><td class="right"><strong>{{ $items->count() }}</strong> kişi</td></tr>
        @endforeach
    </table>
    <p class="meta">Kırmızı pasaport tarihi: tur dönüşünden itibaren 6 aydan az geçerlilik.</p>
@endsection
