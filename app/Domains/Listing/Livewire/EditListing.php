<?php

declare(strict_types=1);

namespace App\Domains\Listing\Livewire;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Actions\DeactivateListing;
use App\Domains\Listing\Actions\ReactivateListing;
use App\Domains\Listing\Actions\ResubmitListing;
use App\Domains\Listing\Actions\UpdateListing;
use App\Domains\Listing\Contracts\ListingActivityChecker;
use App\Domains\Listing\DTOs\UpdateListingDTO;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Models\Listing;
use App\Domains\Listing\Rules\WithinPekanbaru;
use App\Domains\Listing\ValueObjects\PekanbaruBoundary;
use App\Support\Exceptions\DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

final class EditListing extends Component
{
    use WithFileUploads;

    public Listing $listing;

    public string $vehicle_type = 'car';

    public string $title = '';

    public string $brand = '';

    public string $model = '';

    public int|string $year = '';

    public int|string $price = '';

    public bool $negotiable = true;

    public int|string $odometer_km = '';

    public string $transmission = 'manual';

    public string $fuel = 'petrol';

    public string $color = '';

    public string $chassis_number = '';

    public string $description = '';

    public string $area_name = '';

    public mixed $lat = null;

    public mixed $lng = null;

    public string $address_full = '';

    public string $inspection_place = '';

    /** @var array<int, array{slot_start: string, note: string}> */
    public array $inspection_slots = [];

    /** @var array<int, mixed> */
    public array $newPhotos = [];

    /** @var array<int, string> */
    public array $removePhotoIds = [];

    public ?string $coverPhotoId = null;

    public ?int $coverNewPhotoIndex = null;

    public bool $confirmingSave = false;

    public bool $confirmingDeactivate = false;

    public bool $confirmingReactivate = false;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    /** @var array<int, string> */
    public array $carBrands = [
        'Toyota', 'Daihatsu', 'Honda', 'Mitsubishi', 'Suzuki',
        'Nissan', 'Hyundai', 'Wuling', 'Mazda', 'Isuzu',
    ];

    /** @var array<int, string> */
    public array $motorcycleBrands = [
        'Honda', 'Yamaha', 'Suzuki', 'Kawasaki', 'Vespa',
    ];

    public function mount(Listing $listing): void
    {
        $this->listing = $listing;

        $this->authorize('update', $this->listing);

        $this->fillFromListing();
    }

    public function fillFromListing(): void
    {
        $listing = $this->listing->fresh(['photos', 'inspectionSlots']);

        $this->vehicle_type = $listing->vehicle_type->value;
        $this->title = $listing->title;
        $this->brand = $listing->brand;
        $this->model = $listing->model;
        $this->year = $listing->year;
        $this->price = $listing->price;
        $this->negotiable = $listing->negotiable;
        $this->odometer_km = $listing->odometer_km;
        $this->transmission = $listing->transmission->value;
        $this->fuel = $listing->fuel->value;
        $this->color = $listing->color;
        $this->chassis_number = $listing->chassis_number ?? '';
        $this->description = $listing->description ?? '';
        $this->area_name = $listing->area_name ?? '';
        $this->lat = $listing->lat;
        $this->lng = $listing->lng;
        $this->address_full = $listing->address_full ?? '';
        $this->inspection_place = $listing->inspection_place;

        $this->inspection_slots = $listing->inspectionSlots
            ->map(fn ($slot): array => [
                'slot_start' => $slot->slot_start->format('Y-m-d\TH:i'),
                'note' => $slot->note ?? '',
            ])
            ->all();

        $this->newPhotos = [];
        $this->removePhotoIds = [];
        $this->coverPhotoId = null;
        $this->coverNewPhotoIndex = null;
    }

    public function isSold(): bool
    {
        return $this->listing->fresh()->status === ListingStatus::Sold;
    }

    public function isAllLocked(): bool
    {
        return $this->listing->fresh()->status === ListingStatus::InTransaction;
    }

    public function isCoreLocked(): bool
    {
        $status = $this->listing->fresh()->status;

        if ($status === ListingStatus::InInspection) {
            return true;
        }

        if ($status !== ListingStatus::Active) {
            return false;
        }

        $checker = app(ListingActivityChecker::class);

        return $checker->hasActiveJob($this->listing->id)
            || $checker->hasActiveTransaction($this->listing->id);
    }

    public function canEditFields(): bool
    {
        $status = $this->listing->fresh()->status;

        return $status !== ListingStatus::Sold && $status !== ListingStatus::InTransaction;
    }

    public function hasCoreChanges(): bool
    {
        $fresh = $this->listing->fresh();

        return $this->vehicle_type !== $fresh->vehicle_type->value
            || trim($this->brand) !== $fresh->brand
            || trim($this->model) !== $fresh->model
            || (int) $this->year !== (int) $fresh->year
            || (int) $this->odometer_km !== (int) $fresh->odometer_km
            || $this->normalizedChassis() !== $fresh->chassis_number;
    }

    public function addSlot(): void
    {
        $this->inspection_slots[] = [
            'slot_start' => now()->addDays(count($this->inspection_slots) + 2)->setHour(10)->setMinute(0)->format('Y-m-d\TH:i'),
            'note' => '',
        ];
    }

    public function removeSlot(int $index): void
    {
        if (count($this->inspection_slots) > 1) {
            unset($this->inspection_slots[$index]);
            $this->inspection_slots = array_values($this->inspection_slots);
        }
    }

    public function removeExistingPhoto(string $photoId): void
    {
        if (! in_array($photoId, $this->removePhotoIds, true)) {
            $this->removePhotoIds[] = $photoId;
        }

        if ($this->coverPhotoId === $photoId) {
            $this->coverPhotoId = null;
        }
    }

    public function setCoverPhoto(string $photoId): void
    {
        $this->coverPhotoId = $photoId;
        $this->coverNewPhotoIndex = null;
    }

    public function setCoverNewPhoto(int $index): void
    {
        if (isset($this->newPhotos[$index])) {
            $this->coverNewPhotoIndex = $index;
            $this->coverPhotoId = null;
        }
    }

    public function removeNewPhoto(int $index): void
    {
        if (isset($this->newPhotos[$index])) {
            unset($this->newPhotos[$index]);
            $this->newPhotos = array_values($this->newPhotos);

            if ($this->coverNewPhotoIndex === $index) {
                $this->coverNewPhotoIndex = null;
            } elseif (is_int($this->coverNewPhotoIndex) && $this->coverNewPhotoIndex > $index) {
                $this->coverNewPhotoIndex--;
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'vehicle_type' => ['required', 'string', 'in:car,motorcycle'],
            'title' => ['required', 'string', 'min:10', 'max:100'],
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'min:1', 'max:60'],
            'year' => ['required', 'integer', 'min:1990', 'max:'.date('Y')],
            'price' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'negotiable' => ['boolean'],
            'odometer_km' => ['required', 'integer', 'min:0', 'max:999999'],
            'transmission' => ['required', 'string', 'in:manual,automatic'],
            'fuel' => ['required', 'string', 'in:petrol,diesel,electric,hybrid'],
            'color' => ['required', 'string', 'min:1', 'max:30'],
            'chassis_number' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:2000'],
            'area_name' => ['required', 'string', 'min:1', 'max:100'],
            'address_full' => ['required', 'string', 'min:10', 'max:500'],
            'inspection_place' => ['required', 'string', 'max:255'],
            'lat' => ['required', new WithinPekanbaru('lat', 'lng')],
            'lng' => ['required', new WithinPekanbaru('lng', 'lat')],
            'inspection_slots' => ['required', 'array', 'min:1'],
            'inspection_slots.*.slot_start' => ['required', 'date', 'after:now'],
            'inspection_slots.*.note' => ['nullable', 'string', 'max:255'],
            'newPhotos' => ['array', 'max:10'],
            'newPhotos.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function save(UpdateListing $action): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        $this->authorize('update', $this->listing);

        $fresh = $this->listing->fresh();

        if ($fresh->status === ListingStatus::Sold) {
            $this->errorMessage = __('listing.errors.sold_immutable');

            return;
        }

        if ($fresh->status === ListingStatus::InTransaction) {
            $this->errorMessage = __('listing.errors.in_transaction_locked');

            return;
        }

        $this->validate();

        if ($this->hasCoreChanges() && ! $this->confirmingSave) {
            $this->confirmingSave = true;

            return;
        }

        /** @var User $actor */
        $actor = Auth::user();

        try {
            $updated = $action->handle($actor, $fresh, $this->buildDto());
        } catch (DomainException $e) {
            $this->errorMessage = $e->getMessage();
            $this->confirmingSave = false;

            return;
        }

        $this->confirmingSave = false;
        $this->listing = $updated->fresh(['photos', 'inspectionSlots']);
        $this->fillFromListing();

        $this->successMessage = $this->listing->status === ListingStatus::PendingReview
            ? __('listing.edit.sent_to_review')
            : __('listing.edit.saved_direct');
    }

    public function confirmSave(UpdateListing $action): void
    {
        $this->save($action);
    }

    public function cancelSave(): void
    {
        $this->confirmingSave = false;
    }

    public function confirmDeactivate(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;
        $this->confirmingDeactivate = true;
    }

    public function cancelDeactivate(): void
    {
        $this->confirmingDeactivate = false;
    }

    public function deactivate(DeactivateListing $action): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        $this->authorize('update', $this->listing);

        /** @var User $actor */
        $actor = Auth::user();

        try {
            $action->handle($actor, $this->listing->fresh());
        } catch (DomainException $e) {
            $this->errorMessage = $e->getMessage();
            $this->confirmingDeactivate = false;

            return;
        }

        session()->flash('success', __('listing.edit.deactivated'));

        $this->redirectRoute('listings.mine');
    }

    public function confirmReactivate(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;
        $this->confirmingReactivate = true;
    }

    public function cancelReactivate(): void
    {
        $this->confirmingReactivate = false;
    }

    public function reactivate(ReactivateListing $action): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        $this->authorize('update', $this->listing);

        /** @var User $actor */
        $actor = Auth::user();

        try {
            $action->handle($actor, $this->listing->fresh());
        } catch (DomainException $e) {
            $this->errorMessage = $e->getMessage();
            $this->confirmingReactivate = false;

            return;
        }

        session()->flash('success', __('listing.edit.reactivated'));

        $this->redirectRoute('listings.mine');
    }

    public function resubmit(ResubmitListing $action): void
    {
        $this->errorMessage = null;

        $this->authorize('resubmit', $this->listing);

        /** @var User $seller */
        $seller = Auth::user();

        $action->handle($seller, $this->listing->fresh());

        session()->flash('success', __('listing.mine.resubmitted_success'));

        $this->redirectRoute('listings.mine');
    }

    public function render(): View
    {
        $fresh = $this->listing->fresh(['photos', 'inspectionSlots']);
        $brandSuggestions = $this->vehicle_type === 'car' ? $this->carBrands : $this->motorcycleBrands;

        return view('listing::livewire.edit-listing', [
            'listing' => $fresh,
            'brandSuggestions' => $brandSuggestions,
            'mapCentre' => ['lat' => PekanbaruBoundary::CENTRE_LAT, 'lng' => PekanbaruBoundary::CENTRE_LNG],
            'sold' => $fresh->status === ListingStatus::Sold,
            'allLocked' => $fresh->status === ListingStatus::InTransaction,
            'coreLocked' => $this->isCoreLocked(),
            'canEdit' => $this->canEditFields(),
            'editable' => in_array($fresh->status, [ListingStatus::Active, ListingStatus::InInspection], true),
            'reactivatable' => $fresh->status === ListingStatus::Inactive,
        ])->layout('components.layouts.app', ['title' => __('listing.edit.title')]);
    }

    private function normalizedChassis(): ?string
    {
        $value = trim($this->chassis_number);

        return $value === '' ? null : $value;
    }

    private function buildDto(): UpdateListingDTO
    {
        $slots = [];
        foreach ($this->inspection_slots as $slot) {
            $note = trim((string) $slot['note']);

            $slots[] = [
                'slot_start' => $slot['slot_start'],
                'note' => $note === '' ? null : $note,
            ];
        }

        return new UpdateListingDTO(
            fields: [
                'vehicle_type' => $this->vehicle_type,
                'title' => $this->title,
                'brand' => $this->brand,
                'model' => $this->model,
                'year' => $this->year,
                'price' => $this->price,
                'negotiable' => $this->negotiable,
                'odometer_km' => $this->odometer_km,
                'transmission' => $this->transmission,
                'fuel' => $this->fuel,
                'color' => $this->color,
                'chassis_number' => $this->chassis_number,
                'description' => $this->description,
                'area_name' => $this->area_name,
                'lat' => $this->lat,
                'lng' => $this->lng,
                'address_full' => $this->address_full,
                'inspection_place' => $this->inspection_place,
            ],
            slots: $slots,
            removePhotoIds: $this->removePhotoIds,
            newPhotos: $this->newPhotos,
            coverPhotoId: $this->coverPhotoId,
            coverNewPhotoIndex: $this->coverNewPhotoIndex,
        );
    }
}
