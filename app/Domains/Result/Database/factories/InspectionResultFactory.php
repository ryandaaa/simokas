<?php

declare(strict_types=1);

namespace App\Domains\Result\Database\factories;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Models\Listing;
use App\Domains\Result\Enums\ResultStatus;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\InspectionTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InspectionResult>
 */
class InspectionResultFactory extends Factory
{
    protected $model = InspectionResult::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_id' => InspectionJob::factory()->inProgress(),
            'listing_id' => Listing::factory()->active(),
            'inspector_id' => User::factory()->inspector(),
            'template_id' => InspectionTemplate::factory()->firstOrCreate()->id,
            'total_score' => null,
            'recommendation' => null,
            'summary_note' => null,
            'status' => ResultStatus::Draft,
            'submitted_at' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => ResultStatus::Draft,
            'submitted_at' => null,
        ]);
    }

    public function submitted(): static
    {
        return $this->state(fn () => [
            'status' => ResultStatus::Submitted,
            'total_score' => 85.00,
            'recommendation' => 'recommended',
            'submitted_at' => now(),
        ]);
    }
}
