<?php

namespace App\Filament\Resources\Inquiries;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Inquiry;
use App\Support\Options;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
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
use UnitEnum;

class InquiryResource extends Resource
{
    protected static ?string $model = Inquiry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|UnitEnum|null $navigationGroup = 'Müşteriler';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Bilgi Talebi';

    protected static ?string $pluralModelLabel = 'Bilgi Talepleri';

    public static function getNavigationBadge(): ?string
    {
        $count = Inquiry::query()->where('status', 'new')->count();

        return $count ?: null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Ad Soyad')->required(),
            TextInput::make('phone')->label('Telefon')->required(),
            TextInput::make('email')->label('E-posta')->email(),
            Select::make('tour_id')->label('İlgilendiği Tur')->relationship('tour', 'title')->searchable()->preload(),
            Select::make('status')->label('Durum')->options(Options::INQUIRY_STATUSES)->default('new')->required(),
            Select::make('assigned_to')->label('İlgilenen Personel')->relationship('assignee', 'name'),
            Select::make('source')->label('Kaynak')->options(Options::CUSTOMER_SOURCES)->default('phone'),
            Textarea::make('message')->label('Mesaj')->columnSpanFull(),
            Textarea::make('notes')->label('Görüşme Notları')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('name')->label('Ad Soyad')->searchable()->weight('bold'),
                TextColumn::make('phone')->label('Telefon')->searchable()->copyable(),
                TextColumn::make('tour.title')->label('Tur')->placeholder(fn (Inquiry $r) => $r->interest ?: '-'),
                TextColumn::make('message')->label('Mesaj')->limit(60)->tooltip(fn (Inquiry $r) => $r->message)->wrap(),
                TextColumn::make('status')->label('Durum')->badge()
                    ->formatStateUsing(fn ($state) => Options::INQUIRY_STATUSES[$state] ?? $state)
                    ->color(fn ($state) => Options::INQUIRY_STATUS_COLORS[$state] ?? 'gray'),
                TextColumn::make('assignee.name')->label('Personel')->toggleable(),
                TextColumn::make('source')->label('Kaynak')->formatStateUsing(fn ($state) => Options::CUSTOMER_SOURCES[$state] ?? $state)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Durum')->options(Options::INQUIRY_STATUSES),
                SelectFilter::make('tour_id')->label('Tur')->relationship('tour', 'title'),
            ])
            ->recordActions([
                Action::make('whatsapp')->label('')->tooltip('WhatsApp ile yaz')->icon(Heroicon::OutlinedChatBubbleLeftRight)->color('success')
                    ->url(fn (Inquiry $r) => 'https://wa.me/'.CustomerResource::waPhone($r->phone).'?text='.rawurlencode("Merhaba {$r->name}, Bekir Ceylan Turizm'den yazıyoruz. Bilgi talebiniz için teşekkür ederiz."))
                    ->openUrlInNewTab(),
                Action::make('contacted')->label('Görüşüldü')->icon(Heroicon::OutlinedPhone)->color('warning')
                    ->visible(fn (Inquiry $r) => $r->status === 'new')
                    ->action(fn (Inquiry $r) => $r->update(['status' => 'contacted', 'assigned_to' => auth()->id()])),
                Action::make('convert')->label('Müşteri Yap')->icon(Heroicon::OutlinedUserPlus)->color('success')
                    ->visible(fn (Inquiry $r) => ! $r->customer_id)
                    ->url(fn (Inquiry $r) => CustomerResource::getUrl('create', ['inquiry' => $r->id])),
                Action::make('customer')->label('Müşteri Kartı')->icon(Heroicon::OutlinedUser)
                    ->visible(fn (Inquiry $r) => (bool) $r->customer_id)
                    ->url(fn (Inquiry $r) => CustomerResource::getUrl('view', ['record' => $r->customer_id])),
                EditAction::make(),
                DeleteAction::make()->iconButton(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageInquiries::route('/')];
    }
}
