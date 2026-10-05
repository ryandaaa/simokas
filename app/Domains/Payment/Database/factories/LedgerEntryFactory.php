<?php

declare(strict_types=1);

namespace App\Domains\Payment\Database\factories;

use App\Domains\Identity\Models\User;
use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LedgerEntry>
 */
class LedgerEntryFactory extends Factory
{
    protected $model = LedgerEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'account_user_id' => User::factory()->inspector(),
            'entry_type' => LedgerEntryType::AccessFee,
            'amount' => 13500,
            'note' => null,
        ];
    }

    public function forInspector(User $inspector): static
    {
        return $this->state(fn (array $attributes) => [
            'account_user_id' => $inspector->id,
        ]);
    }

    public function platform(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_user_id' => null,
            'entry_type' => LedgerEntryType::PlatformFee,
        ]);
    }

    public function ofType(LedgerEntryType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'entry_type' => $type,
        ]);
    }
}
