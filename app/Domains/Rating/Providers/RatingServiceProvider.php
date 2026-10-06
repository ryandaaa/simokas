<?php

declare(strict_types=1);

namespace App\Domains\Rating\Providers;

use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Rating\Contracts\RatingReader;
use App\Domains\Rating\Livewire\AdminRatingList;
use App\Domains\Rating\Livewire\RatingForm;
use App\Domains\Rating\Models\Rating;
use App\Domains\Rating\Policies\RatingPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class RatingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RatingReader::class, function () {
            return new class implements RatingReader
            {
                public function getInspectorRating(string $inspectorUserId): array
                {
                    /** @var InspectorProfile|null $profile */
                    $profile = InspectorProfile::query()->where('user_id', $inspectorUserId)->first();

                    return [
                        'ratingAvg' => $profile !== null ? (float) $profile->rating_avg : 0.00,
                        'ratingCount' => $profile !== null ? (int) $profile->rating_count : 0,
                    ];
                }
            };
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'rating');
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        Gate::policy(Rating::class, RatingPolicy::class);

        Livewire::component('rating.rating-form', RatingForm::class);
        Livewire::component('rating-form', RatingForm::class);
        Livewire::component('rating.admin-rating-list', AdminRatingList::class);
    }
}
