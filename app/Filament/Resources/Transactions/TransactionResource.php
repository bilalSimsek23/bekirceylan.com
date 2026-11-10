<?php

namespace App\Filament\Resources\Transactions;

use App\Filament\Concerns\AdminOnly;
use App\Models\Booking;
use App\Models\CashAccount;
use App\Models\Transaction;
use App\Services\ExchangeRateService;
use App\Support\Options;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TransactionResource extends Resource
{
    use AdminOnly;

    protected static ?string $model = Transaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = 'Muhasebe';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Kasa Hareketi';

    protected static ?string $pluralModelLabel = 'Gelir / Gider';

    protected static function accountSelect(): Select
    {
        return Select::make('cash_account_id')->label('Kasa / Hesap')->required()
            ->options(fn () => CashAccount::query()->where('is_active', true)->get()
                ->mapWithKeys(fn ($a) => [$a->id => $a->name.' ('.$a->currency.')']))
            ->live()
            ->afterStateUpdated(function ($state, Set $set) {
                $currency = CashAccount::find($state)?->currency;
                $set('currency', $currency);
                $set('exchange_rate', $currency && $currency !== 'TRY' ? app(ExchangeRateService::class)->rate($currency) : 1);
            });
    }

    /** Kur ve rezervasyon karşılığı alanları */
    protected static function rateComponents(?Booking $booking = null): array
    {
        $bookingCurrency = fn (Get $get) => $booking?->currency ?? Booking::find($get('booking_id'))?->currency;

        return [
            TextInput::make('exchange_rate')->label(fn (Get $get) => 'Kur (1 '.($get('currency') ?: '?').' = ? TL)')
                ->numeric()->default(1)
                ->visible(fn (Get $get) => $get('currency') && $get('currency') !== 'TRY'),
            TextInput::make('booking_amount')->label(fn (Get $get) => 'Rezervasyona yansıyacak tutar ('.$bookingCurrency($get).')')
                ->numeric()
                ->helperText('Boş bırakılırsa güncel kurdan otomatik hesaplanır.')
                ->visible(fn (Get $get) => ($bc = $bookingCurrency($get)) && $get('currency') && $bc !== $get('currency')),
        ];
    }

    /** Rezervasyona tahsilat formu */
    public static function paymentComponents(?Booking $booking = null): array
    {
        return [
            Grid::make(2)->schema([
                static::accountSelect(),
                TextInput::make('amount')->label('Tutar')->numeric()->required()->minValue(0.01)
                    ->default($booking && $booking->balance > 0 ? $booking->balance : null)
                    ->helperText($booking ? 'Kalan bakiye: '.Options::money($booking->balance, $booking->currency) : null),
                Hidden::make('currency')->dehydrated(false),
                ...static::rateComponents($booking),
                Select::make('method')->label('Ödeme Şekli')->options(Options::PAYMENT_METHODS)->default('cash')->required(),
                DatePicker::make('date')->label('Tarih')->default(now())->required()->native(false)->displayFormat('d.m.Y'),
                TextInput::make('document_no')->label('Makbuz / Dekont No'),
                TextInput::make('description')->label('Açıklama')->default('Tur ödemesi'),
            ]),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            ToggleButtons::make('type')->label('Hareket Tipi')->options(Options::TRANSACTION_TYPES)
                ->colors(['income' => 'success', 'expense' => 'danger'])
                ->icons(['income' => Heroicon::OutlinedArrowDownLeft, 'expense' => Heroicon::OutlinedArrowUpRight])
                ->inline()->required()->default('income')->live()
                ->afterStateUpdated(fn (Set $set) => $set('category', null)),
            Select::make('category')->label('Kategori')->required()
                ->options(fn (Get $get) => $get('type') === 'expense' ? Options::EXPENSE_CATEGORIES : Options::INCOME_CATEGORIES),
            static::accountSelect(),
            TextInput::make('amount')->label('Tutar')->numeric()->required()->minValue(0.01),
            Hidden::make('currency')->dehydrated(false)
                ->afterStateHydrated(fn (Set $set, Get $get) => $set('currency', CashAccount::find($get('cash_account_id'))?->currency)),
            DatePicker::make('date')->label('Tarih')->default(now())->required()->native(false)->displayFormat('d.m.Y'),
            Select::make('method')->label('Ödeme Şekli')->options(Options::PAYMENT_METHODS),
            Select::make('booking_id')->label('Rezervasyon (müşteri tahsilatı / iadesi)')
                ->relationship('booking', 'booking_no')
                ->getOptionLabelFromRecordUsing(fn (Booking $b) => $b->booking_no.' · '.$b->customer?->full_name.' · '.$b->tour?->title)
                ->searchable()->live(),
            ...static::rateComponents(),
            Select::make('tour_id')->label('Tur (tur maliyeti ise)')->relationship('tour', 'title')->searchable()->preload()
                ->hidden(fn (Get $get) => filled($get('booking_id'))),
            Select::make('supplier_id')->label('Tedarikçi')->relationship('supplier', 'name')->searchable()->preload()
                ->createOptionForm([TextInput::make('name')->label('Ad')->required(), Select::make('type')->label('Tip')->options(Options::SUPPLIER_TYPES)])
                ->visible(fn (Get $get) => $get('type') === 'expense'),
            TextInput::make('document_no')->label('Belge / Fatura No'),
            TextInput::make('description')->label('Açıklama')->columnSpanFull(),
            FileUpload::make('attachment')->label('Belge (fatura, dekont)')->directory('transactions')
                ->acceptedFileTypes(['image/*', 'application/pdf'])->maxSize(5120)->columnSpanFull(),
        ])->columns(2);
    }

    public static function tableColumns(): array
    {
        return [
            TextColumn::make('date')->label('Tarih')->date('d.m.Y')->sortable(),
            TextColumn::make('type')->label('Tip')->badge()
                ->formatStateUsing(fn ($state) => Options::TRANSACTION_TYPES[$state] ?? $state)
                ->color(fn ($state) => $state === 'income' ? 'success' : 'danger'),
            TextColumn::make('category')->label('Kategori')->formatStateUsing(fn ($state) => Options::categories()[$state] ?? $state),
            TextColumn::make('description')->label('Açıklama')->wrap()->searchable()
                ->description(fn (Transaction $r) => collect([$r->customer?->full_name, $r->tour?->title, $r->supplier?->name])->filter()->implode(' · ')),
            TextColumn::make('account.name')->label('Hesap')->toggleable(),
            TextColumn::make('amount')->label('Tutar')->alignEnd()->weight('bold')
                ->formatStateUsing(fn (Transaction $r) => Options::money($r->signed_amount, $r->currency))
                ->color(fn (Transaction $r) => $r->type === 'income' ? 'success' : 'danger'),
            TextColumn::make('amount_try')->label('TL Karşılığı')->alignEnd()
                ->formatStateUsing(fn (Transaction $r) => Options::money(($r->type === 'income' ? 1 : -1) * $r->amount_try, 'TRY'))
                ->summarize(Summarizer::make()->label('Net (TL)')
                    ->using(fn ($query) => Options::money(
                        (clone $query)->where('type', 'income')->sum('amount_try') - (clone $query)->where('type', 'expense')->sum('amount_try'), 'TRY'))),
            TextColumn::make('method')->label('Şekil')->formatStateUsing(fn ($state) => Options::PAYMENT_METHODS[$state] ?? $state)->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('document_no')->label('Belge No')->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['account', 'customer', 'tour', 'supplier']))
            ->defaultSort('date', 'desc')
            ->columns(static::tableColumns())
            ->filters([
                SelectFilter::make('type')->label('Tip')->options(Options::TRANSACTION_TYPES),
                SelectFilter::make('category')->label('Kategori')->options(Options::categories())->multiple(),
                SelectFilter::make('cash_account_id')->label('Hesap')->relationship('account', 'name'),
                SelectFilter::make('tour_id')->label('Tur')->relationship('tour', 'title')->searchable()->preload(),
                SelectFilter::make('supplier_id')->label('Tedarikçi')->relationship('supplier', 'name')->searchable()->preload(),
                Filter::make('date')->schema([
                    DatePicker::make('from')->label('Başlangıç')->native(false)->displayFormat('d.m.Y'),
                    DatePicker::make('until')->label('Bitiş')->native(false)->displayFormat('d.m.Y'),
                ])->query(fn (Builder $query, array $data) => $query
                    ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('date', '>=', $d))
                    ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('date', '<=', $d))),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageTransactions::route('/')];
    }
}
