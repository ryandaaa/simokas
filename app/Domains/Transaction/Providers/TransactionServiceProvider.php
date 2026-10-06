<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Providers;

use App\Domains\Payment\Events\PaymentSucceeded;
use App\Domains\Transaction\Console\Commands\ExpireAcceptedTransactionsCommand;
use App\Domains\Transaction\Console\Commands\ExpireOfferedTransactionsCommand;
use App\Domains\Transaction\Console\Commands\MarkStalePaidHeldTransactionsCommand;
use App\Domains\Transaction\Listeners\HandlePurchasePaymentSucceeded;
use App\Domains\Transaction\Livewire\OfferPage;
use App\Domains\Transaction\Livewire\TransactionDetailPage;
use App\Domains\Transaction\Livewire\TransactionsIndexPage;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Policies\TransactionPolicy;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class TransactionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'transaction');
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        Gate::policy(Transaction::class, TransactionPolicy::class);

        Event::listen(PaymentSucceeded::class, HandlePurchasePaymentSucceeded::class);

        Livewire::component('transaction.offer-page', OfferPage::class);
        Livewire::component('transaction.transactions-index-page', TransactionsIndexPage::class);
        Livewire::component('transaction.transaction-detail-page', TransactionDetailPage::class);

        $this->commands([
            ExpireOfferedTransactionsCommand::class,
            ExpireAcceptedTransactionsCommand::class,
            MarkStalePaidHeldTransactionsCommand::class,
        ]);

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command(ExpireOfferedTransactionsCommand::class)->everyMinute();
            $schedule->command(ExpireAcceptedTransactionsCommand::class)->everyMinute();
            $schedule->command(MarkStalePaidHeldTransactionsCommand::class)->daily();
        });
    }
}
