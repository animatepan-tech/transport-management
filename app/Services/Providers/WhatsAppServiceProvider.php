<?php

namespace App\Providers;

use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Support\ServiceProvider;

class WhatsAppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            WhatsAppManager::class,
            function () {
                return new WhatsAppManager();
            }
        );

        $this->app->singleton(
            'whatsapp',
            function ($app) {
                return $app->make(
                    WhatsAppManager::class
                );
            }
        );
    }

    public function boot(): void
    {
        //
    }
}