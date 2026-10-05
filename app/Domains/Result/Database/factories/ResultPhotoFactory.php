<?php

declare(strict_types=1);

namespace App\Domains\Result\Database\factories;

use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\ResultItem;
use App\Domains\Result\Models\ResultPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ResultPhoto>
 */
class ResultPhotoFactory extends Factory
{
    protected $model = ResultPhoto::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'result_id' => InspectionResult::factory(),
            'result_item_id' => ResultItem::factory(),
            'storage_path' => 'results/photos/'.Str::uuid().'.jpg',
            'captured_at' => now(),
            'stamped_at' => now(),
            'is_camera_capture' => true,
        ];
    }

    public function camera(): static
    {
        return $this->state(fn () => [
            'is_camera_capture' => true,
        ]);
    }

    public function nonCamera(): static
    {
        return $this->state(fn () => [
            'is_camera_capture' => false,
        ]);
    }
}
