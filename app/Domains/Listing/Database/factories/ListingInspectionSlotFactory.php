<?php

declare(strict_types=1);

namespace App\Domains\Listing\Database\factories;

use App\Domains\Listing\Models\Listing;
use App\Domains\Listing\Models\ListingInspectionSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ListingInspectionSlot>
 */
class ListingInspectionSlotFactory extends Factory
{
    protected $model = ListingInspectionSlot::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'listing_id' => Listing::factory(),
            'slot_start' => now()->addDays(2)->setHour(10)->setMinute(0)->setSecond(0),
            'note' => 'Bisa dihubungi 1 jam sebelum tiba',
        ];
    }
}
