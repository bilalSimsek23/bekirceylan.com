<?php

namespace App\Filament\Resources\Bookings\RelationManagers;

use App\Filament\Resources\Transactions\TransactionResource;
use App\Models\Booking;
use App\Support\Options;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $title = 'Ödemeler';

    protected static ?string $modelLabel = 'Ödeme';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public function form(Schema $schema): Schema
    {
        /** @var Booking $booking */
        $booking = $this->getOwnerRecord();

        return $schema->components(TransactionResource::paymentComponents($booking));
    }

    public function table(Table $table): Table
    {
        /** @var Booking $booking */
        $booking = $this->getOwnerRecord();

        return $table
            ->defaultSort('date')
            ->description('Toplam: '.Options::money($booking->total, $booking->currency)
                .' · Ödenen: '.Options::money($booking->paid, $booking->currency)
                .' · Kalan: '.Options::money($booking->balance, $booking->currency))
            ->columns([
                TextColumn::make('date')->label('Tarih')->date('d.m.Y'),
                TextColumn::make('type')->label('Tip')->badge()
                    ->formatStateUsing(fn ($state) => $state === 'income' ? 'Tahsilat' : 'İade')
                    ->color(fn ($state) => $state === 'income' ? 'success' : 'danger'),
                TextColumn::make('account.name')->label('Hesap'),
                TextColumn::make('method')->label('Şekil')->formatStateUsing(fn ($state) => Options::PAYMENT_METHODS[$state] ?? $state),
                TextColumn::make('amount')->label('Tutar')->alignEnd()
                    ->formatStateUsing(fn ($record) => Options::money($record->amount, $record->currency)),
                TextColumn::make('booking_amount')->label('Karşılık ('.$booking->currency.')')->alignEnd()
                    ->formatStateUsing(fn ($record) => Options::money($record->booking_amount, $booking->currency)),
                TextColumn::make('document_no')->label('Makbuz No'),
                TextColumn::make('description')->label('Açıklama'),
            ])
            ->headerActions([
                CreateAction::make()->label('Tahsilat Ekle')
                    ->mutateDataUsing(fn (array $data) => $data + ['type' => 'income', 'category' => 'tour_payment']),
                CreateAction::make('refund')->label('İade Yap')->color('danger')
                    ->modalHeading('Müşteriye İade')
                    ->mutateDataUsing(fn (array $data) => $data + ['type' => 'expense', 'category' => 'refund']),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
