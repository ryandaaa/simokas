<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\Providers;

use App\Domains\Dashboard\Livewire\AdminDashboard;
use App\Domains\Dashboard\Livewire\BuyerSellerDashboard;
use App\Domains\Dashboard\Livewire\DashboardPage;
use App\Domains\Dashboard\Livewire\HistoryPage;
use App\Domains\Dashboard\Livewire\InspectorDashboard;
use App\Domains\Dashboard\ReadModels\InspectorDashboardReader;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class DashboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(InspectorDashboardReader::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'dashboard');
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        $this->registerLivewireComponents();
    }

    private function registerLivewireComponents(): void
    {
        Livewire::component('dashboard.dashboard-page', DashboardPage::class);
        Livewire::component('dashboard.history-page', HistoryPage::class);
        Livewire::component('dashboard.buyer-seller-dashboard', BuyerSellerDashboard::class);
        Livewire::component('dashboard.inspector-dashboard', InspectorDashboard::class);
        Livewire::component('dashboard.admin-dashboard', AdminDashboard::class);
    }
}
