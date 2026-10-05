<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Providers;

use App\Domains\Catalog\Contracts\InspectionJobAccess;
use App\Domains\Catalog\Services\DenyInspectionJobAccess;
use Illuminate\Support\ServiceProvider;

final class CatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Seam for JOB-2: default denies job-inspector private access until
        // the Inspection domain binds a real implementation (shared PR).
        $this->app->bind(InspectionJobAccess::class, DenyInspectionJobAccess::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'catalog');
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');
    }
}
