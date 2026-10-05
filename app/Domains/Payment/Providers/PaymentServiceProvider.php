<?php

declare(strict_types=1);

namespace App\Domains\Payment\Providers;

use App\Domains\Payment\Contracts\Billing;
use App\Domains\Payment\Contracts\Ledger;
use App\Domains\Payment\Events\PaymentSucceeded;
use App\Domains\Payment\Listeners\PostAccessLedger;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Policies\LedgerEntryPolicy;
use App\Domains\Payment\Policies\PaymentPolicy;
use App\Domains\Payment\Services\BillingService;
use App\Domains\Payment\Services\LedgerService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Billing::class, BillingService::class);
        $this->app->singleton(Ledger::class, LedgerService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'payment');
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(LedgerEntry::class, LedgerEntryPolicy::class);

        Event::listen(PaymentSucceeded::class, PostAccessLedger::class);
    }
}
