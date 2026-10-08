<?php

declare(strict_types=1);

namespace App\Domains\Identity\Providers;

use App\Domains\Identity\Contracts\UserModerationContract;
use App\Domains\Identity\Http\Middleware\EnsureUserHasRole;
use App\Domains\Identity\Http\Middleware\EnsureUserNotBlocked;
use App\Domains\Identity\Listeners\ActivateApprovedInspector;
use App\Domains\Identity\Livewire\Blocked;
use App\Domains\Identity\Livewire\ForgotPassword;
use App\Domains\Identity\Livewire\Login;
use App\Domains\Identity\Livewire\Register;
use App\Domains\Identity\Livewire\ResetPassword;
use App\Domains\Identity\Models\User;
use App\Domains\Identity\Policies\UserPolicy;
use App\Domains\Identity\Services\UserModerationService;
use App\Domains\Inspector\Events\InspectorApproved;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(UserModerationContract::class, UserModerationService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'identity');
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        $this->registerLivewireComponents();
        $this->registerPolicies();
        $this->registerMiddleware();
        $this->registerListeners();
    }

    private function registerListeners(): void
    {
        Event::listen(
            InspectorApproved::class,
            ActivateApprovedInspector::class,
        );
    }

    private function registerLivewireComponents(): void
    {
        Livewire::component('identity.register', Register::class);
        Livewire::component('identity.login', Login::class);
        Livewire::component('identity.forgot-password', ForgotPassword::class);
        Livewire::component('identity.reset-password', ResetPassword::class);
        Livewire::component('identity.blocked', Blocked::class);
    }

    private function registerPolicies(): void
    {
        Gate::policy(User::class, UserPolicy::class);
    }

    private function registerMiddleware(): void
    {
        $this->app['router']->aliasMiddleware('not_blocked', EnsureUserNotBlocked::class);
        $this->app['router']->aliasMiddleware('role', EnsureUserHasRole::class);
    }
}
