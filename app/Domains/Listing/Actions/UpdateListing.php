<?php

declare(strict_types=1);

namespace App\Domains\Listing\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Contracts\ListingActivityChecker;
use App\Domains\Listing\DTOs\UpdateListingDTO;
use App\Domains\Listing\Enums\FuelType;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Enums\Transmission;
use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Listing\Events\ListingUpdated;
use App\Domains\Listing\Exceptions\InvalidListingStatusTransitionException;
use App\Domains\Listing\Exceptions\ListingLockedException;
use App\Domains\Listing\Exceptions\ListingModerationException;
use App\Domains\Listing\Exceptions\ListingValidationException;
use App\Domains\Listing\Exceptions\PhotoUploadException;
use App\Domains\Listing\Models\Listing;
use App\Domains\Listing\Models\ListingInspectionSlot;
use App\Domains\Listing\Models\ListingPhoto;
use App\Domains\Listing\ValueObjects\PekanbaruBoundary;
use finfo;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
use ValueError;

/**
 * Edit an active listing following D-05.
 *
 * Non-core changes (price, negotiable, title, transmission, fuel, color,
 * description, location, inspection place and slots, photos) apply directly.
 * Core changes (vehicle_type, brand, model, year, chassis_number,
 * odometer_km) send the listing back to pending_review and revoke the
 * inspection badge. Core fields are locked while a job or transaction is
 * active; everything is locked while in_transaction; sold is immutable.
 */
final class UpdateListing
{
    public const array CORE_FIELDS = [
        'vehicle_type',
        'brand',
        'model',
        'year',
        'chassis_number',
        'odometer_km',
    ];

    public const int MIN_PHOTOS = 3;

    public const int MAX_PHOTOS = 10;

    public const int MAX_PHOTO_SIZE_BYTES = 2 * 1024 * 1024;

    public const array ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public function __construct(
        private readonly Dispatcher $events,
        private readonly ListingActivityChecker $activity,
    ) {}

    public function handle(User $actor, Listing $listing, UpdateListingDTO $dto): Listing
    {
        $this->assertActor($actor, $listing);

        $status = $listing->status;

        if ($status === ListingStatus::Sold) {
            throw ListingLockedException::soldImmutable();
        }

        if ($status === ListingStatus::InTransaction) {
            throw ListingLockedException::allFieldsLockedInTransaction();
        }

        if (! in_array($status, [ListingStatus::Active, ListingStatus::InInspection], true)) {
            throw InvalidListingStatusTransitionException::notEditable($status);
        }

        $scalarChanges = $this->normalizeScalars($listing, $dto->fields);
        $normalizedSlots = $this->normalizeSlots($dto->slots);
        $photoPlan = $this->planPhotos($listing, $dto);

        $coreChanged = collect($scalarChanges)
            ->only(self::CORE_FIELDS)
            ->contains(fn (mixed $change): bool => $change['changed']);

        $nonCoreChanged = collect($scalarChanges)
            ->except(self::CORE_FIELDS)
            ->contains(fn (mixed $change): bool => $change['changed']);

        $slotsChanged = $normalizedSlots !== null && $this->slotsDiffer($listing, $normalizedSlots);

        if ($coreChanged && $this->coreLocked($listing)) {
            throw ListingLockedException::coreFieldsLocked();
        }

        if (! $coreChanged && ! $nonCoreChanged && ! $slotsChanged && ! $photoPlan['changed']) {
            return $listing->fresh();
        }

        $storedPaths = [];
        $newPhotoRows = [];

        try {
            foreach ($photoPlan['new_files'] as $file) {
                $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'jpg';
                $randomFileName = Str::uuid()->toString().'.'.strtolower($extension);
                $directory = "listings/{$listing->id}";

                $storedPath = Storage::disk('private')->putFileAs($directory, $file, $randomFileName);
                if (! is_string($storedPath)) {
                    $storedPath = "{$directory}/{$randomFileName}";
                }

                $storedPaths[] = $storedPath;
                $newPhotoRows[] = $storedPath;
            }

            $result = DB::transaction(function () use ($listing, $status, $scalarChanges, $coreChanged, $normalizedSlots, $slotsChanged, $photoPlan, $newPhotoRows): Listing {
                $columns = [];
                foreach ($scalarChanges as $field => $change) {
                    if ($change['changed']) {
                        $columns[$change['column']] = $change['value'];
                    }
                }

                if ($coreChanged) {
                    $columns['status'] = ListingStatus::PendingReview->value;
                    $columns['is_inspected'] = false;
                    $columns['last_inspected_at'] = null;
                    $columns['rejection_reason'] = null;
                    $columns['validated_by'] = null;
                    $columns['validated_at'] = null;
                }

                $columns['updated_at'] = now();

                // Conditional write: the row lock taken by this UPDATE keeps the
                // status stable inside the transaction. MySQL reports zero
                // affected rows when new values equal the stored ones, so the
                // status is verified with a fresh read instead of the count.
                Listing::query()
                    ->whereKey($listing->getKey())
                    ->where('status', $status->value)
                    ->update($columns);

                /** @var Listing $freshStatus */
                $freshStatus = Listing::query()->findOrFail($listing->getKey());

                $targetStatus = $coreChanged ? ListingStatus::PendingReview : $status;

                if ($freshStatus->status !== $targetStatus) {
                    throw InvalidListingStatusTransitionException::alreadyProcessed($freshStatus->status);
                }

                if ($photoPlan['remove_ids'] !== []) {
                    ListingPhoto::query()
                        ->where('listing_id', $listing->id)
                        ->whereIn('id', $photoPlan['remove_ids'])
                        ->delete();
                }

                $createdIds = [];
                $sortOrder = $photoPlan['next_sort_order'];
                foreach ($newPhotoRows as $storedPath) {
                    /** @var ListingPhoto $photo */
                    $photo = ListingPhoto::query()->create([
                        'listing_id' => $listing->id,
                        'storage_path' => $storedPath,
                        'sort_order' => $sortOrder++,
                        'is_cover' => false,
                    ]);
                    $createdIds[] = $photo->id;
                }

                if ($photoPlan['changed']) {
                    ListingPhoto::query()->where('listing_id', $listing->id)->update(['is_cover' => false]);

                    $coverId = $photoPlan['cover_photo_id'];
                    if ($photoPlan['cover_new_photo_index'] !== null) {
                        $coverId = $createdIds[$photoPlan['cover_new_photo_index']];
                    }

                    ListingPhoto::query()
                        ->where('listing_id', $listing->id)
                        ->where('id', $coverId)
                        ->update(['is_cover' => true]);
                }

                if ($slotsChanged) {
                    ListingInspectionSlot::query()->where('listing_id', $listing->id)->delete();

                    foreach ($normalizedSlots as $slot) {
                        ListingInspectionSlot::query()->create([
                            'listing_id' => $listing->id,
                            'slot_start' => $slot['slot_start'],
                            'note' => $slot['note'],
                        ]);
                    }
                }

                $listing->refresh();

                $this->events->dispatch(new ListingUpdated(
                    listingId: $listing->id,
                    sellerId: $listing->seller_id,
                    sentToReview: $coreChanged,
                ));

                return $listing;
            });

            foreach ($photoPlan['removed_paths'] as $path) {
                Storage::disk('private')->delete($path);
            }

            return $result;
        } catch (Throwable $e) {
            foreach ($storedPaths as $path) {
                Storage::disk('private')->delete($path);
            }

            throw $e;
        }
    }

    private function assertActor(User $actor, Listing $listing): void
    {
        if ($actor->isBlocked() || ! $actor->isActive()) {
            throw ListingValidationException::invalidSeller();
        }

        if (! $actor->isAdmin() && $actor->id !== $listing->seller_id) {
            throw ListingModerationException::forbidden();
        }
    }

    private function coreLocked(Listing $listing): bool
    {
        if ($listing->status === ListingStatus::InInspection) {
            return true;
        }

        return $this->activity->hasActiveJob($listing->id)
            || $this->activity->hasActiveTransaction($listing->id);
    }

    /**
     * Normalize provided scalar fields.
     *
     * @param  array<string, mixed>  $fields
     * @return array<string, array{column: string, value: mixed, changed: bool}>
     */
    private function normalizeScalars(Listing $listing, array $fields): array
    {
        $currentYear = (int) date('Y');
        $out = [];

        $take = function (string $field, string $column, mixed $normalized) use ($listing, &$out): void {
            $current = $listing->getAttribute($column);

            if ($current instanceof \BackedEnum) {
                $current = $current->value;
            }

            if (is_bool($current) || is_bool($normalized)) {
                $changed = (bool) $current !== (bool) $normalized;
                $normalized = (bool) $normalized;
            } elseif (is_float($current) || is_float($normalized) || $column === 'lat' || $column === 'lng') {
                $changed = round((float) $current, 7) !== round((float) $normalized, 7);
            } elseif (is_int($current) || is_int($normalized)) {
                $changed = (int) $current !== (int) $normalized;
                $normalized = (int) $normalized;
            } else {
                $changed = $current !== $normalized;
            }

            $out[$field] = ['column' => $column, 'value' => $normalized, 'changed' => $changed];
        };

        foreach ($fields as $field => $raw) {
            switch ($field) {
                case 'vehicle_type':
                    $take($field, 'vehicle_type', $this->parseEnumValue(VehicleType::class, $raw, $field));
                    break;
                case 'transmission':
                    $take($field, 'transmission', $this->parseEnumValue(Transmission::class, $raw, $field));
                    break;
                case 'fuel':
                    $take($field, 'fuel', $this->parseEnumValue(FuelType::class, $raw, $field));
                    break;
                case 'title':
                    $value = trim((string) $raw);
                    $this->assertLength($field, $value, 10, 100);
                    $take($field, 'title', $value);
                    break;
                case 'brand':
                    $value = trim((string) $raw);
                    $this->assertLength($field, $value, 1, 100);
                    $take($field, 'brand', $value);
                    break;
                case 'model':
                    $value = trim((string) $raw);
                    $this->assertLength($field, $value, 1, 60);
                    $take($field, 'model', $value);
                    break;
                case 'color':
                    $value = trim((string) $raw);
                    $this->assertLength($field, $value, 1, 30);
                    $take($field, 'color', $value);
                    break;
                case 'year':
                    $value = $this->parseInt($field, $raw);
                    if ($value < 1990 || $value > $currentYear) {
                        throw ListingValidationException::fieldInvalid($field);
                    }
                    $take($field, 'year', $value);
                    break;
                case 'price':
                    $value = $this->parseInt($field, $raw);
                    if ($value < 1 || $value > 999999999999) {
                        throw ListingValidationException::fieldInvalid($field);
                    }
                    $take($field, 'price', $value);
                    break;
                case 'odometer_km':
                    $value = $this->parseInt($field, $raw);
                    if ($value < 0 || $value > 999999) {
                        throw ListingValidationException::fieldInvalid($field);
                    }
                    $take($field, 'odometer_km', $value);
                    break;
                case 'negotiable':
                    $take($field, 'negotiable', (bool) $raw);
                    break;
                case 'chassis_number':
                    $value = trim((string) $raw);
                    $value = $value === '' ? null : $value;
                    if ($value !== null && mb_strlen($value) > 30) {
                        throw ListingValidationException::fieldInvalid($field);
                    }
                    $take($field, 'chassis_number', $value);
                    break;
                case 'description':
                    $value = trim((string) $raw);
                    $value = $value === '' ? null : $value;
                    if ($value !== null && mb_strlen($value) > 2000) {
                        throw ListingValidationException::fieldInvalid($field);
                    }
                    $take($field, 'description', $value);
                    break;
                case 'inspection_place':
                    $value = trim((string) $raw);
                    $this->assertLength($field, $value, 1, 255);
                    $take($field, 'inspection_place', $value);
                    break;
                case 'area_name':
                    $value = trim((string) $raw);
                    $this->assertLength($field, $value, 1, 100);
                    $take($field, 'area_name', $value);
                    break;
                case 'address_full':
                    $value = trim((string) $raw);
                    $this->assertLength($field, $value, 10, 500);
                    $take($field, 'address_full', $value);
                    break;
                case 'lat':
                case 'lng':
                    // Handled together below so both coordinates stay consistent.
                    break;
                default:
                    // Unknown keys (seller_id, status, is_inspected, ...) are ignored.
                    break;
            }
        }

        if (array_key_exists('lat', $fields) || array_key_exists('lng', $fields)) {
            if (! array_key_exists('lat', $fields) || ! array_key_exists('lng', $fields)) {
                throw ListingValidationException::locationRequired();
            }

            $lat = $this->parseCoordinate('lat', $fields['lat']);
            $lng = $this->parseCoordinate('lng', $fields['lng']);

            if (! PekanbaruBoundary::contains($lat, $lng)) {
                throw ListingValidationException::locationOutsidePekanbaru();
            }

            $take('lat', 'lat', $lat);
            $take('lng', 'lng', $lng);
        }

        return $out;
    }

    /**
     * @template T of \BackedEnum
     *
     * @param  class-string<T>  $enum
     */
    private function parseEnumValue(string $enum, mixed $raw, string $field): string
    {
        if ($raw instanceof \BackedEnum) {
            return (string) $raw->value;
        }

        try {
            return $enum::from((string) $raw)->value;
        } catch (ValueError) {
            throw ListingValidationException::fieldInvalid($field);
        }
    }

    private function parseInt(string $field, mixed $raw): int
    {
        if (! is_numeric($raw)) {
            throw ListingValidationException::fieldInvalid($field);
        }

        return (int) $raw;
    }

    private function parseCoordinate(string $field, mixed $raw): float
    {
        if (! is_numeric($raw)) {
            throw ListingValidationException::locationInvalid();
        }

        $value = (float) $raw;

        if (! is_finite($value)) {
            throw ListingValidationException::locationInvalid();
        }

        if ($field === 'lat' && ($value < -90 || $value > 90)) {
            throw ListingValidationException::locationInvalid();
        }

        if ($field === 'lng' && ($value < -180 || $value > 180)) {
            throw ListingValidationException::locationInvalid();
        }

        return $value;
    }

    private function assertLength(string $field, string $value, int $min, int $max): void
    {
        $length = mb_strlen($value);

        if ($length < $min || $length > $max) {
            throw ListingValidationException::fieldInvalid($field);
        }
    }

    /**
     * @param  array<int, array{slot_start: mixed, note?: string|null}>|null  $slots
     * @return array<int, array{slot_start: Carbon, note: string|null}|null>
     */
    private function normalizeSlots(?array $slots): ?array
    {
        if ($slots === null) {
            return null;
        }

        if ($slots === []) {
            throw ListingValidationException::slotsRequired();
        }

        $now = now();
        $normalized = [];

        foreach ($slots as $slot) {
            $slotStart = Carbon::parse($slot['slot_start']);

            if ($slotStart->isPast() && $slotStart->diffInMinutes($now, false) > 1) {
                throw ListingValidationException::slotInPast();
            }

            $note = isset($slot['note']) && $slot['note'] !== '' ? (string) $slot['note'] : null;
            if ($note !== null && mb_strlen($note) > 255) {
                throw ListingValidationException::fieldInvalid('inspection_slots');
            }

            $normalized[] = ['slot_start' => $slotStart, 'note' => $note];
        }

        return $normalized;
    }

    /**
     * @param  array<int, array{slot_start: Carbon, note: string|null}>  $normalizedSlots
     */
    private function slotsDiffer(Listing $listing, array $normalizedSlots): bool
    {
        $current = ListingInspectionSlot::query()
            ->where('listing_id', $listing->id)
            ->orderBy('slot_start')
            ->get()
            ->map(fn (ListingInspectionSlot $slot): array => [
                'slot_start' => $slot->slot_start->format('Y-m-d H:i'),
                'note' => $slot->note,
            ])
            ->all();

        $incoming = collect($normalizedSlots)
            ->map(fn (array $slot): array => [
                'slot_start' => $slot['slot_start']->format('Y-m-d H:i'),
                'note' => $slot['note'],
            ])
            ->sortBy('slot_start')
            ->values()
            ->all();

        return $current !== $incoming;
    }

    /**
     * @return array{remove_ids: array<int, string>, removed_paths: array<int, string>, new_files: array<int, UploadedFile>, cover_photo_id: string|null, cover_new_photo_index: int|null, next_sort_order: int, changed: bool}
     */
    private function planPhotos(Listing $listing, UpdateListingDTO $dto): array
    {
        $emptyPlan = [
            'remove_ids' => [],
            'removed_paths' => [],
            'new_files' => [],
            'cover_photo_id' => null,
            'cover_new_photo_index' => null,
            'next_sort_order' => 0,
            'changed' => false,
        ];

        if ($dto->removePhotoIds === [] && $dto->newPhotos === []
            && $dto->coverPhotoId === null && $dto->coverNewPhotoIndex === null) {
            return $emptyPlan;
        }

        $existing = ListingPhoto::query()
            ->where('listing_id', $listing->id)
            ->orderBy('sort_order')
            ->get();

        $existingIds = $existing->pluck('id')->all();
        $removeIds = array_values(array_intersect(
            array_map(strval(...), $dto->removePhotoIds),
            array_map(strval(...), $existingIds)
        ));

        $retained = $existing->reject(fn (ListingPhoto $photo): bool => in_array($photo->id, $removeIds, true));
        $removedPaths = $existing
            ->filter(fn (ListingPhoto $photo): bool => in_array($photo->id, $removeIds, true))
            ->pluck('storage_path')
            ->all();

        $newFiles = [];
        $finfo = new finfo(FILEINFO_MIME_TYPE);

        foreach ($dto->newPhotos as $rawFile) {
            if (! $rawFile instanceof UploadedFile) {
                throw PhotoUploadException::unsupportedType('unknown');
            }

            if ($rawFile->getSize() > self::MAX_PHOTO_SIZE_BYTES) {
                throw PhotoUploadException::fileTooLarge(self::MAX_PHOTO_SIZE_BYTES);
            }

            $realMime = $finfo->file($rawFile->getRealPath());
            if ($realMime === false || ! in_array($realMime, self::ALLOWED_MIME_TYPES, true)) {
                throw PhotoUploadException::unsupportedType((string) $realMime);
            }

            $newFiles[] = $rawFile;
        }

        $finalCount = $retained->count() + count($newFiles);
        if ($finalCount < self::MIN_PHOTOS || $finalCount > self::MAX_PHOTOS) {
            throw PhotoUploadException::countInvalid($finalCount);
        }

        $currentCover = $existing->firstWhere('is_cover', true);
        $currentCoverId = $currentCover?->id;

        $coverPhotoId = null;
        $coverNewPhotoIndex = null;

        if ($dto->coverPhotoId !== null) {
            if (! in_array($dto->coverPhotoId, $retained->pluck('id')->all(), true)) {
                throw PhotoUploadException::coverInvalid();
            }
            $coverPhotoId = $dto->coverPhotoId;
        } elseif ($dto->coverNewPhotoIndex !== null) {
            if (! isset($newFiles[$dto->coverNewPhotoIndex])) {
                throw PhotoUploadException::coverInvalid();
            }
            $coverNewPhotoIndex = $dto->coverNewPhotoIndex;
        } elseif ($currentCoverId !== null && $retained->firstWhere('id', $currentCoverId) !== null) {
            $coverPhotoId = $currentCoverId;
        } else {
            $coverPhotoId = $retained->first()->id;
        }

        $changed = $removeIds !== []
            || $newFiles !== []
            || ($dto->coverPhotoId !== null && $dto->coverPhotoId !== $currentCoverId)
            || $dto->coverNewPhotoIndex !== null;

        return [
            'remove_ids' => $removeIds,
            'removed_paths' => $removedPaths,
            'new_files' => $newFiles,
            'cover_photo_id' => $coverPhotoId,
            'cover_new_photo_index' => $coverNewPhotoIndex,
            'next_sort_order' => ($existing->max('sort_order') ?? -1) + 1,
            'changed' => $changed,
        ];
    }
}
