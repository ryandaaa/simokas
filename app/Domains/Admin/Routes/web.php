<?php

declare(strict_types=1);

use App\Domains\Admin\Livewire\AuditLogIndex;
use App\Domains\Admin\Livewire\InspectionTemplateManager;
use App\Domains\Admin\Livewire\InspectorVerificationQueue;
use App\Domains\Admin\Livewire\JobDetail;
use App\Domains\Admin\Livewire\JobMonitor;
use App\Domains\Admin\Livewire\ListingValidationQueue;
use App\Domains\Admin\Livewire\Settings;
use App\Domains\Admin\Livewire\UnifiedAdminQueue;
use App\Domains\Admin\Livewire\UserManagement;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'not_blocked', 'role:admin'])->group(function () {
    Route::get('/admin', fn () => redirect()->route('admin.queue.index'))->name('admin.index');
    Route::get('/admin/queue', UnifiedAdminQueue::class)->name('admin.queue.index');
    Route::get('/admin/users', UserManagement::class)->name('admin.users.index');
    Route::get('/admin/jobs', JobMonitor::class)->name('admin.jobs.index');
    Route::get('/admin/jobs/{job}', JobDetail::class)->name('admin.jobs.show');
    Route::get('/admin/settings', Settings::class)->name('admin.settings');
    Route::get('/admin/inspectors', InspectorVerificationQueue::class)->name('admin.inspectors.index');
    Route::get('/admin/listings', ListingValidationQueue::class)->name('admin.listings.index');
    Route::get('/admin/templates', InspectionTemplateManager::class)->name('admin.templates.index');
    Route::get('/admin/audit-logs', AuditLogIndex::class)->name('admin.audit-logs.index');
});
