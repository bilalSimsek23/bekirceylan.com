<?php

namespace App\Filament\Resources\TourCategories;

use App\Models\TourCategory;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ManageRecords;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class TourCategoryResource extends Resource
{
    protected static ?string $model = TourCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Tur Yönetimi';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Kategori';

    protected static ?string $pluralModelLabel = 'Tur Kategorileri';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Ad')->required()->maxLength(100)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, Set $set, ?TourCategory $record) => $record ? null : $set('slug', Str::slug($state))),
            TextInput::make('slug')->label('URL (slug)')->required()->unique(ignoreRecord: true)
                ->helperText('Örn: umre → /turlar?kategori=umre'),
            TextInput::make('badge')->label('Kart Etiketi')->placeholder('UMRE')->maxLength(20),
            TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
            Textarea::make('description')->label('Açıklama')->columnSpanFull(),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')->label('Ad')->searchable(),
                TextColumn::make('slug')->label('Slug'),
                TextColumn::make('badge')->label('Etiket')->badge(),
                TextColumn::make('tours_count')->label('Tur Sayısı')->counts('tours'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageTourCategories::route('/')];
    }
}
