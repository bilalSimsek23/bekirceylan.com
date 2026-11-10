<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Inquiries\InquiryResource;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Inquiry;
use App\Models\Tour;
use App\Models\Transaction;
use App\Support\Options;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $stats = [
            Stat::make('Yeni Bilgi Talebi', Inquiry::where('status', 'new')->count())
                ->description('Toplam açık: '.Inquiry::whereIn('status', ['new', 'contacted'])->count())
                ->color('danger')->icon('heroicon-o-inbox-arrow-down')
                ->url(InquiryResource::getUrl('index')),
            Stat::make('Müşteri', Customer::count())
                ->description('Bu ay +'.Customer::where('created_at', '>=', now()->startOfMonth())->count())
                ->icon('heroicon-o-users')->url(CustomerResource::getUrl('index')),
            Stat::make('Yaklaşan Tur', Tour::where('start_date', '>=', today())->where('status', 'published')->count())
                ->description('Aktif yolcu: '.Booking::whereIn('status', ['pending', 'confirmed'])
                    ->whereHas('tour', fn ($q) => $q->where('start_date', '>=', today()))->count())
                ->icon('heroicon-o-globe-europe-africa'),
        ];

        if (auth()->user()?->isAdmin()) {
            $month = Transaction::whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
                ->whereNotIn('category', ['transfer_in', 'transfer_out']);
            $income = (clone $month)->where('type', 'income')->sum('amount_try');
            $expense = (clone $month)->where('type', 'expense')->sum('amount_try');

            $trend = collect(range(6, 0))->map(fn ($i) => (float) Transaction::where('type', 'income')
                ->whereNotIn('category', ['transfer_in'])
                ->whereBetween('date', [now()->subMonths($i)->startOfMonth(), now()->subMonths($i)->endOfMonth()])
                ->sum('amount_try'))->all();

            $stats[] = Stat::make('Bu Ay Tahsilat', Options::money($income, 'TRY', 0))
                ->description('Gider: '.Options::money($expense, 'TRY', 0))
                ->chart($trend)->color('success')->icon('heroicon-o-banknotes');
        }

        return $stats;
    }
}
