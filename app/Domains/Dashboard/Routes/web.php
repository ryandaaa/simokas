<?php

declare(strict_types=1);

use App\Domains\Dashboard\Livewire\DashboardPage;
use App\Domains\Dashboard\Livewire\HistoryPage;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::middleware(['auth', 'not_blocked'])->group(function () {
        Route::get('/dashboard', DashboardPage::class)->name('dashboard');
        Route::get('/history', HistoryPage::class)->name('history');
        Route::get('/dashboard/history', HistoryPage::class)->name('dashboard.history');
    });
});
