<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Tours\TourResource;
use App\Models\Tour;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UpcomingTours extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Yaklaşan Turlar';

    public function table(Table $table): Table
    {
        return $table
            ->query(Tour::query()->where('start_date', '>=', today())->whereIn('status', ['published', 'draft'])
                ->withCount('activeBookings')->orderBy('start_date'))
            ->paginated([5, 10])
            ->columns([
                TextColumn::make('title')->label('Tur')->weight('bold'),
                TextColumn::make('start_date')->label('Tarih')->date('d.m.Y')
                    ->description(fn (Tour $r) => $r->start_date?->diffForHumans()),
                TextColumn::make('active_bookings_count')->label('Doluluk')->badge()
                    ->formatStateUsing(fn (Tour $r) => $r->active_bookings_count.($r->capacity ? ' / '.$r->capacity : ''))
                    ->color(fn (Tour $r) => ! $r->capacity ? 'gray' : ($r->active_bookings_count >= $r->capacity ? 'danger' : 'success')),
                TextColumn::make('pending')->label('Ön Kayıt')
                    ->state(fn (Tour $r) => $r->bookings()->where('status', 'pending')->count()),
            ])
            ->recordActions([
                Action::make('open')->label('Aç')->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Tour $r) => TourResource::getUrl('edit', ['record' => $r])),
                Action::make('list')->label('Yolcu Listesi')->icon(Heroicon::OutlinedPrinter)
                    ->url(fn (Tour $r) => route('admin.export.passengers', $r))->openUrlInNewTab(),
            ]);
    }
}
