<?php

declare(strict_types=1);

namespace App\Domains\Identity\Providers;

use App\Domains\Identity\Http\Middleware\EnsureUserHasRole;
use App\Domains\Identity\Http\Middleware\EnsureUserNotBlocked;
use App\Domains\Identity\Livewire\AdminDashboard;
use App\Domains\Identity\Livewire\Blocked;
use App\Domains\Identity\Livewire\ForgotPassword;
use App\Domains\Identity\Livewire\InspectorDashboard;
use App\Domains\Identity\Livewire\Login;
use App\Domains\Identity\Livewire\Register;
use App\Domains\Identity\Livewire\ResetPassword;
use App\Domains\Identity\Livewire\UserDashboard;
use App\Domains\Identity\Models\User;
use App\Domains\Identity\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'identity');
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        $this->registerLivewireComponents();
        $this->registerPolicies();
        $this->registerMiddleware();
    }

    private function registerLivewireComponents(): void
    {
        Livewire::component('identity.register', Register::class);
        Livewire::component('identity.login', Login::class);
        Livewire::component('identity.forgot-password', ForgotPassword::class);
        Livewire::component('identity.reset-password', ResetPassword::class);
        Livewire::component('identity.user-dashboard', UserDashboard::class);
        Livewire::component('identity.inspector-dashboard', InspectorDashboard::class);
        Livewire::component('identity.admin-dashboard', AdminDashboard::class);
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
