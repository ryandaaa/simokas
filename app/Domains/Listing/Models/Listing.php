<?php

declare(strict_types=1);

namespace App\Domains\Listing\Models;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Database\factories\ListingFactory;
use App\Domains\Listing\Enums\FuelType;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Enums\Transmission;
use App\Domains\Listing\Enums\VehicleType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $seller_id
 * @property VehicleType $vehicle_type
 * @property string $title
 * @property string $brand
 * @property string $model
 * @property int $year
 * @property int $price
 * @property bool $negotiable
 * @property int $odometer_km
 * @property Transmission $transmission
 * @property FuelType $fuel
 * @property string $color
 * @property string|null $chassis_number
 * @property string|null $description
 * @property string|null $area_name
 * @property string|null $lat
 * @property string|null $lng
 * @property string|null $address_full
 * @property string $inspection_place
 * @property ListingStatus $status
 * @property string|null $rejection_reason
 * @property bool $is_inspected
 * @property Carbon|null $last_inspected_at
 * @property string|null $validated_by
 * @property Carbon|null $validated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $seller
 * @property-read User|null $validator
 * @property-read Collection<int, ListingPhoto> $photos
 * @property-read ListingPhoto|null $coverPhoto
 * @property-read Collection<int, ListingInspectionSlot> $inspectionSlots
 */
class Listing extends Model
{
    /** @use HasFactory<ListingFactory> */
    use HasFactory, HasUuids;

    protected $table = 'listings';

    protected $guarded = [];

    protected static function newFactory(): ListingFactory
    {
        return ListingFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'vehicle_type' => VehicleType::class,
            'status' => ListingStatus::class,
            'transmission' => Transmission::class,
            'fuel' => FuelType::class,
            'chassis_number' => 'encrypted',
            'negotiable' => 'boolean',
            'is_inspected' => 'boolean',
            'year' => 'integer',
            'price' => 'integer',
            'odometer_km' => 'integer',
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'last_inspected_at' => 'datetime',
            'validated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * @return HasMany<ListingPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(ListingPhoto::class, 'listing_id')->orderBy('sort_order');
    }

    /**
     * @return HasOne<ListingPhoto, $this>
     */
    public function coverPhoto(): HasOne
    {
        return $this->hasOne(ListingPhoto::class, 'listing_id')->ofMany(
            ['is_cover' => 'max', 'sort_order' => 'min'],
            fn (Builder $query) => $query->where('is_cover', true)
        );
    }

    /**
     * @return HasMany<ListingInspectionSlot, $this>
     */
    public function inspectionSlots(): HasMany
    {
        return $this->hasMany(ListingInspectionSlot::class, 'listing_id')->orderBy('slot_start');
    }

    /**
     * Scope for active listings.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ListingStatus::Active);
    }

    /**
     * Scope for pending review listings.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopePendingReview(Builder $query): Builder
    {
        return $query->where('status', ListingStatus::PendingReview);
    }

    /**
     * Scope for listings that are allowed to be shown in public listings (active, in_inspection, in_transaction).
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ListingStatus::Active,
            ListingStatus::InInspection,
            ListingStatus::InTransaction,
        ]);
    }
}
