<?php

declare(strict_types=1);

namespace App\Domains\Result\Models;

use App\Domains\Result\Database\factories\ResultItemFactory;
use App\Domains\Result\Enums\ItemRating;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $result_id
 * @property string $template_item_id
 * @property ItemRating|null $rating
 * @property string|null $note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read InspectionResult $result
 * @property-read TemplateItem $templateItem
 */
final class ResultItem extends Model
{
    /** @use HasFactory<ResultItemFactory> */
    use HasFactory;

    use HasUuids;

    protected static function newFactory(): ResultItemFactory
    {
        return ResultItemFactory::new();
    }

    protected $table = 'result_items';

    protected $fillable = [
        'result_id',
        'template_item_id',
        'rating',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => ItemRating::class,
        ];
    }

    /**
     * @return BelongsTo<InspectionResult, $this>
     */
    public function result(): BelongsTo
    {
        return $this->belongsTo(InspectionResult::class, 'result_id');
    }

    /**
     * @return BelongsTo<TemplateItem, $this>
     */
    public function templateItem(): BelongsTo
    {
        return $this->belongsTo(TemplateItem::class, 'template_item_id');
    }
}
