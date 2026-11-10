<?php

namespace App\Filament\Resources\Tours\Pages;

use App\Filament\Resources\Tours\TourResource;
use App\Models\Tour;
use App\Support\Options;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditTour extends EditRecord
{
    protected static string $resource = TourResource::class;

    public function getSubheading(): ?string
    {
        /** @var Tour $tour */
        $tour = $this->getRecord();
        $bookings = $tour->activeBookings()->with('transactions')->get();
        $isAdmin = auth()->user()?->isAdmin();

        $text = $bookings->count().' yolcu'.($tour->capacity ? ' / '.$tour->capacity.' kontenjan' : '');
        if ($isAdmin) {
            $sales = $bookings->sum->total;
            $paid = $bookings->sum->paid;
            $expense = $tour->transactions()->where('type', 'expense')->whereNull('booking_id')->sum('amount_try');
            $text .= ' · Satış: '.Options::money($sales, $tour->currency)
                .' · Tahsil: '.Options::money($paid, $tour->currency)
                .' · Gider: '.Options::money($expense, 'TRY');
        }

        return $text;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('passengers')->label('Yolcu Listesi')->icon(Heroicon::OutlinedPrinter)->color('gray')
                ->url(fn () => route('admin.export.passengers', $this->getRecord()))->openUrlInNewTab(),
            Action::make('view_site')->label('Sitede Gör')->icon(Heroicon::OutlinedEye)->color('gray')
                ->url(fn () => route('tours.show', $this->getRecord()))->openUrlInNewTab(),
            DeleteAction::make(),
            RestoreAction::make(),
            ForceDeleteAction::make(),
        ];
    }
}
