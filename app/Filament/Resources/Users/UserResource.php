<?php

namespace App\Filament\Resources\Users;

use App\Filament\Concerns\AdminOnly;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class UserResource extends Resource
{
    use AdminOnly;

    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Ayarlar';

    protected static ?int $navigationSort = 9;

    protected static ?string $modelLabel = 'Kullanıcı';

    protected static ?string $pluralModelLabel = 'Kullanıcılar';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Ad Soyad')->required(),
            TextInput::make('email')->label('E-posta')->email()->required()->unique(ignoreRecord: true),
            TextInput::make('phone')->label('Telefon')->tel(),
            Select::make('role')->label('Rol')->options(User::ROLES)->default('staff')->required()
                ->helperText('Personel: turlar, müşteriler, rezervasyonlar. Yönetici: + muhasebe, ayarlar, kullanıcılar.'),
            TextInput::make('password')->label('Şifre')->password()->revealable()
                ->required(fn (string $operation) => $operation === 'create')
                ->dehydrated(fn ($state) => filled($state))->minLength(8)
                ->helperText(fn (string $operation) => $operation === 'edit' ? 'Değiştirmek istemiyorsanız boş bırakın.' : null),
            Toggle::make('is_active')->label('Aktif (giriş yapabilir)')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Ad')->searchable(),
                TextColumn::make('email')->label('E-posta'),
                TextColumn::make('role')->label('Rol')->badge()->formatStateUsing(fn ($state) => User::ROLES[$state] ?? $state),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->hidden(fn (User $r) => $r->is(auth()->user())),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageUsers::route('/')];
    }
}
