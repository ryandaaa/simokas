<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Providers;

use App\Domains\Inspector\Contracts\InspectorEligibility;
use App\Domains\Inspector\Contracts\InspectorVerificationContract;
use App\Domains\Inspector\Http\Middleware\EnsureInspectorIsApproved;
use App\Domains\Inspector\Models\InspectorDocument;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Inspector\Policies\InspectorDocumentPolicy;
use App\Domains\Inspector\Policies\InspectorProfilePolicy;
use App\Domains\Inspector\Services\InspectorEligibilityService;
use App\Domains\Inspector\Services\InspectorVerificationService;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class InspectorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(InspectorEligibility::class, InspectorEligibilityService::class);
        $this->app->singleton(InspectorVerificationContract::class, InspectorVerificationService::class);
    }

    public function boot(Router $router): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'inspector');
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        $router->aliasMiddleware('verified_inspector', EnsureInspectorIsApproved::class);

        Gate::policy(InspectorProfile::class, InspectorProfilePolicy::class);
        Gate::policy(InspectorDocument::class, InspectorDocumentPolicy::class);
    }
}
