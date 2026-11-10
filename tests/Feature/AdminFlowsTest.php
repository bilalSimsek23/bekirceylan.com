<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteSettings;
use App\Filament\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Resources\Bookings\Pages\EditBooking;
use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Tours\Pages\CreateTour;
use App\Filament\Resources\Transactions\Pages\ManageTransactions;
use App\Models\Booking;
use App\Models\CashAccount;
use App\Models\Customer;
use App\Models\Inquiry;
use App\Models\Setting;
use App\Models\Tour;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminFlowsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Http::fake(['*' => Http::response('', 500)]);
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::query()->where('role', 'admin')->firstOrFail());
    }

    public function test_create_customer_from_inquiry(): void
    {
        $inquiry = Inquiry::query()->create(['name' => 'Zeynep Nur Arslan', 'phone' => '05305556677', 'message' => '4 kişi']);

        Livewire::withQueryParams(['inquiry' => $inquiry->id])
            ->test(CreateCustomer::class)
            ->assertSchemaStateSet(['first_name' => 'Zeynep Nur', 'last_name' => 'Arslan', 'phone' => '05305556677'])
            ->fillForm(['tc_no' => '10000000146', 'gender' => 'female'])
            ->call('create')
            ->assertHasNoFormErrors();

        $customer = Customer::query()->where('tc_no', '10000000146')->firstOrFail();
        $this->assertSame('converted', $inquiry->fresh()->status);
        $this->assertSame($customer->id, $inquiry->fresh()->customer_id);
    }

    public function test_invalid_tc_is_rejected(): void
    {
        Livewire::test(CreateCustomer::class)
            ->fillForm(['first_name' => 'Ali', 'last_name' => 'Veli', 'tc_no' => '12345678901'])
            ->call('create')
            ->assertHasFormErrors(['tc_no']);
    }

    public function test_create_tour_with_program(): void
    {
        Livewire::test(CreateTour::class)
            ->fillForm([
                'title' => 'Ramazan Umresi 2027',
                'status' => 'published',
                'currency' => 'USD',
                'price' => 1450,
                'capacity' => 45,
                'start_date' => '2027-03-01',
                'end_date' => '2027-03-15',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $tour = Tour::query()->where('title', 'Ramazan Umresi 2027')->firstOrFail();
        $this->assertSame('ramazan-umresi-2027', $tour->slug);
        $this->get('/turlar/ramazan-umresi-2027')->assertOk()->assertSee('1.450 $');
    }

    public function test_booking_and_payment_flow(): void
    {
        $tour = Tour::query()->whereNotNull('price')->firstOrFail();
        $customer = Customer::query()->create(['first_name' => 'Mehmet', 'last_name' => 'Kaya', 'phone' => '05441112233']);

        Livewire::withQueryParams(['customer' => $customer->id, 'tour' => $tour->id])
            ->test(CreateBooking::class)
            ->fillForm(['status' => 'confirmed', 'room_type' => 'quad', 'currency' => 'USD', 'price' => 1095, 'discount' => 45])
            ->call('create')
            ->assertHasNoFormErrors();

        $booking = Booking::query()->where('customer_id', $customer->id)->firstOrFail();
        $this->assertSame($tour->id, $booking->tour_id);
        $this->assertStringStartsWith('BC'.now()->format('y'), $booking->booking_no);
        $this->assertEqualsWithDelta(1050, $booking->balance, 0.001);

        $usd = CashAccount::query()->where('currency', 'USD')->firstOrFail();
        Livewire::test(EditBooking::class, ['record' => $booking->getRouteKey()])
            ->callAction('payment', data: ['cash_account_id' => $usd->id, 'amount' => 500, 'method' => 'cash', 'date' => now()->toDateString()])
            ->assertHasNoActionErrors();

        $this->assertEqualsWithDelta(550, $booking->fresh()->balance, 0.001);
        $this->assertSame(1, $tour->transactions()->count());
    }

    public function test_transfer_between_accounts(): void
    {
        $tl = CashAccount::query()->where('currency', 'TRY')->where('type', 'cash')->firstOrFail();
        $bank = CashAccount::query()->where('type', 'bank')->firstOrFail();

        Livewire::test(ManageTransactions::class)
            ->callAction('transfer', data: ['from' => $tl->id, 'to' => $bank->id, 'amount' => 1000, 'date' => now()->toDateString(), 'description' => 'Bankaya yatırıldı'])
            ->assertHasNoActionErrors();

        $this->assertEqualsWithDelta(-1000, $tl->fresh()->balance, 0.001);
        $this->assertEqualsWithDelta(1000, $bank->fresh()->balance, 0.001);
    }

    public function test_site_settings_save(): void
    {
        Livewire::test(SiteSettings::class)
            ->fillForm(['phone' => '0532 999 99 99', 'tursab_no' => '12345'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('12345', Setting::get('tursab_no'));
        $this->get('/')->assertSee('0532 999 99 99')->assertSee('12345');
    }
}
