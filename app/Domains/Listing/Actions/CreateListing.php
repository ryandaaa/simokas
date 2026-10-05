<?php

declare(strict_types=1);

namespace App\Domains\Listing\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\DTOs\CreateListingDTO;
use App\Domains\Listing\Enums\FuelType;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Enums\Transmission;
use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Listing\Events\ListingCreated;
use App\Domains\Listing\Events\ListingSubmitted;
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

final class CreateListing
{
    public const int MIN_PHOTOS = 3;

    public const int MAX_PHOTOS = 10;

    public const int MAX_PHOTO_SIZE_BYTES = 2 * 1024 * 1024; // 2 MB

    public const array ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public function __construct(
        private readonly Dispatcher $events,
    ) {}

    public function handle(User $seller, CreateListingDTO $dto): Listing
    {
        // 1. Otorisasi penjual
        if ($seller->isBlocked() || ! $seller->isActive()) {
            throw ListingValidationException::invalidSeller();
        }

        if ($seller->isInspector()) {
            throw ListingValidationException::inspectorForbidden();
        }

        if ($seller->isAdmin()) {
            throw ListingValidationException::adminForbidden();
        }

        if (! $seller->isUser()) {
            throw ListingValidationException::invalidSeller();
        }

        // 2. Validasi jumlah foto (3–10)
        $photoCount = count($dto->photos);
        if ($photoCount < self::MIN_PHOTOS || $photoCount > self::MAX_PHOTOS) {
            throw PhotoUploadException::countInvalid($photoCount);
        }

        // 3. Normalisasi & Validasi setiap foto
        $normalizedPhotos = [];
        $coverCount = 0;
        $finfo = new finfo(FILEINFO_MIME_TYPE);

        foreach ($dto->photos as $index => $photoItem) {
            /** @var mixed $rawFile */
            $rawFile = is_array($photoItem) ? $photoItem['file'] : $photoItem;

            if (! $rawFile instanceof UploadedFile) {
                throw PhotoUploadException::unsupportedType('unknown');
            }

            $file = $rawFile;

            // Validasi ukuran berkas (maks 2 MB)
            if ($file->getSize() > self::MAX_PHOTO_SIZE_BYTES) {
                throw PhotoUploadException::fileTooLarge(self::MAX_PHOTO_SIZE_BYTES);
            }

            // Validasi MIME sebenarnya dari isi berkas menggunakan finfo
            $realMime = $finfo->file($file->getRealPath());
            if ($realMime === false || ! in_array($realMime, self::ALLOWED_MIME_TYPES, true)) {
                throw PhotoUploadException::unsupportedType((string) $realMime);
            }

            // Tentukan foto sampul
            $isCover = false;
            if (is_array($photoItem) && isset($photoItem['is_cover'])) {
                $isCover = (bool) $photoItem['is_cover'];
            } elseif ($dto->cover_photo_index !== null) {
                $isCover = ($index === $dto->cover_photo_index);
            } elseif ($index === 0) {
                $isCover = true;
            }

            if ($isCover) {
                $coverCount++;
            }

            $sortOrder = (is_array($photoItem) && isset($photoItem['sort_order']))
                ? (int) $photoItem['sort_order']
                : $index;

            $normalizedPhotos[] = [
                'file' => $file,
                'is_cover' => $isCover,
                'sort_order' => $sortOrder,
                'mime' => $realMime,
            ];
        }

        // Harus ada tepat satu foto sampul
        if ($coverCount !== 1) {
            throw PhotoUploadException::oneCoverRequired();
        }

        // 4. Validasi slot inspeksi (min 1, tidak di masa lalu)
        if (empty($dto->slots)) {
            throw ListingValidationException::slotsRequired();
        }

        $now = now();
        $normalizedSlots = [];
        foreach ($dto->slots as $slot) {
            $slotStart = Carbon::parse($slot['slot_start']);
            if ($slotStart->isPast() && $slotStart->diffInMinutes($now, false) > 1) {
                throw ListingValidationException::slotInPast();
            }

            $normalizedSlots[] = [
                'slot_start' => $slotStart,
                'note' => isset($slot['note']) && $slot['note'] !== '' ? (string) $slot['note'] : null,
            ];
        }

        // 5. Validasi lokasi di server (LST-R3): titik wajib ada, numerik,
        // dalam rentang, dan di dalam wilayah Pekanbaru. Dijaga di Action
        // agar berlaku untuk semua pemanggil, bukan hanya form Livewire.
        if ($dto->lat === null || $dto->lng === null) {
            throw ListingValidationException::locationRequired();
        }

        if (! is_finite($dto->lat) || ! is_finite($dto->lng)) {
            throw ListingValidationException::locationInvalid();
        }

        if ($dto->lat < -90 || $dto->lat > 90 || $dto->lng < -180 || $dto->lng > 180) {
            throw ListingValidationException::locationInvalid();
        }

        if (! PekanbaruBoundary::contains($dto->lat, $dto->lng)) {
            throw ListingValidationException::locationOutsidePekanbaru();
        }

        $addressFull = trim((string) ($dto->address_full ?? ''));
        if ($addressFull === '' || mb_strlen($addressFull) < 10 || mb_strlen($addressFull) > 500) {
            throw ListingValidationException::locationRequired();
        }

        $areaName = trim((string) ($dto->area_name ?? ''));
        if ($areaName === '' || mb_strlen($areaName) > 100) {
            throw ListingValidationException::locationRequired();
        }

        // 6. Simpan transaksi database dan berkas di disk privat
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($seller, $dto, $normalizedPhotos, $normalizedSlots, &$storedPaths): Listing {
                /** @var Listing $listing */
                $listing = Listing::query()->create([
                    'seller_id' => $seller->id,
                    'vehicle_type' => $dto->vehicle_type instanceof VehicleType ? $dto->vehicle_type : VehicleType::from((string) $dto->vehicle_type),
                    'title' => $dto->title,
                    'brand' => $dto->brand,
                    'model' => $dto->model,
                    'year' => $dto->year,
                    'price' => $dto->price,
                    'negotiable' => $dto->negotiable,
                    'odometer_km' => $dto->odometer_km,
                    'transmission' => $dto->transmission instanceof Transmission ? $dto->transmission : Transmission::from((string) $dto->transmission),
                    'fuel' => $dto->fuel instanceof FuelType ? $dto->fuel : FuelType::from((string) $dto->fuel),
                    'color' => $dto->color,
                    'chassis_number' => $dto->chassis_number,
                    'description' => $dto->description,
                    'area_name' => $dto->area_name,
                    'lat' => $dto->lat,
                    'lng' => $dto->lng,
                    'address_full' => $dto->address_full,
                    'inspection_place' => $dto->inspection_place,
                    'status' => ListingStatus::PendingReview,
                    'is_inspected' => false,
                ]);

                // Simpan berkas foto ke disk privat
                $directory = "listings/{$listing->id}";

                foreach ($normalizedPhotos as $photoData) {
                    /** @var UploadedFile $file */
                    $file = $photoData['file'];
                    $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'jpg';
                    $randomFileName = Str::uuid()->toString().'.'.strtolower($extension);

                    $storedPath = Storage::disk('private')->putFileAs($directory, $file, $randomFileName);
                    if (! is_string($storedPath)) {
                        $storedPath = "{$directory}/{$randomFileName}";
                    }
                    $storedPaths[] = $storedPath;

                    ListingPhoto::query()->create([
                        'listing_id' => $listing->id,
                        'storage_path' => $storedPath,
                        'sort_order' => $photoData['sort_order'],
                        'is_cover' => $photoData['is_cover'],
                    ]);
                }

                // Simpan slot inspeksi
                foreach ($normalizedSlots as $slotData) {
                    ListingInspectionSlot::query()->create([
                        'listing_id' => $listing->id,
                        'slot_start' => $slotData['slot_start'],
                        'note' => $slotData['note'],
                    ]);
                }

                $this->events->dispatch(new ListingCreated($listing));
                $this->events->dispatch(new ListingSubmitted(
                    listingId: $listing->id,
                    sellerId: $listing->seller_id,
                ));

                return $listing->load(['photos', 'inspectionSlots']);
            });
        } catch (Throwable $e) {
            // Bersihkan berkas yang sudah terunggah agar tidak ada berkas yatim
            foreach ($storedPaths as $path) {
                Storage::disk('private')->delete($path);
            }

            throw $e;
        }
    }
}
