<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public const DEFAULTS = [
        'site_title' => 'Bekir Ceylan Turizm',
        'site_tagline' => 'Yolculuğunuz Güvenle Başlasın',
        'meta_description' => 'Bekir Ceylan Turizm - Hac, Umre, Kültür ve Gezi turları. TÜRSAB Belge No: 19034',
        'phone' => '0532 134 13 41',
        'whatsapp' => '905321341341',
        'whatsapp_message' => 'Merhaba, Bekir Ceylan Turizm turları hakkında bilgi almak istiyorum.',
        'email' => 'bekirceylanturizm@gmail.com',
        'address' => '',
        'map_embed' => '',
        'instagram' => 'https://www.instagram.com/bekirceylanturizm/',
        'facebook' => '',
        'youtube' => '',
        'tursab_no' => '19034',
        'company_title' => 'BEKİR CEYLAN TURİZM',
        'show_exchange_rate' => '1',
        'tours_eyebrow' => 'Yolculuğunuzu Seçin',
        'tours_title' => 'Öne Çıkan Tur Programları',
        'tours_subtitle' => 'İlham veren rotalar ve özel deneyimler.',
        'about_eyebrow' => 'Bizimle Yolculuk',
        'about_title' => 'Bekir Ceylan Turizm',
        'about_text' => 'Manevi yolculuklar ve yeni keşifler için özenle tasarlanmış bir turizm deneyimi.',
        'contact_title' => 'Bir Sonraki Yolculuğunuz Burada Başlasın',
        'contact_text' => 'Tur programları hakkında bilgi almak için formu doldurun. Gönderdiğiniz bilgiler WhatsApp mesajı olarak hazırlanır.',
    ];

    public static function allCached(): array
    {
        return Cache::rememberForever('settings.all', function () {
            try {
                return array_merge(self::DEFAULTS, self::query()->pluck('value', 'key')->all());
            } catch (\Throwable) {
                return self::DEFAULTS;
            }
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::allCached()[$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            self::query()->updateOrCreate(['key' => $key], ['value' => is_array($value) ? json_encode($value) : $value]);
        }
        Cache::forget('settings.all');
    }
}
