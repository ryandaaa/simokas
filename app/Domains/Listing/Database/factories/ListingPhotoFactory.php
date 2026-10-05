<?php

declare(strict_types=1);

namespace App\Domains\Listing\Database\factories;

use App\Domains\Listing\Models\Listing;
use App\Domains\Listing\Models\ListingPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ListingPhoto>
 */
class ListingPhotoFactory extends Factory
{
    protected $model = ListingPhoto::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'listing_id' => Listing::factory(),
            'storage_path' => 'listings/'.Str::uuid()->toString().'/'.Str::uuid()->toString().'.webp',
            'sort_order' => 0,
            'is_cover' => false,
        ];
    }

    public function cover(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_cover' => true,
            'sort_order' => 0,
        ]);
    }

    public function sortOrder(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'sort_order' => $order,
        ]);
    }
}
