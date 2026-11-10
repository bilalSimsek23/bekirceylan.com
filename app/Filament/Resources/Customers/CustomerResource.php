<?php

namespace App\Filament\Resources\Customers;

use App\Models\Customer;
use App\Rules\TcKimlikNo;
use App\Support\Options;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Müşteriler';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Müşteri';

    protected static ?string $pluralModelLabel = 'Müşteriler';

    protected static ?string $recordTitleAttribute = 'first_name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'phone', 'tc_no', 'passport_no', 'email'];
    }

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        return $record->full_name;
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return array_filter(['Telefon' => $record->phone, 'TC' => $record->tc_no]);
    }

    /** Rezervasyon ekranından hızlı müşteri oluşturma */
    public static function quickCreateComponents(): array
    {
        return [
            Grid::make(2)->schema([
                TextInput::make('first_name')->label('Ad')->required(),
                TextInput::make('last_name')->label('Soyad')->required(),
                TextInput::make('phone')->label('Telefon')->tel(),
                TextInput::make('tc_no')->label('TC Kimlik No')->length(11)->unique('customers', 'tc_no')->rule(new TcKimlikNo),
                Select::make('gender')->label('Cinsiyet')->options(Options::GENDERS),
                DatePicker::make('birth_date')->label('Doğum Tarihi')->native(false)->displayFormat('d.m.Y'),
            ]),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('customer')->columnSpanFull()->persistTabInQueryString()->tabs([
                Tab::make('Kimlik Bilgileri')->icon(Heroicon::OutlinedIdentification)->columns(3)->schema([
                    TextInput::make('first_name')->label('Ad')->required()->maxLength(100),
                    TextInput::make('last_name')->label('Soyad')->required()->maxLength(100),
                    TextInput::make('tc_no')->label('TC Kimlik No')->length(11)->unique(ignoreRecord: true)->rule(new TcKimlikNo),
                    Select::make('gender')->label('Cinsiyet')->options(Options::GENDERS),
                    DatePicker::make('birth_date')->label('Doğum Tarihi')->native(false)->displayFormat('d.m.Y')->maxDate(now()),
                    TextInput::make('birth_place')->label('Doğum Yeri'),
                    TextInput::make('father_name')->label('Baba Adı'),
                    TextInput::make('mother_name')->label('Anne Adı'),
                    TextInput::make('nationality')->label('Uyruk')->default('TC'),
                    Select::make('marital_status')->label('Medeni Hal')->options(Options::MARITAL_STATUSES),
                    TextInput::make('occupation')->label('Meslek'),
                    Select::make('blood_type')->label('Kan Grubu')->options(Options::BLOOD_TYPES),
                    FileUpload::make('photo')->label('Vesikalık Fotoğraf')->image()->avatar()->directory('customers/photos')->maxSize(2048),
                ]),
                Tab::make('İletişim')->icon(Heroicon::OutlinedPhone)->columns(3)->schema([
                    TextInput::make('phone')->label('Cep Telefonu')->tel()->placeholder('0532 000 00 00'),
                    TextInput::make('phone_secondary')->label('2. Telefon')->tel(),
                    TextInput::make('email')->label('E-posta')->email(),
                    TextInput::make('city')->label('İl'),
                    TextInput::make('district')->label('İlçe'),
                    Textarea::make('address')->label('Adres')->rows(2)->columnSpanFull(),
                    Section::make('Acil Durumda Ulaşılacak Kişi')->columns(3)->columnSpanFull()->schema([
                        TextInput::make('emergency_contact_name')->label('Ad Soyad'),
                        TextInput::make('emergency_contact_relation')->label('Yakınlığı'),
                        TextInput::make('emergency_contact_phone')->label('Telefon')->tel(),
                    ]),
                ]),
                Tab::make('Pasaport')->icon(Heroicon::OutlinedGlobeAlt)->columns(3)->schema([
                    Select::make('passport_type')->label('Pasaport Türü')->options(Options::PASSPORT_TYPES),
                    TextInput::make('passport_no')->label('Pasaport No'),
                    TextInput::make('passport_issue_place')->label('Veriliş Yeri'),
                    DatePicker::make('passport_issue_date')->label('Veriliş Tarihi')->native(false)->displayFormat('d.m.Y'),
                    DatePicker::make('passport_expiry_date')->label('Geçerlilik Bitiş')->native(false)->displayFormat('d.m.Y')
                        ->helperText('Umre/yurtdışı için dönüşten itibaren en az 6 ay geçerli olmalı.'),
                ]),
                Tab::make('Sağlık & Tercihler')->icon(Heroicon::OutlinedHeart)->columns(3)->schema([
                    Textarea::make('health_notes')->label('Sağlık Durumu / Kullandığı İlaçlar')->rows(3)->columnSpanFull(),
                    Toggle::make('needs_wheelchair')->label('Tekerlekli sandalye ihtiyacı'),
                    Select::make('room_preference')->label('Oda Tercihi')->options(Options::ROOM_TYPES),
                    TextInput::make('meal_preference')->label('Yemek Tercihi / Alerji'),
                ]),
                Tab::make('CRM & Notlar')->icon(Heroicon::OutlinedChatBubbleBottomCenterText)->columns(3)->schema([
                    Select::make('source')->label('Bizi Nereden Buldu')->options(Options::CUSTOMER_SOURCES),
                    TagsInput::make('tags')->label('Etiketler')->placeholder('VIP, Hoca, Grup lideri...')->columnSpan(2),
                    Toggle::make('kvkk_consent')->label('KVKK onayı alındı'),
                    Toggle::make('sms_consent')->label('SMS / WhatsApp ileti izni'),
                    Textarea::make('notes')->label('Notlar')->rows(4)->columnSpanFull(),
                ]),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(4)->columnSpanFull()->schema([
                ImageEntry::make('photo')->hiddenLabel()->disk('public')->circular()->imageHeight(90)
                    ->placeholder('Fotoğraf yok'),
                Grid::make(3)->columnSpan(3)->schema([
                    TextEntry::make('full_name')->label('Ad Soyad')->weight('bold')->size('lg'),
                    TextEntry::make('tc_no')->label('TC Kimlik No')->copyable()->placeholder('-'),
                    TextEntry::make('phone')->label('Telefon')->placeholder('-')
                        ->url(fn (Customer $r) => $r->phone ? 'tel:'.preg_replace('/\s/', '', $r->phone) : null),
                    TextEntry::make('birth_date')->label('Doğum')->placeholder('-')
                        ->formatStateUsing(fn (Customer $r) => $r->birth_date?->format('d.m.Y').' ('.$r->age.' yaş)'),
                    TextEntry::make('gender')->label('Cinsiyet')->formatStateUsing(fn ($state) => Options::GENDERS[$state] ?? '-')->placeholder('-'),
                    TextEntry::make('city')->label('Şehir')->formatStateUsing(fn (Customer $r) => trim($r->district.' / '.$r->city, ' /'))->placeholder('-'),
                ]),
            ]),
            Section::make('Pasaport')->columns(4)->columnSpanFull()->schema([
                TextEntry::make('passport_type')->label('Tür')->formatStateUsing(fn ($state) => Options::PASSPORT_TYPES[$state] ?? $state)->placeholder('-'),
                TextEntry::make('passport_no')->label('No')->copyable()->placeholder('-'),
                TextEntry::make('passport_issue_date')->label('Veriliş')->date('d.m.Y')->placeholder('-'),
                TextEntry::make('passport_expiry_date')->label('Bitiş')->date('d.m.Y')->placeholder('-')->badge()
                    ->color(fn (Customer $r) => $r->passportExpiresSoon() ? 'danger' : 'success'),
            ]),
            Grid::make(2)->columnSpanFull()->schema([
                Section::make('İletişim & Acil Durum')->columns(2)->schema([
                    TextEntry::make('email')->label('E-posta')->placeholder('-'),
                    TextEntry::make('phone_secondary')->label('2. Telefon')->placeholder('-'),
                    TextEntry::make('address')->label('Adres')->placeholder('-')->columnSpanFull(),
                    TextEntry::make('emergency_contact_name')->label('Acil Durum Kişisi')->placeholder('-')
                        ->formatStateUsing(fn (Customer $r) => $r->emergency_contact_name.($r->emergency_contact_relation ? ' ('.$r->emergency_contact_relation.')' : '')),
                    TextEntry::make('emergency_contact_phone')->label('Acil Telefon')->placeholder('-'),
                ]),
                Section::make('Sağlık & Tercihler')->columns(2)->schema([
                    TextEntry::make('health_notes')->label('Sağlık')->placeholder('-')->columnSpanFull(),
                    IconEntry::make('needs_wheelchair')->label('Tekerlekli Sandalye')->boolean(),
                    TextEntry::make('blood_type')->label('Kan Grubu')->placeholder('-'),
                    TextEntry::make('room_preference')->label('Oda Tercihi')->formatStateUsing(fn ($state) => Options::ROOM_TYPES[$state] ?? $state)->placeholder('-'),
                    TextEntry::make('tags')->label('Etiketler')->badge()->placeholder('-'),
                    TextEntry::make('notes')->label('Notlar')->placeholder('-')->columnSpanFull(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['bookings' => fn ($q) => $q->where('status', '!=', 'cancelled')]))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('first_name')->label('Ad Soyad')->weight('bold')
                    ->formatStateUsing(fn (Customer $r) => $r->full_name)
                    ->description(fn (Customer $r) => $r->tc_no)
                    ->searchable(['first_name', 'last_name', 'tc_no', 'passport_no'])->sortable(['last_name', 'first_name']),
                TextColumn::make('phone')->label('Telefon')->searchable()->copyable(),
                TextColumn::make('city')->label('Şehir')->searchable()->toggleable(),
                TextColumn::make('birth_date')->label('Yaş')->state(fn (Customer $r) => $r->age)->toggleable(),
                TextColumn::make('passport_expiry_date')->label('Pasaport Bitiş')->date('d.m.Y')->sortable()->badge()
                    ->color(fn (Customer $r) => $r->passportExpiresSoon() ? 'danger' : 'gray')->placeholder('Yok'),
                TextColumn::make('bookings_count')->label('Tur Sayısı')->badge()->color('info')->sortable(),
                TextColumn::make('source')->label('Kaynak')->formatStateUsing(fn ($state) => Options::CUSTOMER_SOURCES[$state] ?? $state)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tags')->label('Etiket')->badge()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Kayıt')->date('d.m.Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tour')->label('Katıldığı Tur')
                    ->relationship('bookings.tour', 'title')->searchable()->preload(),
                Filter::make('passport_expiring')->label('Pasaportu 6 ay içinde bitecek / yok')
                    ->query(fn (Builder $query) => $query->where(fn ($q) => $q->whereNull('passport_expiry_date')->orWhere('passport_expiry_date', '<', now()->addMonths(6)))),
                Filter::make('no_bookings')->label('Hiç tura katılmamış')->query(fn (Builder $query) => $query->doesntHave('bookings')),
                SelectFilter::make('source')->label('Kaynak')->options(Options::CUSTOMER_SOURCES),
                SelectFilter::make('gender')->label('Cinsiyet')->options(Options::GENDERS),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('whatsapp')->label('')->tooltip('WhatsApp')->icon(Heroicon::OutlinedChatBubbleLeftRight)->color('success')
                    ->visible(fn (Customer $r) => filled($r->phone))
                    ->url(fn (Customer $r) => 'https://wa.me/'.static::waPhone($r->phone))->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make(), RestoreBulkAction::make()]),
            ]);
    }

    public static function waPhone(?string $phone): string
    {
        $p = preg_replace('/\D/', '', (string) $phone);

        return str_starts_with($p, '0') ? '9'.$p : (strlen($p) === 10 ? '90'.$p : $p);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\BookingsRelationManager::class,
            RelationManagers\DocumentsRelationManager::class,
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
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
