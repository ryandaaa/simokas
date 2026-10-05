<?php

declare(strict_types=1);

namespace App\Domains\Notification\Providers;

use Illuminate\Support\ServiceProvider;

final class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'notification');
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');
    }
}
