<?php

declare(strict_types=1);

namespace App\Domains\Result\Models;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Models\Listing;
use App\Domains\Result\Database\factories\InspectionResultFactory;
use App\Domains\Result\Enums\ResultStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $job_id
 * @property string $listing_id
 * @property string $inspector_id
 * @property string $template_id
 * @property float|null $total_score
 * @property string|null $recommendation
 * @property string|null $summary_note
 * @property ResultStatus $status
 * @property Carbon|null $submitted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read InspectionJob $job
 * @property-read Listing $listing
 * @property-read User $inspector
 * @property-read InspectionTemplate $template
 * @property-read Collection<int, ResultItem> $items
 */
final class InspectionResult extends Model
{
    /** @use HasFactory<InspectionResultFactory> */
    use HasFactory;

    use HasUuids;

    protected static function newFactory(): InspectionResultFactory
    {
        return InspectionResultFactory::new();
    }

    protected $table = 'inspection_results';

    protected $fillable = [
        'job_id',
        'listing_id',
        'inspector_id',
        'template_id',
        'total_score',
        'recommendation',
        'summary_note',
        'status',
        'submitted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ResultStatus::class,
            'total_score' => 'decimal:2',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<InspectionJob, $this>
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(InspectionJob::class, 'job_id');
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
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    /**
     * @return BelongsTo<InspectionTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(InspectionTemplate::class, 'template_id');
    }

    /**
     * @return HasMany<ResultItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ResultItem::class, 'result_id');
    }
}
