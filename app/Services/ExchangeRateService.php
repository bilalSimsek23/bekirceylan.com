<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Döviz kurları: önce TCMB (today.xml), ulaşılamazsa open.er-api.com.
 * Sonuç 1 saat önbellekte tutulur; başarısızlıkta son değer kullanılır ve
 * 10 dk boyunca tekrar denenmez (sayfa açılışını bekletmemek için).
 * Zamanlanmış görev: `php artisan fx:refresh` (routes/console.php, saatlik).
 */
class ExchangeRateService
{
    private const TCMB_URL = 'https://www.tcmb.gov.tr/kurlar/today.xml';

    private const FALLBACK_URL = 'https://open.er-api.com/v6/latest/TRY';

    /** @return array{rates: array<string,float>, date: ?string, fetched_at: ?string, source: ?string} */
    public function all(bool $force = false): array
    {
        $cached = Cache::get('fx.rates');
        if (! $force && $cached && ($cached['expires'] ?? 0) > time()) {
            return $cached;
        }

        $data = $this->fromTcmb() ?? $this->fromFallback();

        if ($data) {
            $data['expires'] = time() + 3600;
            Cache::forever('fx.rates', $data);

            return $data;
        }

        $cached ??= ['rates' => ['TRY' => 1.0], 'date' => null, 'fetched_at' => null, 'source' => null];
        $cached['expires'] = time() + 600;
        Cache::forever('fx.rates', $cached);

        return $cached;
    }

    public function rate(string $currency): ?float
    {
        return $this->all()['rates'][strtoupper($currency)] ?? null;
    }

    private function fromTcmb(): ?array
    {
        try {
            $response = Http::connectTimeout(3)->timeout(5)->get(self::TCMB_URL);
            if (! $response->successful()) {
                return null;
            }
            $xml = simplexml_load_string($response->body());
            $rates = ['TRY' => 1.0];
            foreach ($xml->Currency as $currency) {
                $code = (string) $currency['CurrencyCode'];
                $unit = (int) $currency->Unit ?: 1;
                $selling = (float) str_replace(',', '.', (string) ($currency->BanknoteSelling ?: $currency->ForexSelling));
                if ($selling > 0) {
                    $rates[$code] = round($selling / $unit, 4);
                }
            }

            return ['rates' => $rates, 'date' => (string) $xml['Tarih'], 'fetched_at' => now()->format('d/m H:i'), 'source' => 'TCMB'];
        } catch (\Throwable $e) {
            Log::warning('TCMB kur alınamadı: '.$e->getMessage());

            return null;
        }
    }

    private function fromFallback(): ?array
    {
        try {
            $response = Http::connectTimeout(3)->timeout(5)->get(self::FALLBACK_URL);
            if (! $response->successful() || $response->json('result') !== 'success') {
                return null;
            }
            $rates = ['TRY' => 1.0];
            foreach (['USD', 'EUR', 'SAR', 'GBP'] as $code) {
                $perTry = (float) $response->json("rates.$code");
                if ($perTry > 0) {
                    $rates[$code] = round(1 / $perTry, 4);
                }
            }

            return ['rates' => $rates, 'date' => now()->format('d.m.Y'), 'fetched_at' => now()->format('d/m H:i'), 'source' => 'ExchangeRate-API'];
        } catch (\Throwable $e) {
            Log::warning('Yedek kur kaynağı alınamadı: '.$e->getMessage());

            return null;
        }
    }
}
