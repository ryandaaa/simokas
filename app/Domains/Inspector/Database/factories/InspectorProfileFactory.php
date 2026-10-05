<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Database\factories;

use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Enums\VehicleType;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Models\InspectorProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InspectorProfile>
 */
class InspectorProfileFactory extends Factory
{
    protected $model = InspectorProfile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->inspector()->pending(),
            'vehicle_types' => [VehicleType::Car->value],
            'experience_years' => fake()->numberBetween(1, 15),
            'verification_status' => VerificationStatus::Pending,
            'rejection_reason' => null,
            'verified_by' => null,
            'verified_at' => null,
            'rating_avg' => 0.00,
            'rating_count' => 0,
            'completed_jobs' => 0,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => VerificationStatus::Pending,
            'rejection_reason' => null,
            'verified_by' => null,
            'verified_at' => null,
        ]);
    }

    public function approved(?User $admin = null): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => VerificationStatus::Approved,
            'rejection_reason' => null,
            'verified_by' => $admin !== null ? $admin->id : User::factory()->admin(),
            'verified_at' => now(),
        ]);
    }

    public function rejected(string $reason = 'Dokumen tidak lengkap atau tidak valid'): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => VerificationStatus::Rejected,
            'rejection_reason' => $reason,
            'verified_by' => User::factory()->admin(),
            'verified_at' => now(),
        ]);
    }

    public function carAndMotorcycle(): static
    {
        return $this->state(fn (array $attributes) => [
            'vehicle_types' => [VehicleType::Car->value, VehicleType::Motorcycle->value],
        ]);
    }
}
