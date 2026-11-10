<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Yaklaşan turlarda pasaportu eksik veya dönüşten itibaren 6 aydan az geçerli yolcular.
 */
class PassportWarnings extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = '⚠ Pasaport Kontrolü (yaklaşan turlar)';

    protected static function sixMonthsAfterReturnSql(): string
    {
        $returnDate = '(SELECT COALESCE(end_date, start_date) FROM tours WHERE tours.id = bookings.tour_id)';

        return Booking::query()->getConnection()->getDriverName() === 'sqlite'
            ? "date($returnDate, '+6 months')"
            : "DATE_ADD($returnDate, INTERVAL 6 MONTH)";
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::query()->with(['customer', 'tour'])
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->whereHas('tour', fn (Builder $query) => $query->where('start_date', '>=', today()))
                    ->whereHas('customer', fn (Builder $query) => $query->whereNull('passport_no')
                        ->orWhereNull('passport_expiry_date')
                        ->orWhereRaw('passport_expiry_date < '.static::sixMonthsAfterReturnSql()))
            )
            ->emptyStateHeading('Tüm yolcuların pasaportu uygun')
            ->paginated([5, 10])
            ->columns([
                TextColumn::make('customer.first_name')->label('Yolcu')->formatStateUsing(fn (Booking $r) => $r->customer?->full_name),
                TextColumn::make('customer.phone')->label('Telefon'),
                TextColumn::make('tour.title')->label('Tur')->description(fn (Booking $r) => $r->tour?->start_date?->format('d.m.Y')),
                TextColumn::make('customer.passport_expiry_date')->label('Pasaport Bitiş')->date('d.m.Y')
                    ->placeholder('Pasaport bilgisi yok')->color('danger'),
            ])
            ->recordActions([
                Action::make('open')->label('Aç')->url(fn (Booking $r) => BookingResource::getUrl('edit', ['record' => $r])),
            ]);
    }
}
