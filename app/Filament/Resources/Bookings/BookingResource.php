<?php

namespace App\Filament\Resources\Bookings;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Tour;
use App\Support\Options;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|UnitEnum|null $navigationGroup = 'Tur Yönetimi';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Rezervasyon';

    protected static ?string $pluralModelLabel = 'Rezervasyonlar';

    protected static ?string $recordTitleAttribute = 'booking_no';

    public static function getNavigationBadge(): ?string
    {
        $count = Booking::query()->where('status', 'pending')->count();

        return $count ?: null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    /** Rezervasyon form alanları – müşteri ve tur ilişki yöneticilerinde de kullanılır. */
    public static function formComponents(bool $withCustomer = true, bool $withTour = true): array
    {
        return [
            Section::make('Rezervasyon')->columns(3)->schema(array_values(array_filter([
                $withCustomer ? Select::make('customer_id')->label('Müşteri')->required()
                    ->relationship('customer', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn (Customer $c) => $c->full_name.($c->phone ? ' · '.$c->phone : ''))
                    ->searchable(['first_name', 'last_name', 'phone', 'tc_no', 'passport_no'])
                    ->createOptionForm(CustomerResource::quickCreateComponents())
                    ->columnSpan(2) : null,
                $withTour ? Select::make('tour_id')->label('Tur')->required()
                    ->relationship('tour', 'title', fn (Builder $query) => $query->orderByDesc('start_date'))
                    ->getOptionLabelFromRecordUsing(fn (Tour $t) => $t->label)
                    ->searchable()->preload()->live()
                    ->afterStateUpdated(fn ($state, Set $set) => static::fillPriceFromTour($state, $set))
                    ->columnSpan(2) : null,
                Select::make('status')->label('Durum')->options(Options::BOOKING_STATUSES)->default('pending')->required()->live(),
                Select::make('room_type')->label('Oda Tipi')->options(Options::ROOM_TYPES)->live()
                    ->afterStateUpdated(fn ($state, Get $get, Set $set) => static::fillRoomPrice($get('tour_id'), $state, $set)),
                TextInput::make('group_code')->label('Grup / Aile Kodu')->placeholder('YILMAZ-AILE')
                    ->helperText('Aynı odada / birlikte olacakları eşleştirir.'),
                TextInput::make('roommate_notes')->label('Oda Arkadaşı Notu'),
                DatePicker::make('registered_at')->label('Kayıt Tarihi')->default(now())->native(false)->displayFormat('d.m.Y'),
                Select::make('visa_status')->label('Vize Durumu')->options(Options::VISA_STATUSES),
                TextInput::make('seat_no')->label('Koltuk / Bilet No'),
                TextInput::make('cancel_reason')->label('İptal Nedeni')->visible(fn (Get $get) => $get('status') === 'cancelled')->columnSpan(2),
            ]))),
            Section::make('Ücret')->columns(4)->schema([
                Select::make('currency')->label('Para Birimi')->options(Options::CURRENCIES)->default('USD')->required()->live(),
                TextInput::make('price')->label('Tur Ücreti')->numeric()->default(0)->required()->live(onBlur: true),
                TextInput::make('extra_charges')->label('Ek Hizmet')->numeric()->default(0)->live(onBlur: true),
                TextInput::make('discount')->label('İndirim')->numeric()->default(0)->live(onBlur: true),
                TextEntry::make('total_preview')->label('Toplam')
                    ->state(fn (Get $get) => Options::money((float) $get('price') + (float) $get('extra_charges') - (float) $get('discount'), $get('currency'))),
                TextEntry::make('paid_preview')->label('Ödenen')->visibleOn('edit')
                    ->state(fn (?Booking $record) => $record ? Options::money($record->paid, $record->currency) : '-'),
                TextEntry::make('balance_preview')->label('Kalan')->visibleOn('edit')->color('danger')->weight('bold')
                    ->state(fn (?Booking $record) => $record ? Options::money($record->balance, $record->currency) : '-'),
            ]),
            Textarea::make('notes')->label('Notlar')->rows(2)->columnSpanFull(),
        ];
    }

    public static function fillPriceFromTour($tourId, Set $set): void
    {
        if ($tour = Tour::find($tourId)) {
            $set('currency', $tour->currency);
            $set('price', $tour->price ?? 0);
        }
    }

    public static function fillRoomPrice($tourId, $roomType, Set $set): void
    {
        $tour = Tour::find($tourId);
        $room = collect($tour?->room_prices ?? [])->firstWhere('type', $roomType);
        if ($room && filled($room['price'] ?? null)) {
            $set('price', $room['price']);
        }
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components(static::formComponents());
    }

    /** Tablo sütunları – ilişki yöneticilerinde de kullanılır. */
    public static function tableColumns(bool $withCustomer = true, bool $withTour = true): array
    {
        return array_values(array_filter([
            TextColumn::make('booking_no')->label('No')->searchable()->sortable()->copyable(),
            $withCustomer ? TextColumn::make('customer.first_name')->label('Müşteri')
                ->formatStateUsing(fn (Booking $r) => $r->customer?->full_name)
                ->description(fn (Booking $r) => $r->customer?->phone)
                ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('customer', fn (Builder $c) => $c
                    ->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")->orWhere('tc_no', 'like', "%{$search}%")))
                ->url(fn (Booking $r) => $r->customer_id ? CustomerResource::getUrl('view', ['record' => $r->customer_id]) : null) : null,
            $withTour ? TextColumn::make('tour.title')->label('Tur')
                ->description(fn (Booking $r) => $r->tour?->start_date?->format('d.m.Y'))->searchable()->wrap() : null,
            TextColumn::make('status')->label('Durum')->badge()
                ->formatStateUsing(fn ($state) => Options::BOOKING_STATUSES[$state] ?? $state)
                ->color(fn ($state) => Options::BOOKING_STATUS_COLORS[$state] ?? 'gray'),
            TextColumn::make('room_type')->label('Oda')->formatStateUsing(fn ($state) => Options::ROOM_TYPES[$state] ?? $state)->toggleable(),
            TextColumn::make('group_code')->label('Grup')->toggleable()->searchable(),
            TextColumn::make('visa_status')->label('Vize')->formatStateUsing(fn ($state) => Options::VISA_STATUSES[$state] ?? $state)->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('total')->label('Tutar')->state(fn (Booking $r) => Options::money($r->total, $r->currency))->alignEnd(),
            TextColumn::make('balance')->label('Kalan')->alignEnd()->weight('bold')
                ->state(fn (Booking $r) => $r->status === 'cancelled' ? '—' : Options::money($r->balance, $r->currency))
                ->color(fn (Booking $r) => $r->status !== 'cancelled' && $r->balance > 0 ? 'danger' : 'success'),
            TextColumn::make('registered_at')->label('Kayıt')->date('d.m.Y')->sortable()->toggleable(),
        ]));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['customer', 'tour', 'transactions']))
            ->defaultSort('created_at', 'desc')
            ->columns(static::tableColumns())
            ->filters([
                SelectFilter::make('tour_id')->label('Tur')->relationship('tour', 'title')->searchable()->preload(),
                SelectFilter::make('status')->label('Durum')->options(Options::BOOKING_STATUSES)->multiple(),
                SelectFilter::make('visa_status')->label('Vize')->options(Options::VISA_STATUSES),
                Filter::make('has_balance')->label('Borcu olanlar')
                    ->query(fn (Builder $query) => $query->where('status', '!=', 'cancelled')
                        ->whereRaw('(price + extra_charges - discount) > (SELECT COALESCE(SUM(CASE WHEN type = \'income\' THEN booking_amount ELSE -booking_amount END), 0) FROM transactions WHERE transactions.booking_id = bookings.id)')),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    static::paymentAction(),
                    Action::make('statement')->label('Döküm / Makbuz')->icon(Heroicon::OutlinedPrinter)
                        ->url(fn (Booking $r) => route('admin.export.booking', $r))->openUrlInNewTab(),
                    Action::make('whatsapp')->label('WhatsApp')->icon(Heroicon::OutlinedChatBubbleLeftRight)->color('success')
                        ->visible(fn (Booking $r) => filled($r->customer?->phone))
                        ->url(fn (Booking $r) => static::whatsappUrl($r))->openUrlInNewTab(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('confirm')->label('Kesin Kayda Çevir')->icon(Heroicon::OutlinedCheckCircle)
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'confirmed'])),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function whatsappUrl(Booking $r): string
    {
        $phone = preg_replace('/\D/', '', (string) $r->customer?->phone);
        $phone = str_starts_with($phone, '0') ? '9'.$phone : (strlen($phone) === 10 ? '90'.$phone : $phone);
        $text = "Merhaba {$r->customer?->first_name} Bey/Hanım, {$r->tour?->title} turu rezervasyonunuz ({$r->booking_no}) hakkında: "
            .($r->balance > 0 ? 'kalan bakiyeniz '.Options::money($r->balance, $r->currency).'.' : '');

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($text);
    }

    /** Tablo satırından hızlı tahsilat girişi. */
    public static function paymentAction(): Action
    {
        return Action::make('payment')->label('Tahsilat Ekle')->icon(Heroicon::OutlinedBanknotes)->color('success')
            ->visible(fn () => auth()->user()?->isAdmin())
            ->schema(fn (Booking $record) => \App\Filament\Resources\Transactions\TransactionResource::paymentComponents($record))
            ->action(function (array $data, Booking $record) {
                $record->transactions()->create($data + ['type' => 'income', 'category' => 'tour_payment']);
            })
            ->successNotificationTitle('Tahsilat kaydedildi');
    }

    public static function getRelations(): array
    {
        return [RelationManagers\TransactionsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}
