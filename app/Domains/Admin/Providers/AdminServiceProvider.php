<?php

declare(strict_types=1);

namespace App\Domains\Admin\Providers;

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
    }
}
