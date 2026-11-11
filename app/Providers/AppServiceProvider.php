<?php

namespace App\Providers;

use App\Contracts\Messaging\MessagingProvider;
use App\Services\Messaging\WhatsAppWebProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MessagingProvider::class, WhatsAppWebProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
