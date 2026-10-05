<?php

declare(strict_types=1);

namespace App\Domains\Setting\Providers;

use App\Domains\Setting\Actions\UpdateSettings;
use App\Domains\Setting\Contracts\SettingsReader;
use App\Domains\Setting\Contracts\UpdatesSettings;
use App\Domains\Setting\Models\AppSetting;
use App\Domains\Setting\Policies\SettingPolicy;
use App\Domains\Setting\Services\DatabaseSettingsReader;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class SettingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsReader::class, DatabaseSettingsReader::class);
        $this->app->bind(UpdatesSettings::class, UpdateSettings::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'setting');
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        Gate::policy(AppSetting::class, SettingPolicy::class);
    }
}
