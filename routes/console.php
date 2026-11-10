<?php

use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('fx:refresh', function (ExchangeRateService $fx) {
    $data = $fx->all(force: true);
    $this->info('Kaynak: '.($data['source'] ?? 'yok').' · USD: '.($data['rates']['USD'] ?? '-').' · EUR: '.($data['rates']['EUR'] ?? '-'));
})->purpose('Döviz kurlarını yeniler (TCMB / yedek kaynak)');

Schedule::command('fx:refresh')->hourly();
