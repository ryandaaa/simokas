<?php

declare(strict_types=1);

namespace App\Domains\Listing\Providers;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Contracts\ListingActivityChecker;
use App\Domains\Listing\Contracts\ListingModerationContract;
use App\Domains\Listing\Livewire\CreateListing;
use App\Domains\Listing\Livewire\EditListing;
use App\Domains\Listing\Livewire\SellerListings;
use App\Domains\Listing\Models\Listing;
use App\Domains\Listing\Policies\ListingPolicy;
use App\Domains\Listing\Services\DefaultListingActivityChecker;
use App\Domains\Listing\Services\ListingModerationService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class ListingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ListingModerationContract::class, ListingModerationService::class);
        $this->app->singleton(ListingActivityChecker::class, DefaultListingActivityChecker::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'listing');
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        Gate::policy(Listing::class, ListingPolicy::class);

        Gate::define('listing.moderate', fn (User $user): bool => ! $user->isBlocked() && $user->isActive() && $user->isAdmin());

        Livewire::component('listing.create-listing', CreateListing::class);
        Livewire::component('listing.seller-listings', SellerListings::class);
        Livewire::component('listing.edit-listing', EditListing::class);
    }
}
