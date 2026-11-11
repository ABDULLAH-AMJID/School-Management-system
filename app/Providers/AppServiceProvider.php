<?php

namespace App\Providers;

use App\Contracts\Messaging\MessagingProvider;
use App\Contracts\Payments\PaymentGateway;
use App\Services\Messaging\WhatsAppWebProvider;
use App\Services\Payments\OfflinePaymentGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MessagingProvider::class, WhatsAppWebProvider::class);
        $this->app->bind(PaymentGateway::class, OfflinePaymentGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
