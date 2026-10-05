<?php

declare(strict_types=1);

namespace App\Domains\Result\Models;

use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Result\Database\factories\InspectionTemplateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $vehicle_type
 * @property string $name
 * @property int $version
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, TemplateItem> $items
 * @property-read Collection<int, InspectionResult> $results
 */
final class InspectionTemplate extends Model
{
    /** @use HasFactory<InspectionTemplateFactory> */
    use HasFactory;

    use HasUuids;

    protected static function newFactory(): InspectionTemplateFactory
    {
        return InspectionTemplateFactory::new();
    }

    protected $table = 'inspection_templates';

    protected $fillable = [
        'vehicle_type',
        'name',
        'version',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<TemplateItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(TemplateItem::class, 'template_id')->orderBy('sort_order');
    }

    /**
     * @return HasMany<InspectionResult, $this>
     */
    public function results(): HasMany
    {
        return $this->hasMany(InspectionResult::class, 'template_id');
    }

    /**
     * @param  Builder<InspectionTemplate>  $query
     * @return Builder<InspectionTemplate>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<InspectionTemplate>  $query
     * @return Builder<InspectionTemplate>
     */
    public function scopeForVehicleType(Builder $query, VehicleType|string $type): Builder
    {
        $value = $type instanceof VehicleType ? $type->value : $type;

        return $query->where('vehicle_type', $value);
    }
}
