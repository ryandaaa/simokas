<?php

declare(strict_types=1);

namespace App\Domains\Payment\Database\factories;

use App\Domains\Identity\Models\User;
use App\Domains\Payment\Enums\PaymentMethod;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payer_id' => User::factory(),
            'purpose' => PaymentPurpose::Inspection,
            'ref_id' => (string) Str::uuid(),
            'amount' => 50000,
            'status' => PaymentStatus::Pending,
            'method' => null,
            'idempotency_key' => (string) Str::uuid(),
            'expires_at' => now()->addMinutes(30),
            'paid_at' => null,
        ];
    }

    public function forPayer(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'payer_id' => $user->id,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Pending,
            'paid_at' => null,
        ]);
    }

    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Success,
            'method' => $attributes['method'] ?? PaymentMethod::Qris,
            'paid_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Failed,
            'paid_at' => null,
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Refunded,
            'method' => $attributes['method'] ?? PaymentMethod::Qris,
            'paid_at' => now()->subHour(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subMinutes(5),
        ]);
    }

    public function inspection(): static
    {
        return $this->state(fn (array $attributes) => [
            'purpose' => PaymentPurpose::Inspection,
            'amount' => 50000,
        ]);
    }

    public function access(): static
    {
        return $this->state(fn (array $attributes) => [
            'purpose' => PaymentPurpose::Access,
            'amount' => 50000,
        ]);
    }

    public function purchase(): static
    {
        return $this->state(fn (array $attributes) => [
            'purpose' => PaymentPurpose::Purchase,
            'amount' => 150000000,
        ]);
    }

    public function qris(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => PaymentMethod::Qris,
        ]);
    }

    public function transfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => PaymentMethod::Transfer,
        ]);
    }

    public function ewallet(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => PaymentMethod::Ewallet,
        ]);
    }
}
