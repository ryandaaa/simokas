<?php

declare(strict_types=1);

namespace App\Domains\Rating\Database\factories;

use App\Domains\Identity\Models\User;
use App\Domains\Rating\Models\Rating;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rating>
 */
final class RatingFactory extends Factory
{
    protected $model = Rating::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_id' => Transaction::factory(),
            'inspector_id' => User::factory()->inspector(),
            'buyer_id' => User::factory(),
            'score' => 5,
            'comment' => 'Inspeksi sangat teliti dan penjelasannya mudah dipahami.',
            'is_hidden' => false,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn () => [
            'is_hidden' => true,
        ]);
    }
}
