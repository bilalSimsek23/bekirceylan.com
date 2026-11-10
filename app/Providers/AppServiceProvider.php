<?php

namespace App\Providers;

use App\Models\Setting;
use App\Services\ExchangeRateService;
use Carbon\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ExchangeRateService::class);
    }

    public function boot(): void
    {
        Carbon::setLocale('tr');

        View::composer('site.*', function ($view) {
            $settings = Setting::allCached();
            $fx = $settings['show_exchange_rate'] ? app(ExchangeRateService::class)->all() : null;

            $view->with([
                'settings' => $settings,
                'fx' => $fx,
                'whatsappUrl' => 'https://wa.me/'.$settings['whatsapp'].'?text='.rawurlencode($settings['whatsapp_message']),
            ]);
        });
    }
}
