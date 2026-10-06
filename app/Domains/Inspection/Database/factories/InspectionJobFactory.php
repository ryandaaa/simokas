<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Database\factories;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Models\Listing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InspectionJob>
 */
class InspectionJobFactory extends Factory
{
    protected $model = InspectionJob::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'listing_id' => Listing::factory()->active(),
            'requester_id' => User::factory()->user(),
            'inspector_id' => null,
            'status' => JobStatus::Requested,
            'fee_amount' => 50000,
            'platform_fee_pct' => 10,
            'expires_at' => now()->addMinutes(30),
            'available_since' => null,
            'scheduled_at' => null,
            'taken_at' => null,
            'started_at' => null,
            'submitted_at' => null,
            'completed_at' => null,
            'needs_attention_at' => null,
            'cancel_reason' => null,
            'cancelled_by' => null,
        ];
    }

    public function requested(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JobStatus::Requested,
            'available_since' => null,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JobStatus::Paid,
            'available_since' => now(),
        ]);
    }

    public function car(): static
    {
        return $this->state(fn (array $attributes) => [
            'fee_amount' => 50000,
        ]);
    }

    public function motorcycle(): static
    {
        return $this->state(fn (array $attributes) => [
            'fee_amount' => 15000,
        ]);
    }

    public function taken(?User $inspector = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JobStatus::Taken,
            'inspector_id' => $inspector !== null ? $inspector->id : User::factory()->inspector(),
            'available_since' => now()->subHour(),
            'taken_at' => now(),
        ]);
    }

    public function scheduled(?User $inspector = null, ?\DateTimeInterface $scheduledAt = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JobStatus::Scheduled,
            'inspector_id' => $inspector !== null ? $inspector->id : User::factory()->inspector(),
            'available_since' => now()->subHours(2),
            'taken_at' => now()->subHour(),
            'scheduled_at' => $scheduledAt ?? now()->addDay(),
        ]);
    }

    public function inProgress(?User $inspector = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JobStatus::InProgress,
            'inspector_id' => $inspector !== null ? $inspector->id : User::factory()->inspector(),
            'available_since' => now()->subHours(3),
            'taken_at' => now()->subHours(2),
            'scheduled_at' => now()->subHour(),
            'started_at' => now()->subMinutes(10),
        ]);
    }

    public function cancelled(?User $cancelledBy = null, string $reason = 'Dibatalkan'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JobStatus::Cancelled,
            'cancel_reason' => $reason,
            'cancelled_by' => $cancelledBy?->id,
        ]);
    }

    public function forListing(Listing $listing): static
    {
        return $this->state(fn (array $attributes) => [
            'listing_id' => $listing->id,
        ]);
    }
}
