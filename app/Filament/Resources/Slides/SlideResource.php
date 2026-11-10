<?php

namespace App\Filament\Resources\Slides;

use App\Models\Slide;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class SlideResource extends Resource
{
    protected static ?string $model = Slide::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Site Yönetimi';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Slayt';

    protected static ?string $pluralModelLabel = 'Ana Sayfa Slider';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('image')->label('Arka Plan Görseli')->image()->directory('slides')
                ->imageEditor()->maxSize(4096)->columnSpanFull()
                ->helperText('Önerilen: 1920×900 px, JPG'),
            TextInput::make('eyebrow')->label('Üst Etiket')->placeholder('HAC • UMRE • KÜLTÜR TURLARI'),
            TextInput::make('title')->label('Başlık')->required(),
            Textarea::make('subtitle')->label('Alt Metin')->columnSpanFull(),
            Grid::make(2)->schema([
                TextInput::make('primary_button_text')->label('1. Buton Metni')->default('Turlarımızı Keşfedin'),
                TextInput::make('primary_button_url')->label('1. Buton Linki')->default('#turlar'),
                TextInput::make('secondary_button_text')->label('2. Buton Metni')->default('Bizimle İletişime Geçin'),
                TextInput::make('secondary_button_url')->label('2. Buton Linki')->default('#iletisim'),
            ])->columnSpanFull(),
            TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
            Toggle::make('is_active')->label('Yayında')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('image')->label('Görsel')->disk('public')->imageHeight(50),
                TextColumn::make('title')->label('Başlık')->searchable(),
                TextColumn::make('eyebrow')->label('Etiket'),
                ToggleColumn::make('is_active')->label('Yayında'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageSlides::route('/')];
    }
}
