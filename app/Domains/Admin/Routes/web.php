<?php

declare(strict_types=1);

use App\Domains\Admin\Livewire\InspectionTemplateManager;
use App\Domains\Admin\Livewire\InspectorVerificationQueue;
use App\Domains\Admin\Livewire\ListingValidationQueue;
use App\Domains\Admin\Livewire\Settings;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'not_blocked', 'role:admin'])->group(function () {
    Route::get('/admin/settings', Settings::class)->name('admin.settings');
    Route::get('/admin/inspectors', InspectorVerificationQueue::class)->name('admin.inspectors.index');
    Route::get('/admin/listings', ListingValidationQueue::class)->name('admin.listings.index');
    Route::get('/admin/templates', InspectionTemplateManager::class)->name('admin.templates.index');
});
