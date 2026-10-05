<?php

declare(strict_types=1);

use App\Domains\Result\Http\Controllers\ViewResultPhotoController;
use App\Domains\Result\Livewire\InspectionFormPage;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    // Foto hasil inspeksi: disajikan dari storage privat lewat URL bertanda tangan sementara
    Route::get('/results/{result}/photos/{photo}', ViewResultPhotoController::class)
        ->middleware(['auth', 'not_blocked', 'signed'])
        ->whereUuid('result')
        ->whereUuid('photo')
        ->name('result.photos.show');

    Route::middleware(['auth', 'not_blocked', 'role:inspector', 'verified_inspector'])->group(function () {
        Route::get('/jobs/{job}/fill', InspectionFormPage::class)
            ->whereUuid('job')
            ->name('result.fill');
    });
});

