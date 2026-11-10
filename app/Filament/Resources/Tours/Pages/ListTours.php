<?php

namespace App\Filament\Resources\Tours\Pages;

use App\Filament\Resources\Tours\TourResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTours extends ListRecords
{
    protected static string $resource = TourResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return [
            'upcoming' => Tab::make('Yaklaşan / Tarihsiz')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereRaw('(start_date IS NULL OR start_date >= ?)', [today()->toDateString()])),
            'past' => Tab::make('Geçmiş')->modifyQueryUsing(fn (Builder $query) => $query->where('start_date', '<', today())),
            'all' => Tab::make('Tümü'),
        ];
    }
}
