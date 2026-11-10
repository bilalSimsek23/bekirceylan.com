<?php

namespace App\Filament\Resources\Tours;

use App\Models\Tour;
use App\Support\Options;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class TourResource extends Resource
{
    protected static ?string $model = Tour::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeEuropeAfrica;

    protected static string|UnitEnum|null $navigationGroup = 'Tur Yönetimi';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Tur';

    protected static ?string $pluralModelLabel = 'Turlar';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('tour')->columnSpanFull()->persistTabInQueryString()->tabs([
                Tab::make('Genel Bilgiler')->icon(Heroicon::OutlinedInformationCircle)->columns(2)->schema([
                    TextInput::make('title')->label('Tur Adı')->required()->maxLength(200),
                    Select::make('tour_category_id')->label('Kategori')->relationship('category', 'name')
                        ->preload()->createOptionForm([
                            TextInput::make('name')->label('Ad')->required(),
                            TextInput::make('badge')->label('Kart Etiketi'),
                        ]),
                    TextInput::make('location')->label('Lokasyon / Rota')->placeholder('Mekke & Medine'),
                    TextInput::make('slug')->label('URL (slug)')->unique(ignoreRecord: true)
                        ->helperText('Boş bırakırsanız tur adından otomatik oluşur.'),
                    DatePicker::make('start_date')->label('Başlangıç Tarihi')->native(false)->displayFormat('d.m.Y'),
                    DatePicker::make('end_date')->label('Bitiş Tarihi')->native(false)->displayFormat('d.m.Y')->afterOrEqual('start_date'),
                    TextInput::make('duration_text')->label('Süre')->placeholder('4 Gece 5 Gün'),
                    TextInput::make('departure_city')->label('Hareket Noktası')->placeholder('İstanbul Havalimanı'),
                    TextInput::make('transport')->label('Ulaşım')->placeholder('Uçak (THY)'),
                    Select::make('status')->label('Durum')->options(Options::TOUR_STATUSES)->default('published')->required(),
                    Textarea::make('summary')->label('Kısa Açıklama (kartta görünür)')->maxLength(500)->rows(2)->columnSpanFull(),
                    RichEditor::make('description')->label('Detaylı Açıklama')->columnSpanFull()
                        ->fileAttachmentsDirectory('tours/content'),
                    Toggle::make('is_featured')->label('Ana sayfada göster')->default(true),
                    TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                ]),

                Tab::make('Program & Konaklama')->icon(Heroicon::OutlinedMap)->schema([
                    Repeater::make('itinerary')->label('Gün Gün Program')
                        ->schema([
                            TextInput::make('day')->label('Gün')->placeholder('1. Gün')->columnSpan(1),
                            TextInput::make('title')->label('Başlık')->required()->columnSpan(3),
                            Textarea::make('description')->label('Açıklama')->rows(3)->columnSpanFull(),
                        ])->columns(4)->reorderableWithButtons()->collapsible()
                        ->itemLabel(fn (array $state) => trim(($state['day'] ?? '').' '.($state['title'] ?? '')))
                        ->addActionLabel('Gün Ekle')->defaultItems(0),
                    Repeater::make('hotels')->label('Oteller')
                        ->schema([
                            TextInput::make('city')->label('Şehir'),
                            TextInput::make('name')->label('Otel')->required(),
                            TextInput::make('nights')->label('Gece')->numeric(),
                            Select::make('stars')->label('Yıldız')->options([3 => '3★', 4 => '4★', 5 => '5★']),
                            TextInput::make('note')->label('Not')->placeholder('Harem\'e 200 m')->columnSpanFull(),
                        ])->columns(4)->addActionLabel('Otel Ekle')->defaultItems(0),
                    Grid::make(2)->schema([
                        Repeater::make('included')->label('Fiyata Dahil')
                            ->simple(TextInput::make('item')->required())->addActionLabel('Ekle')->defaultItems(0),
                        Repeater::make('excluded')->label('Fiyata Dahil Değil')
                            ->simple(TextInput::make('item')->required())->addActionLabel('Ekle')->defaultItems(0),
                    ]),
                ]),

                Tab::make('Fiyat & Kontenjan')->icon(Heroicon::OutlinedBanknotes)->columns(3)->schema([
                    Select::make('currency')->label('Para Birimi')->options(Options::CURRENCIES)->default('USD')->required(),
                    TextInput::make('price')->label('Kişi Başı Fiyat')->numeric()->minValue(0)
                        ->helperText('Boşsa sitede fiyat etiketi gösterilir.'),
                    TextInput::make('old_price')->label('Eski Fiyat (üstü çizili)')->numeric()->minValue(0),
                    TextInput::make('capacity')->label('Kontenjan (kişi)')->numeric()->minValue(0)
                        ->helperText('Kalan yer = kontenjan − aktif rezervasyon'),
                    TextInput::make('price_label')->label('Fiyat Yoksa Etiket')->default('Bilgi Alınız'),
                    Repeater::make('room_prices')->label('Oda Tipine Göre Fiyatlar')->columnSpanFull()
                        ->schema([
                            Select::make('type')->label('Oda Tipi')->options(Options::ROOM_TYPES)->required(),
                            TextInput::make('price')->label('Kişi Başı')->numeric()->required(),
                        ])->columns(2)->grid(2)->addActionLabel('Oda Fiyatı Ekle')->defaultItems(0),
                ]),

                Tab::make('Görseller')->icon(Heroicon::OutlinedPhoto)->schema([
                    FileUpload::make('cover_image')->label('Kapak Görseli')->image()->directory('tours')
                        ->imageEditor()->imageAspectRatio('16:10')->maxSize(5120),
                    FileUpload::make('gallery')->label('Galeri')->image()->multiple()->reorderable()
                        ->directory('tours/gallery')->maxFiles(20)->maxSize(5120)->panelLayout('grid'),
                ]),

                Tab::make('SEO & İç Notlar')->icon(Heroicon::OutlinedMagnifyingGlass)->schema([
                    TextInput::make('meta_title')->label('SEO Başlık')->maxLength(70),
                    Textarea::make('meta_description')->label('SEO Açıklama')->maxLength(160)->rows(2),
                    Section::make('Ofis İçi Notlar')->description('Sitede görünmez.')->schema([
                        Textarea::make('internal_notes')->hiddenLabel()->rows(4),
                    ]),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('category')->withCount('activeBookings'))
            ->defaultSort('start_date', 'desc')
            ->columns([
                ImageColumn::make('cover_image')->label('')->disk('public')->imageHeight(44)->square(),
                TextColumn::make('title')->label('Tur')->searchable()->sortable()->weight('bold')
                    ->description(fn (Tour $r) => $r->location),
                TextColumn::make('category.name')->label('Kategori')->badge(),
                TextColumn::make('start_date')->label('Tarih')->date('d.m.Y')->sortable()
                    ->description(fn (Tour $r) => $r->end_date?->format('d.m.Y')),
                TextColumn::make('price')->label('Fiyat')
                    ->formatStateUsing(fn (Tour $r) => $r->formattedPrice())->placeholder('—'),
                TextColumn::make('active_bookings_count')->label('Doluluk')
                    ->formatStateUsing(fn (Tour $r) => $r->active_bookings_count.($r->capacity ? ' / '.$r->capacity : ''))
                    ->badge()
                    ->color(fn (Tour $r) => ! $r->capacity ? 'gray' : ($r->active_bookings_count >= $r->capacity ? 'danger' : ($r->active_bookings_count / $r->capacity > .7 ? 'warning' : 'success'))),
                TextColumn::make('status')->label('Durum')->badge()
                    ->formatStateUsing(fn ($state) => Options::TOUR_STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) { 'published' => 'success', 'draft' => 'gray', 'completed' => 'info', default => 'danger' }),
                ToggleColumn::make('is_featured')->label('Ana Sayfa'),
            ])
            ->filters([
                SelectFilter::make('tour_category_id')->label('Kategori')->relationship('category', 'name'),
                SelectFilter::make('status')->label('Durum')->options(Options::TOUR_STATUSES),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    Action::make('passengers')->label('Yolcu Listesi')->icon(Heroicon::OutlinedPrinter)
                        ->url(fn (Tour $r) => route('admin.export.passengers', $r))->openUrlInNewTab(),
                    Action::make('csv')->label('Excel (CSV)')->icon(Heroicon::OutlinedArrowDownTray)
                        ->url(fn (Tour $r) => route('admin.export.passengers.csv', $r)),
                    Action::make('view_site')->label('Sitede Gör')->icon(Heroicon::OutlinedEye)
                        ->url(fn (Tour $r) => route('tours.show', $r))->openUrlInNewTab(),
                    ReplicateAction::make()->label('Kopyala (yeni dönem)')
                        ->excludeAttributes(['slug', 'active_bookings_count'])
                        ->mutateRecordDataUsing(fn (array $data) => [...$data, 'title' => $data['title'].' (Kopya)', 'status' => 'draft'])
                        ->successRedirectUrl(fn (Tour $replica) => static::getUrl('edit', ['record' => $replica])),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make(), RestoreBulkAction::make()]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\BookingsRelationManager::class,
            RelationManagers\TransactionsRelationManager::class,
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTours::route('/'),
            'create' => Pages\CreateTour::route('/create'),
            'edit' => Pages\EditTour::route('/{record}/edit'),
        ];
    }
}
