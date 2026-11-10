# Bekir Ceylan Turizm – Web Sitesi & Yönetim Paneli

Laravel 13 + Filament 5 ile hazırlanmış turizm acentesi uygulaması.

## Bölümler

| Bölüm | Adres | Açıklama |
|---|---|---|
| Web sitesi | `/` | Slider, kategori filtreli tur kartları, tur detay sayfaları, bilgi formu (kaydeder + WhatsApp açar), canlı USD kuru, sitemap |
| Yönetim paneli | `/admin` | Turlar, rezervasyonlar, müşteriler (CRM), bilgi talepleri, muhasebe, site ayarları |

### Yönetim paneli modülleri
- **Tur Yönetimi:** Turlar (gün gün program, oteller, dahil/hariç, oda tipine göre fiyat, galeri, SEO, “yeni dönem olarak kopyala”), kategoriler, rezervasyonlar (ön kayıt / kesin kayıt / iptal, grup–aile kodu, oda tipi, vize durumu).
- **Müşteriler:** Özlük (TC doğrulamalı), iletişim, pasaport, sağlık, acil durum kişisi, KVKK onayı, etiketler, belgeler (gizli diskte saklanır), katıldığı turlar ve ödeme geçmişi. Bilgi taleplerini tek tıkla müşteriye dönüştürme.
- **Muhasebe** *(sadece Yönetici rolü)*: Kasa/banka hesapları ve bakiyeleri, gelir–gider, müşteri tahsilatı ve iadesi (döviz kurlu), virman, tedarikçiler, raporlar (aylık gelir/gider, kategori dağılımı, tur bazlı kâr/zarar, müşteri alacakları).
- **Çıktılar:** Yazdırılabilir yolcu listesi + Excel (CSV), müşteri rezervasyon/ödeme dökümü.
- **Kontrol paneli:** Yeni talepler, yaklaşan turlar ve doluluk, pasaport uyarıları, aylık tahsilat.
- **Roller:** `Yönetici` her şeyi görür; `Personel` muhasebe, ayarlar ve kullanıcıları göremez.

## Kurulum (geliştirme)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed      # örnek veriler APP_ENV=local iken eklenir
php artisan storage:link
php artisan serve
```

Varsayılan yönetici: `admin@bekirceylan.com.tr` / `BekirCeylan2026!` → **ilk girişte Profil'den değiştirin.**
(Seed öncesi `.env` içine `ADMIN_PASSWORD=...` yazarak farklı şifre belirleyebilirsiniz.)

## Canlıya alma

1. `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://bekirceylan.com.tr`
2. MySQL için:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_DATABASE=bekirceylan
   DB_USERNAME=...
   DB_PASSWORD=...
   ```
3. `composer install --no-dev --optimize-autoloader && php artisan migrate --seed --force && php artisan storage:link && php artisan optimize && php artisan filament:optimize`
4. Döviz kuru için cron (saatlik yenileme):
   ```
   * * * * * cd /yol/proje && php artisan schedule:run >> /dev/null 2>&1
   ```
5. Web sunucusunun kök dizini `public/` olmalı.

## Testler

```bash
php artisan test
```

## Notlar
- Döviz kuru önce TCMB'den, ulaşılamazsa yedek kaynaktan alınır; sayfa açılışını bekletmez (`php artisan fx:refresh`).
- Dövizli rezervasyona TL ödeme girildiğinde, “rezervasyona yansıyacak tutar” elle girilebilir; boşsa güncel kurdan hesaplanır.
- Mevcut sitedeki turlar ve görseller seed sırasında aktarılır (`database/seeders/data/tours.json`, `database/seeders/images`).

## İleride eklenebilecekler
- e-Fatura / e-Arşiv entegrasyonu, sanal POS ile online ön ödeme
- Toplu SMS / WhatsApp Business API ile hatırlatma (ödeme, pasaport, tur öncesi bilgilendirme)
- Müşteri portalı (belgelerini yükleme, ödeme durumunu görme)
- Otel oda dağılımı (rooming) ekranı, otobüs koltuk planı
- Personel bazlı satış/komisyon raporu, değişiklik geçmişi (audit log)
