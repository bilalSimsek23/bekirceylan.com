<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\AdminOnly;
use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SiteSettings extends Page
{
    use AdminOnly;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Site Yönetimi';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Site Ayarları';

    protected static ?string $title = 'Site Ayarları';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $values = Setting::allCached();
        $values['show_exchange_rate'] = (bool) $values['show_exchange_rate'];
        $this->form->fill($values);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Tabs::make('settings')->tabs([
                Tab::make('Genel & İletişim')->columns(2)->schema([
                    TextInput::make('site_title')->label('Site Adı')->required(),
                    TextInput::make('site_tagline')->label('Slogan'),
                    TextInput::make('phone')->label('Telefon (görünen)'),
                    TextInput::make('whatsapp')->label('WhatsApp Numarası')->helperText('Ülke koduyla, boşluksuz: 905321341341')->regex('/^\d{10,15}$/'),
                    TextInput::make('whatsapp_message')->label('WhatsApp Hazır Mesaj')->columnSpanFull(),
                    TextInput::make('email')->label('E-posta')->email(),
                    TextInput::make('address')->label('Adres'),
                    Textarea::make('map_embed')->label('Google Harita Embed Kodu (iframe)')->rows(2)->columnSpanFull(),
                    Toggle::make('show_exchange_rate')->label('Üst menüde USD kurunu göster'),
                ]),
                Tab::make('Kurumsal & Sosyal')->columns(2)->schema([
                    TextInput::make('company_title')->label('Firma Ünvanı'),
                    TextInput::make('tursab_no')->label('TÜRSAB Belge No'),
                    TextInput::make('instagram')->label('Instagram URL')->url(),
                    TextInput::make('facebook')->label('Facebook URL')->url(),
                    TextInput::make('youtube')->label('YouTube URL')->url(),
                ]),
                Tab::make('Ana Sayfa Metinleri')->columns(2)->schema([
                    TextInput::make('tours_eyebrow')->label('Turlar – Üst Etiket'),
                    TextInput::make('tours_title')->label('Turlar – Başlık'),
                    TextInput::make('tours_subtitle')->label('Turlar – Alt Metin')->columnSpanFull(),
                    TextInput::make('about_eyebrow')->label('Hakkımızda – Üst Etiket'),
                    TextInput::make('about_title')->label('Hakkımızda – Başlık'),
                    Textarea::make('about_text')->label('Hakkımızda – Metin')->columnSpanFull(),
                    TextInput::make('contact_title')->label('İletişim – Başlık')->columnSpanFull(),
                    Textarea::make('contact_text')->label('İletişim – Metin')->columnSpanFull(),
                ]),
                Tab::make('SEO')->schema([
                    Textarea::make('meta_description')->label('Meta Açıklama')->maxLength(160),
                ]),
            ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Kaydet')->submit('save')->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $data['show_exchange_rate'] = ! empty($data['show_exchange_rate']) ? '1' : '0';
        Setting::setMany(array_intersect_key($data, Setting::DEFAULTS));

        Notification::make()->title('Ayarlar kaydedildi')->success()->send();
    }
}
