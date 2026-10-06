<?php

declare(strict_types=1);

namespace App\Domains\Result\Database\factories;

use App\Domains\Identity\Models\User;
use App\Domains\Payment\Models\Payment;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\ResultAccess;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResultAccess>
 */
final class ResultAccessFactory extends Factory
{
    protected $model = ResultAccess::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'result_id' => InspectionResult::factory(),
            'user_id' => User::factory(),
            'payment_id' => null,
        ];
    }

    /**
     * Create access with a paid payment
     */
    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'payment_id' => Payment::factory()->success(),
            ];
        });
    }
}
