<?php

declare(strict_types=1);

namespace App\Domains\Payment\Providers;

use App\Domains\Payment\Contracts\Billing;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Policies\PaymentPolicy;
use App\Domains\Payment\Services\BillingService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Billing::class, BillingService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'payment');
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        Gate::policy(Payment::class, PaymentPolicy::class);
    }
}
