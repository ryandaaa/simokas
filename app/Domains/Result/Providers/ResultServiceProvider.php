<?php

declare(strict_types=1);

namespace App\Domains\Result\Providers;

use App\Domains\Result\Livewire\InspectionFormPage;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Policies\InspectionResultPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class ResultServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'result');
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        Gate::policy(InspectionResult::class, InspectionResultPolicy::class);
        Gate::define('fill-inspection', [InspectionResultPolicy::class, 'fill']);

        Livewire::component('result.inspection-form-page', InspectionFormPage::class);
    }
}
