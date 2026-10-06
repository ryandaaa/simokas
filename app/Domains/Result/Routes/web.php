<?php

declare(strict_types=1);

use App\Domains\Result\Http\Controllers\DeleteResultPhotoController;
use App\Domains\Result\Http\Controllers\RequestResultAccessController;
use App\Domains\Result\Http\Controllers\UploadResultPhotoController;
use App\Domains\Result\Http\Controllers\ViewResultController;
use App\Domains\Result\Http\Controllers\ViewResultPhotoController;
use App\Domains\Result\Livewire\InspectionFormPage;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    // View inspection result with access control
    Route::get('/results/{result}', ViewResultController::class)
        ->middleware(['auth', 'not_blocked'])
        ->whereUuid('result')
        ->name('results.show');

    // Request paid access to inspection result
    Route::post('/results/{result}/request-access', RequestResultAccessController::class)
        ->middleware(['auth', 'not_blocked'])
        ->whereUuid('result')
        ->name('results.request-access');

    // Foto hasil inspeksi: disajikan dari storage privat lewat URL bertanda tangan sementara
    Route::get('/results/{result}/photos/{photo}', ViewResultPhotoController::class)
        ->middleware(['auth', 'not_blocked', 'signed'])
        ->whereUuid('result')
        ->whereUuid('photo')
        ->name('results.photos.show');

    Route::middleware(['auth', 'not_blocked', 'role:inspector', 'verified_inspector'])->group(function () {
        Route::get('/jobs/{job}/fill', InspectionFormPage::class)
            ->whereUuid('job')
            ->name('result.fill');

        Route::post('/jobs/{job}/result/photos', UploadResultPhotoController::class)
            ->whereUuid('job')
            ->name('result.photos.upload');

        Route::delete('/jobs/{job}/result/photos/{photo}', DeleteResultPhotoController::class)
            ->whereUuid('job')
            ->whereUuid('photo')
            ->name('result.photos.delete');
    });
});
