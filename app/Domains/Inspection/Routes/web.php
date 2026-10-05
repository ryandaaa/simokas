<?php

declare(strict_types=1);

use App\Domains\Inspection\Livewire\MyInspections;
use App\Domains\Inspection\Livewire\RequestInspectionPage;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::middleware(['auth', 'not_blocked', 'role:user'])->group(function () {
        Route::get('/jobs/mine', MyInspections::class)->name('jobs.mine');
        Route::get('/jobs/request/{listing}', RequestInspectionPage::class)
            ->whereUuid('listing')
            ->name('jobs.request');
    });
});
