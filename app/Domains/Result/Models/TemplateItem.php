<?php

declare(strict_types=1);

namespace App\Domains\Result\Models;

use App\Domains\Result\Database\factories\TemplateItemFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $template_id
 * @property string $category
 * @property string $label
 * @property int $weight
 * @property bool $is_required
 * @property bool $requires_photo
 * @property bool $is_critical
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read InspectionTemplate $template
 * @property-read Collection<int, ResultItem> $resultItems
 */
final class TemplateItem extends Model
{
    /** @use HasFactory<TemplateItemFactory> */
    use HasFactory;

    use HasUuids;

    protected static function newFactory(): TemplateItemFactory
    {
        return TemplateItemFactory::new();
    }

    protected $table = 'template_items';

    protected $fillable = [
        'template_id',
        'category',
        'label',
        'weight',
        'is_required',
        'requires_photo',
        'is_critical',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weight' => 'integer',
            'is_required' => 'boolean',
            'requires_photo' => 'boolean',
            'is_critical' => 'boolean',
            'sort_order' => 'integer',
        ];
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
    public function resultItems(): HasMany
    {
        return $this->hasMany(ResultItem::class, 'template_item_id');
    }
}
