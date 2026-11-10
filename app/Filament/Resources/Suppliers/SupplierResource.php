<?php

namespace App\Filament\Resources\Suppliers;

use App\Filament\Concerns\AdminOnly;
use App\Filament\Resources\Transactions\TransactionResource;
use App\Models\Supplier;
use App\Support\Options;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SupplierResource extends Resource
{
    use AdminOnly;

    protected static ?string $model = Supplier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|UnitEnum|null $navigationGroup = 'Muhasebe';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Tedarikçi';

    protected static ?string $pluralModelLabel = 'Tedarikçiler';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Firma / Kişi')->required(),
            Select::make('type')->label('Tür')->options(Options::SUPPLIER_TYPES),
            TextInput::make('contact_person')->label('Yetkili'),
            TextInput::make('phone')->label('Telefon')->tel(),
            TextInput::make('email')->label('E-posta')->email(),
            TextInput::make('iban')->label('IBAN'),
            TextInput::make('tax_office')->label('Vergi Dairesi'),
            TextInput::make('tax_no')->label('Vergi No'),
            Textarea::make('address')->label('Adres')->columnSpanFull(),
            Textarea::make('notes')->label('Notlar')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withSum(['transactions as paid_try' => fn ($q) => $q->where('type', 'expense')], 'amount_try'))
            ->columns([
                TextColumn::make('name')->label('Firma')->searchable()->weight('bold')->description(fn (Supplier $r) => $r->contact_person),
                TextColumn::make('type')->label('Tür')->badge()->formatStateUsing(fn ($state) => Options::SUPPLIER_TYPES[$state] ?? $state),
                TextColumn::make('phone')->label('Telefon')->copyable(),
                TextColumn::make('paid_try')->label('Toplam Ödenen (TL)')->alignEnd()
                    ->formatStateUsing(fn ($state) => Options::money($state, 'TRY')),
            ])
            ->filters([SelectFilter::make('type')->label('Tür')->options(Options::SUPPLIER_TYPES)])
            ->recordActions([
                Action::make('movements')->label('Ödemeler')->icon(Heroicon::OutlinedListBullet)
                    ->url(fn (Supplier $r) => TransactionResource::getUrl('index', ['filters' => ['supplier_id' => ['value' => $r->id]]])),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageSuppliers::route('/')];
    }
}
