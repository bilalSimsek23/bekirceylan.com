<?php

namespace App\Filament\Resources\CashAccounts;

use App\Filament\Concerns\AdminOnly;
use App\Filament\Resources\Transactions\TransactionResource;
use App\Models\CashAccount;
use App\Support\Options;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CashAccountResource extends Resource
{
    use AdminOnly;

    protected static ?string $model = CashAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static string|UnitEnum|null $navigationGroup = 'Muhasebe';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Kasa / Banka Hesabı';

    protected static ?string $pluralModelLabel = 'Kasa & Banka';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Hesap Adı')->required(),
            Select::make('type')->label('Tür')->options(Options::ACCOUNT_TYPES)->default('cash')->required(),
            Select::make('currency')->label('Para Birimi')->options(Options::CURRENCIES)->default('TRY')->required()
                ->disabledOn('edit')->helperText('Hareket girildikten sonra değiştirilemez.'),
            TextInput::make('opening_balance')->label('Açılış Bakiyesi')->numeric()->default(0),
            TextInput::make('bank_name')->label('Banka'),
            TextInput::make('iban')->label('IBAN'),
            Textarea::make('notes')->label('Not')->columnSpanFull(),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withSum(['transactions as income_sum' => fn ($q) => $q->where('type', 'income')], 'amount')
                ->withSum(['transactions as expense_sum' => fn ($q) => $q->where('type', 'expense')], 'amount'))
            ->columns([
                TextColumn::make('name')->label('Hesap')->weight('bold')->description(fn (CashAccount $r) => $r->iban),
                TextColumn::make('type')->label('Tür')->formatStateUsing(fn ($state) => Options::ACCOUNT_TYPES[$state] ?? $state)->badge(),
                TextColumn::make('currency')->label('Döviz'),
                TextColumn::make('income_sum')->label('Giren')->alignEnd()->color('success')
                    ->formatStateUsing(fn (CashAccount $r) => Options::money($r->income_sum, $r->currency)),
                TextColumn::make('expense_sum')->label('Çıkan')->alignEnd()->color('danger')
                    ->formatStateUsing(fn (CashAccount $r) => Options::money($r->expense_sum, $r->currency)),
                TextColumn::make('balance')->label('Bakiye')->alignEnd()->weight('bold')->size('lg')
                    ->state(fn (CashAccount $r) => Options::money($r->balance, $r->currency)),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->recordActions([
                Action::make('movements')->label('Hareketler')->icon(Heroicon::OutlinedListBullet)
                    ->url(fn (CashAccount $r) => TransactionResource::getUrl('index', ['filters' => ['cash_account_id' => ['value' => $r->id]]])),
                EditAction::make(),
                DeleteAction::make()->visible(fn (CashAccount $r) => ! $r->transactions()->exists()),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageCashAccounts::route('/')];
    }
}
