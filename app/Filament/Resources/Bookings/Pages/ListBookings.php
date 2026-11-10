<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return [
            'active' => Tab::make('Aktif')->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['pending', 'confirmed'])),
            'pending' => Tab::make('Ön Kayıt')->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending')),
            'completed' => Tab::make('Tamamlanan')->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed')),
            'cancelled' => Tab::make('İptal')->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled')),
            'all' => Tab::make('Tümü'),
        ];
    }
}
