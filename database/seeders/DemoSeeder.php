<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\CashAccount;
use App\Models\Customer;
use App\Models\Inquiry;
use App\Models\Supplier;
use App\Models\Tour;
use App\Models\Transaction;
use Illuminate\Database\Seeder;

/**
 * Sadece yerel geliştirme için örnek müşteri / rezervasyon / muhasebe verisi.
 * Canlıda çalışmaz (APP_ENV=local değilse DatabaseSeeder çağırmaz).
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Customer::query()->exists()) {
            return;
        }

        $umre = Tour::query()->where('title', 'like', '%Umre%')->first();
        $kudus = Tour::query()->where('title', 'Kudüs')->first();
        $tlKasa = CashAccount::query()->where('currency', 'TRY')->where('type', 'cash')->first();
        $usdKasa = CashAccount::query()->where('currency', 'USD')->first();
        $banka = CashAccount::query()->where('type', 'bank')->first();

        $people = [
            ['Ahmet', 'Yılmaz', 'male', '1965-04-12', 'Konya', '0532 111 22 33', '2027-03-01'],
            ['Fatma', 'Yılmaz', 'female', '1968-09-30', 'Konya', '0532 111 22 34', '2031-05-20'],
            ['Mehmet', 'Kaya', 'male', '1958-01-05', 'Kayseri', '0544 222 33 44', '2029-11-11'],
            ['Ayşe', 'Demir', 'female', '1972-07-21', 'Ankara', '0505 333 44 55', '2030-02-14'],
            ['Hasan', 'Çelik', 'male', '1980-12-02', 'İstanbul', '0533 444 55 66', null],
        ];

        $customers = collect($people)->map(fn ($p) => Customer::query()->create([
            'first_name' => $p[0], 'last_name' => $p[1], 'gender' => $p[2], 'birth_date' => $p[3],
            'birth_place' => $p[4], 'city' => $p[4], 'phone' => $p[5], 'passport_type' => 'umuma',
            'passport_no' => $p[6] ? 'U'.random_int(10000000, 99999999) : null, 'passport_expiry_date' => $p[6],
            'source' => 'website', 'kvkk_consent' => true,
        ]));

        if ($umre) {
            foreach ($customers->take(4) as $i => $customer) {
                $booking = Booking::query()->create([
                    'customer_id' => $customer->id, 'tour_id' => $umre->id,
                    'status' => $i < 3 ? 'confirmed' : 'pending', 'room_type' => 'quad',
                    'group_code' => $i < 2 ? 'YILMAZ-AILE' : null,
                    'price' => 1095, 'currency' => 'USD', 'visa_status' => 'pending',
                ]);
                if ($i < 3) {
                    Transaction::query()->create([
                        'type' => 'income', 'category' => 'tour_payment', 'cash_account_id' => $usdKasa->id,
                        'amount' => $i === 0 ? 1095 : 500, 'exchange_rate' => 42.5, 'booking_id' => $booking->id,
                        'method' => 'cash', 'date' => now()->subDays(10 - $i), 'description' => 'Umre kapora / ödeme',
                    ]);
                }
            }
        }

        if ($kudus) {
            Booking::query()->create([
                'customer_id' => $customers[4]->id, 'tour_id' => $kudus->id, 'status' => 'pending',
                'room_type' => 'double', 'price' => 45000, 'currency' => 'TRY',
            ]);
        }

        $otel = Supplier::query()->create(['name' => 'Mekke Grand Otel', 'type' => 'hotel', 'phone' => '+966 12 000 00 00']);
        Supplier::query()->create(['name' => 'Örnek Havayolları', 'type' => 'airline']);

        Transaction::query()->create([
            'type' => 'expense', 'category' => 'hotel', 'cash_account_id' => $banka->id, 'amount' => 60000,
            'tour_id' => $umre?->id, 'supplier_id' => $otel->id, 'method' => 'transfer', 'date' => now()->subDays(5),
            'description' => 'Umre grubu otel kaporası',
        ]);
        Transaction::query()->create([
            'type' => 'expense', 'category' => 'rent', 'cash_account_id' => $tlKasa->id, 'amount' => 25000,
            'method' => 'cash', 'date' => now()->startOfMonth(), 'description' => 'Ofis kirası',
        ]);

        Inquiry::query()->create(['name' => 'Zeynep Arslan', 'phone' => '0530 555 66 77', 'interest' => 'Umre Turları', 'message' => 'Ailecek 4 kişi düşünüyoruz, fiyat bilgisi alabilir miyim?']);
        Inquiry::query()->create(['name' => 'Ali Öztürk', 'phone' => '0542 777 88 99', 'tour_id' => $kudus?->id, 'interest' => 'Kültür ve Gezi Turları', 'status' => 'contacted']);
    }
}
