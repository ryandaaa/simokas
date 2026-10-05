<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Models;

use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Database\factories\InspectorProfileFactory;
use App\Domains\Inspector\Enums\DocumentType;
use App\Domains\Inspector\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $user_id
 * @property array<int, string> $vehicle_types
 * @property int $experience_years
 * @property VerificationStatus $verification_status
 * @property string|null $rejection_reason
 * @property string|null $verified_by
 * @property Carbon|null $verified_at
 * @property float $rating_avg
 * @property int $rating_count
 * @property int $completed_jobs
 * @property array<int, array{reason: string, recorded_at: string}>|null $rejection_history
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'id',
    'user_id',
    'vehicle_types',
    'experience_years',
    'verification_status',
    'rejection_reason',
    'rejection_history',
    'verified_by',
    'verified_at',
    'rating_avg',
    'rating_count',
    'completed_jobs',
])]
final class InspectorProfile extends Model
{
    /** @use HasFactory<InspectorProfileFactory> */
    use HasFactory, HasUuids;

    protected static function newFactory(): InspectorProfileFactory
    {
        return InspectorProfileFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'vehicle_types' => 'array',
            'experience_years' => 'integer',
            'verification_status' => VerificationStatus::class,
            'verified_at' => 'datetime',
            'rejection_history' => 'array',
            'rating_avg' => 'float',
            'rating_count' => 'integer',
            'completed_jobs' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * @return HasMany<InspectorDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(InspectorDocument::class, 'user_id', 'user_id');
    }

    public function isPending(): bool
    {
        return $this->verification_status === VerificationStatus::Pending;
    }

    public function isApproved(): bool
    {
        return $this->verification_status === VerificationStatus::Approved;
    }

    public function isRejected(): bool
    {
        return $this->verification_status === VerificationStatus::Rejected;
    }

    public function hasCompleteDocuments(): bool
    {
        return count($this->missingDocumentTypes()) === 0;
    }

    /**
     * @return array<int, DocumentType>
     */
    public function missingDocumentTypes(): array
    {
        $existing = $this->documents()
            ->pluck('type')
            ->map(fn ($type): string => $type instanceof DocumentType ? $type->value : (string) $type)
            ->unique()
            ->toArray();

        $required = [
            DocumentType::Ktp,
            DocumentType::Certificate,
            DocumentType::Cv,
            DocumentType::Portfolio,
        ];

        return array_values(array_filter(
            $required,
            fn (DocumentType $type): bool => ! in_array($type->value, $existing, true)
        ));
    }

    public function isReadyForVerification(): bool
    {
        return $this->isPending() && $this->hasCompleteDocuments();
    }

    /**
     * @param  Builder<InspectorProfile>  $query
     * @return Builder<InspectorProfile>
     */
    public function scopeReadyForVerification(Builder $query): Builder
    {
        return $query->where('verification_status', VerificationStatus::Pending->value)
            ->whereHas('documents', fn (Builder $q) => $q->where('type', DocumentType::Ktp->value))
            ->whereHas('documents', fn (Builder $q) => $q->where('type', DocumentType::Certificate->value))
            ->whereHas('documents', fn (Builder $q) => $q->where('type', DocumentType::Cv->value))
            ->whereHas('documents', fn (Builder $q) => $q->where('type', DocumentType::Portfolio->value));
    }

    /**
     * @param  Builder<InspectorProfile>  $query
     * @return Builder<InspectorProfile>
     */
    public function scopeInVerificationQueue(Builder $query): Builder
    {
        return $this->scopeReadyForVerification($query);
    }
}
