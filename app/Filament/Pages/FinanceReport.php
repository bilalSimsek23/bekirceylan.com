<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\AdminOnly;
use App\Models\Booking;
use App\Models\CashAccount;
use App\Models\Tour;
use App\Models\Transaction;
use App\Support\Options;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use UnitEnum;

/**
 * Muhasebe özet raporu: dönem gelir/gider, kategori dağılımı,
 * tur bazlı kâr-zarar, müşteri alacakları ve kasa bakiyeleri.
 */
class FinanceReport extends Page
{
    use AdminOnly;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Muhasebe';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Raporlar';

    protected static ?string $title = 'Muhasebe Raporu';

    protected string $view = 'filament.pages.finance-report';

    public string $from = '';

    public string $until = '';

    public function mount(): void
    {
        $this->from = now()->startOfYear()->toDateString();
        $this->until = now()->endOfMonth()->toDateString();
    }

    public function setPeriod(string $period): void
    {
        [$this->from, $this->until] = match ($period) {
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->subYears(10), now()->addYear()],
        };
        $this->from = Carbon::parse($this->from)->toDateString();
        $this->until = Carbon::parse($this->until)->toDateString();
    }

    protected function getViewData(): array
    {
        $period = Transaction::query()->whereBetween('date', [$this->from, $this->until])
            ->whereNotIn('category', ['transfer_in', 'transfer_out']);

        $byCategory = (clone $period)->selectRaw('type, category, SUM(amount_try) as total, COUNT(*) as cnt')
            ->groupBy('type', 'category')->orderByDesc('total')->get()
            ->map(fn ($r) => ['type' => $r->type, 'label' => Options::categories()[$r->category] ?? $r->category, 'total' => (float) $r->total, 'count' => $r->cnt]);

        $monthExpr = Transaction::query()->getConnection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', date)" : "DATE_FORMAT(date, '%Y-%m')";
        $monthly = (clone $period)->selectRaw("$monthExpr as ym, type, SUM(amount_try) as total")
            ->groupBy('ym', 'type')->orderBy('ym')->get()
            ->groupBy('ym')->map(fn ($rows) => [
                'income' => (float) $rows->firstWhere('type', 'income')?->total,
                'expense' => (float) $rows->firstWhere('type', 'expense')?->total,
            ]);

        // Tur bazlı kâr / zarar (TL karşılıklarıyla)
        $tours = Tour::query()
            ->where(fn ($q) => $q->whereHas('transactions')->orWhereHas('bookings'))
            ->withCount('activeBookings')
            ->orderByDesc('start_date')->get()
            ->map(function (Tour $tour) {
                $tx = $tour->transactions()->get();
                $income = $tx->where('type', 'income')->sum('amount_try');
                $refund = $tx->where('type', 'expense')->where('category', 'refund')->sum('amount_try');
                $cost = $tx->where('type', 'expense')->where('category', '!=', 'refund')->sum('amount_try');
                $bookings = $tour->activeBookings()->with('transactions')->get();

                return [
                    'tour' => $tour,
                    'pax' => $tour->active_bookings_count,
                    'sales' => $bookings->sum->total,
                    'receivable' => $bookings->sum(fn ($b) => max(0, $b->balance)),
                    'income' => $income - $refund,
                    'cost' => $cost,
                    'profit' => $income - $refund - $cost,
                ];
            });

        // Müşteri alacakları
        $receivables = Booking::query()->with(['customer', 'tour', 'transactions'])
            ->whereIn('status', ['pending', 'confirmed'])->get()
            ->filter(fn (Booking $b) => $b->balance > 0.009)
            ->sortBy(fn (Booking $b) => $b->tour?->start_date)
            ->values();

        $accounts = CashAccount::query()
            ->withSum(['transactions as income_sum' => fn ($q) => $q->where('type', 'income')], 'amount')
            ->withSum(['transactions as expense_sum' => fn ($q) => $q->where('type', 'expense')], 'amount')
            ->get();

        return [
            'income' => $byCategory->where('type', 'income')->sum('total'),
            'expense' => $byCategory->where('type', 'expense')->sum('total'),
            'byCategory' => $byCategory,
            'monthly' => $monthly,
            'tours' => $tours,
            'receivables' => $receivables,
            'receivableTotals' => $receivables->groupBy('currency')->map(fn ($g) => $g->sum->balance),
            'accounts' => $accounts,
        ];
    }
}
