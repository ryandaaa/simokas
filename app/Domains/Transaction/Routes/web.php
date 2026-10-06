<?php

declare(strict_types=1);

use App\Domains\Transaction\Livewire\OfferPage;
use App\Domains\Transaction\Livewire\TransactionDetailPage;
use App\Domains\Transaction\Livewire\TransactionsIndexPage;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'not_blocked'])->group(function () {
    Route::middleware('role:user')->group(function () {
        Route::get('/transactions/offer/{listing}', OfferPage::class)
            ->whereUuid('listing')
            ->name('transactions.offer');
    });

    Route::get('/transactions', TransactionsIndexPage::class)->name('transactions.index');
    Route::get('/transactions/{transaction}', TransactionDetailPage::class)
        ->whereUuid('transaction')
        ->name('transactions.show');
});
