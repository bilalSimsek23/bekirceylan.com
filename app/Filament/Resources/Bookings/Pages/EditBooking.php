<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    public function getTitle(): string
    {
        return $this->getRecord()->booking_no.' – '.$this->getRecord()->customer?->full_name;
    }

    protected function getHeaderActions(): array
    {
        return [
            BookingResource::paymentAction()->after(fn () => $this->refreshFormData([])),
            Action::make('statement')->label('Döküm / Makbuz')->icon(Heroicon::OutlinedPrinter)->color('gray')
                ->url(fn () => route('admin.export.booking', $this->getRecord()))->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
