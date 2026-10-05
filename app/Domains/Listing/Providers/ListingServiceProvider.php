<?php

declare(strict_types=1);

namespace App\Domains\Listing\Providers;

use App\Domains\Listing\Livewire\CreateListing;
use App\Domains\Listing\Livewire\EditListing;
use App\Domains\Listing\Livewire\SellerListings;
use App\Domains\Listing\Models\Listing;
use App\Domains\Listing\Policies\ListingPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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

        Gate::policy(Listing::class, ListingPolicy::class);

        Livewire::component('listing.create-listing', CreateListing::class);
        Livewire::component('listing.seller-listings', SellerListings::class);
        Livewire::component('listing.edit-listing', EditListing::class);
    }
}
