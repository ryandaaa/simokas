<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Providers;

use App\Domains\Catalog\Contracts\InspectionJobAccess;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Listeners\MarkJobPaidOnPaymentSucceeded;
use App\Domains\Inspection\Livewire\InspectorJobsPage;
use App\Domains\Inspection\Livewire\JobDetailPage;
use App\Domains\Inspection\Livewire\MyInspections;
use App\Domains\Inspection\Livewire\OpenJobsPage;
use App\Domains\Inspection\Livewire\RequestInspectionPage;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspection\Policies\InspectionJobPolicy;
use App\Domains\Inspection\Services\InspectionJobAccessService;
use App\Domains\Inspection\Services\InspectionListingActivityChecker;
use App\Domains\Listing\Contracts\ListingActivityChecker;
use App\Domains\Listing\Models\Listing;
use App\Domains\Payment\Events\PaymentSucceeded;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class InspectionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ListingActivityChecker::class, InspectionListingActivityChecker::class);
        $this->app->singleton(InspectionJobAccess::class, InspectionJobAccessService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'inspection');
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        // Rebind di boot agar binding Inspection MENANG atas binding bawaan
        // Listing terlepas dari urutan provider: boot() semua provider
        // berjalan setelah register() semuanya selesai, sehingga binding
        // terakhir ini selalu yang dipakai container.
        $this->app->singleton(ListingActivityChecker::class, InspectionListingActivityChecker::class);
        $this->app->singleton(InspectionJobAccess::class, InspectionJobAccessService::class);

        Gate::policy(InspectionJob::class, InspectionJobPolicy::class);
        Gate::define('inspection.request', fn (User $user, Listing $listing): bool => (new InspectionJobPolicy)->request($user, $listing));

        Event::listen(PaymentSucceeded::class, MarkJobPaidOnPaymentSucceeded::class);

        Livewire::component('inspection.request-inspection-page', RequestInspectionPage::class);
        Livewire::component('inspection.my-inspections', MyInspections::class);
        Livewire::component('inspection.open-jobs-page', OpenJobsPage::class);
        Livewire::component('inspection.inspector-jobs-page', InspectorJobsPage::class);
        Livewire::component('inspection.job-detail-page', JobDetailPage::class);
    }
}
