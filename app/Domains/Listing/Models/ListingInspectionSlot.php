<?php

declare(strict_types=1);

namespace App\Domains\Listing\Models;

use App\Domains\Listing\Database\factories\ListingInspectionSlotFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $listing_id
 * @property Carbon $slot_start
 * @property string|null $note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Listing $listing
 */
class ListingInspectionSlot extends Model
{
    /** @use HasFactory<ListingInspectionSlotFactory> */
    use HasFactory, HasUuids;

    protected $table = 'listing_inspection_slots';

    protected $guarded = [];

    protected static function newFactory(): ListingInspectionSlotFactory
    {
        return ListingInspectionSlotFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'slot_start' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Listing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class, 'listing_id');
    }
}
