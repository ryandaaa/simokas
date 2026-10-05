<?php

declare(strict_types=1);

namespace App\Domains\Result\Database\factories;

use App\Domains\Result\Enums\ItemRating;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\ResultItem;
use App\Domains\Result\Models\TemplateItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResultItem>
 */
class ResultItemFactory extends Factory
{
    protected $model = ResultItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'result_id' => InspectionResult::factory(),
            'template_item_id' => TemplateItem::factory(),
            'rating' => ItemRating::Good,
            'note' => null,
        ];
    }

    public function good(): static
    {
        return $this->state(fn () => ['rating' => ItemRating::Good]);
    }

    public function fair(): static
    {
        return $this->state(fn () => ['rating' => ItemRating::Fair]);
    }

    public function poor(): static
    {
        return $this->state(fn () => ['rating' => ItemRating::Poor]);
    }
}
