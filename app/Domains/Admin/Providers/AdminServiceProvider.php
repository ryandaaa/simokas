<?php

declare(strict_types=1);

namespace App\Domains\Admin\Providers;

use App\Domains\Admin\Livewire\InspectionTemplateManager;
use App\Domains\Admin\Livewire\InspectorVerificationQueue;
use App\Domains\Admin\Livewire\ListingValidationQueue;
use App\Domains\Admin\Livewire\Settings;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'admin');
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        \Illuminate\Support\Facades\Gate::policy(
            \App\Domains\Admin\Models\AuditLog::class,
            \App\Domains\Admin\Policies\AuditLogPolicy::class,
        );

        $this->registerLivewireComponents();
        $this->registerEventListeners();
    }

    private function registerLivewireComponents(): void
    {
        Livewire::component('admin.settings', Settings::class);
        Livewire::component('admin.inspector-verification-queue', InspectorVerificationQueue::class);
        Livewire::component('admin.listing-validation-queue', ListingValidationQueue::class);
        Livewire::component('admin.inspection-template-manager', InspectionTemplateManager::class);
        Livewire::component('admin.audit-log-index', \App\Domains\Admin\Livewire\AuditLogIndex::class);
    }

    private function registerEventListeners(): void
    {
        \Illuminate\Support\Facades\Event::listen(
            \App\Domains\Result\Events\ResultReopened::class,
            \App\Domains\Admin\Listeners\RecordResultReopenedListener::class,
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Domains\Inspection\Events\JobCancelled::class,
            \App\Domains\Admin\Listeners\RecordJobCancelledListener::class,
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Domains\Inspector\Events\InspectorApproved::class,
            \App\Domains\Admin\Listeners\RecordInspectorApprovedListener::class,
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Domains\Inspector\Events\InspectorRejected::class,
            \App\Domains\Admin\Listeners\RecordInspectorRejectedListener::class,
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Domains\Listing\Events\ListingApproved::class,
            \App\Domains\Admin\Listeners\RecordListingApprovedListener::class,
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Domains\Listing\Events\ListingRejected::class,
            \App\Domains\Admin\Listeners\RecordListingRejectedListener::class,
        );
    }
}
