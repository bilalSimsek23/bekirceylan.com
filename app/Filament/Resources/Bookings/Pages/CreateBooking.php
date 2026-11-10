<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    public function mount(): void
    {
        parent::mount();

        // ?customer=ID&tour=ID ile ön doldurma
        $fill = array_filter([
            'customer_id' => request()->integer('customer') ?: null,
            'tour_id' => request()->integer('tour') ?: null,
        ]);
        if ($fill) {
            $this->form->fill(array_merge($this->form->getRawState(), $fill));
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
