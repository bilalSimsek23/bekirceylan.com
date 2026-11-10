<?php

namespace App\Support;

/**
 * Uygulama genelinde kullanılan seçim listeleri (DB'de anahtar, ekranda etiket).
 */
class Options
{
    public const CURRENCIES = ['TRY' => 'TL (₺)', 'USD' => 'Dolar ($)', 'EUR' => 'Euro (€)', 'SAR' => 'Riyal (SAR)'];

    public const CURRENCY_SYMBOLS = ['TRY' => '₺', 'USD' => '$', 'EUR' => '€', 'SAR' => 'SAR'];

    public const TOUR_STATUSES = [
        'draft' => 'Taslak',
        'published' => 'Yayında',
        'completed' => 'Tamamlandı',
        'cancelled' => 'İptal',
    ];

    public const BOOKING_STATUSES = [
        'pending' => 'Ön Kayıt',
        'confirmed' => 'Kesin Kayıt',
        'completed' => 'Tamamlandı',
        'cancelled' => 'İptal',
    ];

    public const BOOKING_STATUS_COLORS = [
        'pending' => 'warning',
        'confirmed' => 'success',
        'completed' => 'info',
        'cancelled' => 'danger',
    ];

    public const ROOM_TYPES = [
        'single' => 'Tek Kişilik',
        'double' => '2 Kişilik',
        'triple' => '3 Kişilik',
        'quad' => '4 Kişilik',
        'quint' => '5 Kişilik',
    ];

    public const VISA_STATUSES = [
        'not_required' => 'Gerekmiyor',
        'pending' => 'Evrak Bekleniyor',
        'applied' => 'Başvuru Yapıldı',
        'approved' => 'Onaylandı',
        'rejected' => 'Reddedildi',
    ];

    public const GENDERS = ['male' => 'Erkek', 'female' => 'Kadın'];

    public const MARITAL_STATUSES = ['single' => 'Bekar', 'married' => 'Evli', 'widowed' => 'Dul', 'divorced' => 'Boşanmış'];

    public const BLOOD_TYPES = ['0+' => '0 Rh+', '0-' => '0 Rh-', 'A+' => 'A Rh+', 'A-' => 'A Rh-', 'B+' => 'B Rh+', 'B-' => 'B Rh-', 'AB+' => 'AB Rh+', 'AB-' => 'AB Rh-'];

    public const PASSPORT_TYPES = ['umuma' => 'Umuma (Bordo)', 'hususi' => 'Hususi (Yeşil)', 'hizmet' => 'Hizmet (Gri)', 'diplomatik' => 'Diplomatik (Siyah)'];

    public const CUSTOMER_SOURCES = [
        'website' => 'Web Sitesi',
        'whatsapp' => 'WhatsApp',
        'instagram' => 'Instagram',
        'reference' => 'Tavsiye / Referans',
        'office' => 'Ofise Geldi',
        'phone' => 'Telefon',
        'repeat' => 'Eski Müşteri',
        'other' => 'Diğer',
    ];

    public const DOCUMENT_TYPES = [
        'passport' => 'Pasaport',
        'id_card' => 'Kimlik',
        'photo' => 'Vesikalık Fotoğraf',
        'vaccine' => 'Aşı Kartı',
        'visa' => 'Vize',
        'contract' => 'Sözleşme',
        'invoice' => 'Fatura',
        'other' => 'Diğer',
    ];

    public const INQUIRY_STATUSES = [
        'new' => 'Yeni',
        'contacted' => 'Görüşüldü',
        'converted' => 'Müşteriye Dönüştü',
        'closed' => 'Kapandı',
    ];

    public const INQUIRY_STATUS_COLORS = ['new' => 'danger', 'contacted' => 'warning', 'converted' => 'success', 'closed' => 'gray'];

    public const ACCOUNT_TYPES = ['cash' => 'Kasa', 'bank' => 'Banka', 'pos' => 'POS / Kredi Kartı', 'other' => 'Diğer'];

    public const PAYMENT_METHODS = ['cash' => 'Nakit', 'transfer' => 'Havale / EFT', 'credit_card' => 'Kredi Kartı', 'other' => 'Diğer'];

    public const TRANSACTION_TYPES = ['income' => 'Gelir', 'expense' => 'Gider'];

    public const INCOME_CATEGORIES = [
        'tour_payment' => 'Tur Tahsilatı',
        'extra_service' => 'Ek Hizmet',
        'commission' => 'Komisyon Geliri',
        'transfer_in' => 'Virman (Giriş)',
        'other_income' => 'Diğer Gelir',
    ];

    public const EXPENSE_CATEGORIES = [
        'hotel' => 'Otel / Konaklama',
        'flight' => 'Uçak Bileti',
        'bus' => 'Otobüs / Transfer',
        'visa' => 'Vize',
        'guide' => 'Rehber / Görevli',
        'meal' => 'Yemek',
        'insurance' => 'Seyahat Sigortası',
        'gift' => 'Hediye / Promosyon',
        'refund' => 'Müşteri İadesi',
        'rent' => 'Kira',
        'salary' => 'Maaş / SGK',
        'tax' => 'Vergi / Harç',
        'advertising' => 'Reklam',
        'office' => 'Ofis Giderleri',
        'transfer_out' => 'Virman (Çıkış)',
        'other_expense' => 'Diğer Gider',
    ];

    public const SUPPLIER_TYPES = [
        'hotel' => 'Otel',
        'airline' => 'Havayolu',
        'bus' => 'Otobüs / Transfer',
        'guide' => 'Rehber',
        'visa' => 'Vize Acentesi',
        'agency' => 'Acente',
        'insurance' => 'Sigorta',
        'other' => 'Diğer',
    ];

    public static function categories(): array
    {
        return self::INCOME_CATEGORIES + self::EXPENSE_CATEGORIES;
    }

    public static function money(float|int|string|null $amount, ?string $currency = 'TRY', int $decimals = 2): string
    {
        $symbol = self::CURRENCY_SYMBOLS[$currency ?? 'TRY'] ?? $currency;

        return number_format((float) $amount, $decimals, ',', '.').' '.$symbol;
    }
}
