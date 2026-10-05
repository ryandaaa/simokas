<?php

declare(strict_types=1);

namespace App\Domains\Payment\Providers;

use Illuminate\Support\ServiceProvider;

final class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'payment');
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');
    }
}
