<?php

declare(strict_types=1);

namespace App\Domains\Rating\Models;

use App\Domains\Identity\Models\User;
use App\Domains\Rating\Database\factories\RatingFactory;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $transaction_id
 * @property string $inspector_id
 * @property string $buyer_id
 * @property int $score
 * @property string|null $comment
 * @property bool $is_hidden
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Transaction $transaction
 * @property-read User $inspector
 * @property-read User $buyer
 */
final class Rating extends Model
{
    /** @use HasFactory<RatingFactory> */
    use HasFactory, HasUuids;

    protected $table = 'ratings';

    protected $guarded = [];

    protected static function newFactory(): RatingFactory
    {
        return RatingFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'is_hidden' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * Scope: only visible (non-hidden) ratings.
     *
     * @param  Builder<Rating>  $query
     * @return Builder<Rating>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_hidden', false);
    }

    /**
     * Scope: ratings for a given inspector.
     *
     * @param  Builder<Rating>  $query
     * @return Builder<Rating>
     */
    public function scopeForInspector(Builder $query, string $inspectorId): Builder
    {
        return $query->where('inspector_id', $inspectorId);
    }
}
