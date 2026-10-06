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

        $this->registerLivewireComponents();
    }

    private function registerLivewireComponents(): void
    {
        Livewire::component('admin.settings', Settings::class);
        Livewire::component('admin.inspector-verification-queue', InspectorVerificationQueue::class);
        Livewire::component('admin.listing-validation-queue', ListingValidationQueue::class);
        Livewire::component('admin.inspection-template-manager', InspectionTemplateManager::class);
    }
}
