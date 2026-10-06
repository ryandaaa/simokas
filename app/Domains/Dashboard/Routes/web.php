<?php

declare(strict_types=1);

use App\Domains\Dashboard\Livewire\DashboardPage;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardPage::class)->name('dashboard');
});
