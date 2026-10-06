<?php

declare(strict_types=1);

namespace App\Domains\Admin\Providers;

use App\Domains\Admin\Listeners\RecordInspectorApprovedListener;
use App\Domains\Admin\Listeners\RecordInspectorRejectedListener;
use App\Domains\Admin\Listeners\RecordJobCancelledListener;
use App\Domains\Admin\Listeners\RecordListingApprovedListener;
use App\Domains\Admin\Listeners\RecordListingRejectedListener;
use App\Domains\Admin\Listeners\RecordResultReopenedListener;
use App\Domains\Admin\Livewire\AuditLogIndex;
use App\Domains\Admin\Livewire\InspectionTemplateManager;
use App\Domains\Admin\Livewire\InspectorVerificationQueue;
use App\Domains\Admin\Livewire\JobDetail;
use App\Domains\Admin\Livewire\JobMonitor;
use App\Domains\Admin\Livewire\ListingValidationQueue;
use App\Domains\Admin\Livewire\Settings;
use App\Domains\Admin\Livewire\UnifiedAdminQueue;
use App\Domains\Admin\Livewire\UserManagement;
use App\Domains\Admin\Models\AuditLog;
use App\Domains\Admin\Policies\AuditLogPolicy;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Events\JobCancelled;
use App\Domains\Inspector\Events\InspectorApproved;
use App\Domains\Inspector\Events\InspectorRejected;
use App\Domains\Listing\Events\ListingApproved;
use App\Domains\Listing\Events\ListingRejected;
use App\Domains\Result\Events\ResultReopened;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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

        Gate::policy(
            AuditLog::class,
            AuditLogPolicy::class,
        );

        Gate::define(
            'admin.access',
            fn (User $user): bool => $user->isAdmin() && $user->isActive() && ! $user->isBlocked(),
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
        Livewire::component('admin.audit-log-index', AuditLogIndex::class);
        Livewire::component('admin.unified-admin-queue', UnifiedAdminQueue::class);
        Livewire::component('admin.job-monitor', JobMonitor::class);
        Livewire::component('admin.job-detail', JobDetail::class);
        Livewire::component('admin.user-management', UserManagement::class);
    }

    private function registerEventListeners(): void
    {
        Event::listen(
            ResultReopened::class,
            RecordResultReopenedListener::class,
        );
        Event::listen(
            JobCancelled::class,
            RecordJobCancelledListener::class,
        );
        Event::listen(
            InspectorApproved::class,
            RecordInspectorApprovedListener::class,
        );
        Event::listen(
            InspectorRejected::class,
            RecordInspectorRejectedListener::class,
        );
        Event::listen(
            ListingApproved::class,
            RecordListingApprovedListener::class,
        );
        Event::listen(
            ListingRejected::class,
            RecordListingRejectedListener::class,
        );
    }
}
