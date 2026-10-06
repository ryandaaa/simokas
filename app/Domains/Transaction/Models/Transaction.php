<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Models;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Models\Listing;
use App\Domains\Transaction\Database\factories\TransactionFactory;
use App\Domains\Transaction\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $listing_id
 * @property string $buyer_id
 * @property TransactionStatus $status
 * @property int $price_amount
 * @property int|null $commission_pct
 * @property string|null $inspector_id
 * @property string|null $notes
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory, HasUuids;

    protected $table = 'transactions';

    protected $guarded = [];

    protected static function newFactory(): TransactionFactory
    {
        return TransactionFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TransactionStatus::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    /**
     * Scope: only active transactions (offered, accepted, paid_held).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            TransactionStatus::Offered,
            TransactionStatus::Accepted,
            TransactionStatus::PaidHeld,
        ]);
    }

    /**
     * Scope: one active transaction per listing.
     */
    public function scopeForListing(Builder $query, string $listingId): Builder
    {
        return $query->where('listing_id', $listingId);
    }
}
