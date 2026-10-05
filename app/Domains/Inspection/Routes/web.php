<?php

declare(strict_types=1);

use App\Domains\Inspection\Http\Controllers\TakeJobController;
use App\Domains\Inspection\Livewire\InspectorJobsPage;
use App\Domains\Inspection\Livewire\JobDetailPage;
use App\Domains\Inspection\Livewire\MyInspections;
use App\Domains\Inspection\Livewire\OpenJobsPage;
use App\Domains\Inspection\Livewire\RequestInspectionPage;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::middleware(['auth', 'not_blocked', 'role:user'])->group(function () {
        Route::get('/jobs/mine', MyInspections::class)->name('jobs.mine');
        Route::get('/jobs/request/{listing}', RequestInspectionPage::class)
            ->whereUuid('listing')
            ->name('jobs.request');
    });

    Route::middleware(['auth', 'not_blocked', 'role:inspector', 'verified_inspector'])->group(function () {
        Route::get('/jobs', OpenJobsPage::class)->name('jobs.index');
        Route::get('/jobs/open', OpenJobsPage::class)->name('jobs.open');
        Route::post('/jobs/{job}/take', TakeJobController::class)
            ->whereUuid('job')
            ->name('jobs.take');
        Route::get('/inspector/jobs', InspectorJobsPage::class)->name('jobs.inspector');
    });

    Route::middleware(['auth', 'not_blocked'])->group(function () {
        Route::get('/jobs/{job}', JobDetailPage::class)
            ->whereUuid('job')
            ->name('jobs.show');
    });
});

