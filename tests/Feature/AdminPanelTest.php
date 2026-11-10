<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Tour;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Http::fake(['*' => Http::response('', 500)]);
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoSeeder::class);
        $this->admin = User::query()->where('role', 'admin')->firstOrFail();
    }

    public function test_every_admin_page_renders_for_admin(): void
    {
        $tour = Tour::query()->has('bookings')->firstOrFail();
        $customer = Customer::query()->has('bookings')->firstOrFail();
        $booking = Booking::query()->firstOrFail();

        $urls = [
            '/admin', '/admin/tours', '/admin/tours/create', "/admin/tours/{$tour->slug}/edit",
            '/admin/tour-categories', '/admin/bookings', '/admin/bookings/create', "/admin/bookings/{$booking->id}/edit",
            '/admin/customers', '/admin/customers/create', "/admin/customers/{$customer->id}", "/admin/customers/{$customer->id}/edit",
            '/admin/inquiries', '/admin/transactions', '/admin/cash-accounts', '/admin/suppliers', '/admin/finance-report',
            '/admin/slides', '/admin/site-settings', '/admin/users', '/admin/profile',
            "/admin/cikti/tur/{$tour->slug}/yolcu-listesi", "/admin/cikti/rezervasyon/{$booking->id}/makbuz",
        ];

        foreach ($urls as $url) {
            $response = $this->actingAs($this->admin)->get($url);
            $error = $response->exception ? ' '.$response->exception->getMessage().' @ '.$response->exception->getFile().':'.$response->exception->getLine() : '';
            $this->assertSame(200, $response->status(), "$url → {$response->status()}$error");
        }

        $this->actingAs($this->admin)->get("/admin/cikti/tur/{$tour->slug}/yolcu-listesi.csv")->assertOk();
    }

    public function test_staff_cannot_access_accounting(): void
    {
        $staff = User::query()->create(['name' => 'Personel', 'email' => 'p@x.com', 'password' => 'secret123', 'role' => 'staff']);

        $this->actingAs($staff)->get('/admin/customers')->assertOk();
        $this->actingAs($staff)->get('/admin/bookings')->assertOk();
        foreach (['/admin/transactions', '/admin/cash-accounts', '/admin/finance-report', '/admin/users', '/admin/site-settings'] as $url) {
            $this->actingAs($staff)->get($url)->assertForbidden();
        }
    }

    public function test_booking_balance_and_payment_math(): void
    {
        $booking = Booking::query()->where('currency', 'USD')->whereHas('transactions')->firstOrFail();
        $paidBefore = $booking->paid;

        $booking->update(['discount' => 95]);
        $tlAccount = \App\Models\CashAccount::query()->where('currency', 'TRY')->firstOrFail();

        // TL ile ödeme, rezervasyon USD → karşılık elle girilen tutar
        Transaction::query()->create([
            'type' => 'income', 'category' => 'tour_payment', 'cash_account_id' => $tlAccount->id,
            'amount' => 5000, 'exchange_rate' => 1, 'booking_id' => $booking->id, 'booking_amount' => 100, 'date' => now(),
        ]);

        $booking->refresh();
        $this->assertEqualsWithDelta($paidBefore + 100, $booking->paid, 0.001);
        $this->assertEqualsWithDelta(1095 - 95 - $booking->paid, $booking->balance, 0.001);
        $this->assertSame('TRY', $booking->transactions()->latest('id')->first()->currency);
    }

    public function test_tc_number_validation(): void
    {
        $this->assertTrue(Customer::isValidTcNo('10000000146'));
        $this->assertFalse(Customer::isValidTcNo('12345678901'));
        $this->assertFalse(Customer::isValidTcNo('01234567890'));
    }
}
