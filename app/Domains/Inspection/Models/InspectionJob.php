<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Models;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Database\factories\InspectionJobFactory;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Listing\Models\Listing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $listing_id
 * @property string $requester_id
 * @property string|null $inspector_id
 * @property JobStatus $status
 * @property int $fee_amount
 * @property int $platform_fee_pct
 * @property Carbon|null $expires_at
 * @property Carbon|null $available_since
 * @property Carbon|null $scheduled_at
 * @property Carbon|null $taken_at
 * @property Carbon|null $started_at
 * @property Carbon|null $submitted_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $needs_attention_at
 * @property string|null $cancel_reason
 * @property string|null $cancelled_by
 * @property string|null $active_listing_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class InspectionJob extends Model
{
    /** @use HasFactory<InspectionJobFactory> */
    use HasFactory, HasUuids;

    protected $table = 'inspection_jobs';

    protected $guarded = [];

    protected static function newFactory(): InspectionJobFactory
    {
        return InspectionJobFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => JobStatus::class,
            'fee_amount' => 'integer',
            'platform_fee_pct' => 'integer',
            'expires_at' => 'datetime',
            'available_since' => 'datetime',
            'scheduled_at' => 'datetime',
            'taken_at' => 'datetime',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
            'needs_attention_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Listing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class, 'listing_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['completed', 'cancelled', 'expired']);
    }

    /**
     * Include jobs awaiting result confirmation and overdue jobs requiring intervention.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeRequiresAdminAction(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->where('status', JobStatus::ResultSubmitted->value)
                ->orWhere(function (Builder $query): void {
                    $query->whereNotNull('needs_attention_at')
                        ->whereNotIn('status', [
                            JobStatus::Completed->value,
                            JobStatus::Cancelled->value,
                            JobStatus::Expired->value,
                        ]);
                });
        });
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }
}
