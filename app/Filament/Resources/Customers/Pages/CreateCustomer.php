<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Inquiry;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    public ?int $inquiryId = null;

    public function mount(): void
    {
        parent::mount();

        // Bilgi talebinden müşteriye dönüştürme: ?inquiry=ID
        if ($inquiry = Inquiry::find(request()->integer('inquiry'))) {
            $this->inquiryId = $inquiry->id;
            [$first, $last] = array_pad(preg_split('/\s+(?=\S+$)/u', trim($inquiry->name)), 2, '');
            $this->form->fill(array_merge($this->form->getRawState(), [
                'first_name' => $first, 'last_name' => $last, 'phone' => $inquiry->phone,
                'email' => $inquiry->email, 'source' => 'website', 'kvkk_consent' => true,
                'notes' => $inquiry->message,
            ]));
        }
    }

    protected function afterCreate(): void
    {
        if ($this->inquiryId) {
            Inquiry::whereKey($this->inquiryId)->update(['status' => 'converted', 'customer_id' => $this->getRecord()->id]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
