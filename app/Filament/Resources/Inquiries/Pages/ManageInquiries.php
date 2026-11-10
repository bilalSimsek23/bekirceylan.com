<?php

namespace App\Filament\Resources\Inquiries\Pages;

use App\Filament\Resources\Inquiries\InquiryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManageInquiries extends ManageRecords
{
    protected static string $resource = InquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Talep Ekle (telefon vb.)')];
    }

    public function getTabs(): array
    {
        return [
            'open' => Tab::make('Açık')->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['new', 'contacted'])),
            'new' => Tab::make('Yeni')->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'new')),
            'all' => Tab::make('Tümü'),
        ];
    }
}
