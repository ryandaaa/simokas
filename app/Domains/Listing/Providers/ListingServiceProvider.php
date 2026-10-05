<?php

declare(strict_types=1);

namespace App\Domains\Listing\Providers;

use Illuminate\Support\ServiceProvider;

final class ListingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'listing');
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');
    }
}
