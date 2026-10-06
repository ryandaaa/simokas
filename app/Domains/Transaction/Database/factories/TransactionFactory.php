<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Database\factories;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Models\Listing;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
final class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'listing_id' => Listing::factory(),
            'buyer_id' => User::factory(),
            'status' => 'offered',
            'price_amount' => 100000000,
            'commission_pct' => null,
            'inspector_id' => null,
            'notes' => null,
            'expires_at' => null,
        ];
    }

    public function offered(): self
    {
        return $this->state([
            'status' => 'offered',
            'expires_at' => now()->addHours(48),
        ]);
    }

    public function accepted(): self
    {
        return $this->state([
            'status' => 'accepted',
            'expires_at' => now()->addHours(24),
        ]);
    }

    public function paidHeld(): self
    {
        return $this->state([
            'status' => 'paid_held',
        ]);
    }

    public function withInspectionFee(int $pct = 3, ?string $inspectorId = null): self
    {
        return $this->state([
            'commission_pct' => $pct,
            'inspector_id' => $inspectorId ?? User::factory(),
        ]);
    }

    public function rejected(): self
    {
        return $this->state([
            'status' => 'rejected',
        ]);
    }

    public function cancelled(): self
    {
        return $this->state([
            'status' => 'cancelled',
        ]);
    }
}
