<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\Providers;

use Illuminate\Support\ServiceProvider;

final class DashboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'dashboard');
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');
    }
}
