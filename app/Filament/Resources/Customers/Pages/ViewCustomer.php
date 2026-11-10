<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use App\Support\Options;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    public function getTitle(): string
    {
        return $this->getRecord()->full_name;
    }

    public function getSubheading(): ?string
    {
        /** @var Customer $c */
        $c = $this->getRecord();
        $bookings = $c->bookings()->with('transactions')->where('status', '!=', 'cancelled')->get();
        $balances = $bookings->groupBy('currency')
            ->map(fn ($items, $cur) => Options::money($items->sum->balance, $cur))
            ->filter(fn ($v) => ! str_starts_with($v, '0,00'))
            ->implode(' + ');

        return $bookings->count().' tur kaydı'
            .($c->passportExpiresSoon() ? ' · ⚠ Pasaport 6 aydan kısa sürede bitiyor' : '')
            .(auth()->user()?->isAdmin() && $balances ? ' · Kalan borç: '.$balances : '');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('new_booking')->label('Tura Kaydet')->icon(Heroicon::OutlinedTicket)
                ->url(fn () => BookingResource::getUrl('create', ['customer' => $this->getRecord()->id])),
            Action::make('whatsapp')->label('WhatsApp')->icon(Heroicon::OutlinedChatBubbleLeftRight)->color('success')
                ->visible(fn () => filled($this->getRecord()->phone))
                ->url(fn () => 'https://wa.me/'.CustomerResource::waPhone($this->getRecord()->phone))->openUrlInNewTab(),
            EditAction::make(),
        ];
    }
}
