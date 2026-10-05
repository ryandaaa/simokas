<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Providers;

use App\Domains\Inspector\Models\InspectorDocument;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Inspector\Policies\InspectorDocumentPolicy;
use App\Domains\Inspector\Policies\InspectorProfilePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class InspectorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'inspector');
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        Gate::policy(InspectorProfile::class, InspectorProfilePolicy::class);
        Gate::policy(InspectorDocument::class, InspectorDocumentPolicy::class);
    }
}
