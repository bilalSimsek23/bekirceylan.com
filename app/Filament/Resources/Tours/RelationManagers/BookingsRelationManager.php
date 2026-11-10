<?php

namespace App\Filament\Resources\Tours\RelationManagers;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookings';

    protected static ?string $title = 'Yolcular / Rezervasyonlar';

    protected static ?string $modelLabel = 'Rezervasyon';

    public function form(Schema $schema): Schema
    {
        return $schema->components(BookingResource::formComponents(withTour: false));
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['customer', 'transactions']))
            ->defaultSort('group_code')
            ->columns(BookingResource::tableColumns(withTour: false))
            ->headerActions([
                CreateAction::make()->label('Yolcu Ekle')
                    ->mutateDataUsing(function (array $data) {
                        $tour = $this->getOwnerRecord();
                        $data['currency'] ??= $tour->currency;

                        return $data;
                    })
                    ->fillForm(fn () => ['currency' => $this->getOwnerRecord()->currency, 'price' => $this->getOwnerRecord()->price ?? 0, 'status' => 'pending', 'registered_at' => now()]),
            ])
            ->recordActions([
                EditAction::make()->url(fn (Booking $r) => BookingResource::getUrl('edit', ['record' => $r])),
                BookingResource::paymentAction(),
                Action::make('statement')->label('Döküm')->icon(Heroicon::OutlinedPrinter)->iconButton()
                    ->url(fn (Booking $r) => route('admin.export.booking', $r))->openUrlInNewTab(),
                DeleteAction::make()->iconButton(),
            ]);
    }
}
