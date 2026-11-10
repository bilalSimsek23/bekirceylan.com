<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Tour;
use App\Support\Options;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    private function bookings(Tour $tour)
    {
        return $tour->bookings()
            ->with(['customer', 'transactions'])
            ->where('status', '!=', 'cancelled')
            ->get()
            ->sortBy(fn (Booking $b) => [$b->group_code ?? 'zzz', $b->customer->last_name, $b->customer->first_name])
            ->values();
    }

    /** Yazdırılabilir yolcu listesi (otel / havayolu / rehber için) */
    public function passengerList(Tour $tour)
    {
        return view('admin.passenger-list', ['tour' => $tour, 'bookings' => $this->bookings($tour)]);
    }

    /** Excel ile açılabilen CSV (UTF-8 BOM, ; ayraç) */
    public function passengerCsv(Tour $tour): StreamedResponse
    {
        $bookings = $this->bookings($tour);
        $filename = str($tour->title)->slug().'-yolcu-listesi.csv';

        return response()->streamDownload(function () use ($bookings) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Sıra', 'Rez. No', 'Adı', 'Soyadı', 'Cinsiyet', 'Doğum Tarihi', 'TC No', 'Pasaport No', 'Pasaport Bitiş', 'Telefon', 'Oda', 'Grup', 'Durum', 'Vize', 'Tutar', 'Ödenen', 'Kalan', 'Para Birimi', 'Sağlık / Not'], ';');
            foreach ($bookings as $i => $b) {
                $c = $b->customer;
                fputcsv($out, [
                    $i + 1, $b->booking_no, $c->first_name, $c->last_name, Options::GENDERS[$c->gender] ?? '',
                    $c->birth_date?->format('d.m.Y'), $c->tc_no, $c->passport_no, $c->passport_expiry_date?->format('d.m.Y'),
                    $c->phone, Options::ROOM_TYPES[$b->room_type] ?? '', $b->group_code,
                    Options::BOOKING_STATUSES[$b->status] ?? $b->status, Options::VISA_STATUSES[$b->visa_status] ?? '',
                    number_format($b->total, 2, ',', ''), number_format($b->paid, 2, ',', ''), number_format($b->balance, 2, ',', ''),
                    $b->currency, trim($c->health_notes.' '.$b->notes),
                ], ';');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Müşteriye verilebilecek rezervasyon / ödeme dökümü */
    public function bookingStatement(Booking $booking)
    {
        $booking->load(['customer', 'tour', 'transactions.account']);

        return view('admin.booking-statement', compact('booking'));
    }
}
