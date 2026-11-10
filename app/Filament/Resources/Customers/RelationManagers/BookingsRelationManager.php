<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookings';

    protected static ?string $title = 'Katıldığı Turlar';

    protected static ?string $modelLabel = 'Tur Kaydı';

    protected static string|\BackedEnum|null $icon = Heroicon::OutlinedTicket;

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components(BookingResource::formComponents(withCustomer: false));
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['tour', 'transactions']))
            ->defaultSort('registered_at', 'desc')
            ->columns(BookingResource::tableColumns(withCustomer: false))
            ->headerActions([CreateAction::make()->label('Tura Kaydet')])
            ->recordActions([
                EditAction::make()->url(fn (Booking $r) => BookingResource::getUrl('edit', ['record' => $r])),
                BookingResource::paymentAction(),
                Action::make('statement')->label('Döküm')->icon(Heroicon::OutlinedPrinter)
                    ->url(fn (Booking $r) => route('admin.export.booking', $r))->openUrlInNewTab(),
            ]);
    }
}
