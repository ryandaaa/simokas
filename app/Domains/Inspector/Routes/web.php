<?php

declare(strict_types=1);

use App\Domains\Inspector\Http\Controllers\ViewInspectorDocumentController;
use App\Domains\Inspector\Livewire\InspectorOnboarding;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::middleware(['auth', 'not_blocked'])->group(function () {
        Route::get('/inspector/onboarding', InspectorOnboarding::class)
            ->middleware('role:inspector')
            ->name('inspector.onboarding');

        Route::get('/inspector/documents/{document}/view', ViewInspectorDocumentController::class)
            ->middleware('signed')
            ->name('inspector.documents.view');
    });
});
