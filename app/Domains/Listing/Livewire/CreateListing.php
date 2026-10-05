<?php

declare(strict_types=1);

namespace App\Domains\Listing\Livewire;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Actions\CreateListing as CreateListingAction;
use App\Domains\Listing\DTOs\CreateListingDTO;
use App\Domains\Listing\Enums\FuelType;
use App\Domains\Listing\Enums\Transmission;
use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Listing\Models\Listing;
use App\Domains\Listing\Rules\WithinPekanbaru;
use App\Domains\Listing\ValueObjects\PekanbaruBoundary;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

final class CreateListing extends Component
{
    use WithFileUploads;

    public int $step = 1;

    // Step 1: Vehicle Details
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

    // Step 3: Location (map pin + full address, PRD 16.1)
    public mixed $lat = null;

    public mixed $lng = null;

    public string $address_full = '';

    // Step 2: Photos
    /** @var array<int, mixed> */
    public array $photos = [];

    public int $cover_index = 0;

    // Step 3: Inspection
    public string $inspection_place = '';

    /** @var array<int, array{slot_start: string, note: string}> */
    public array $inspection_slots = [];

    /** @var array<int, string> */
    public array $carBrands = [
        'Toyota', 'Daihatsu', 'Honda', 'Mitsubishi', 'Suzuki',
        'Nissan', 'Hyundai', 'Wuling', 'Mazda', 'Isuzu',
    ];

    /** @var array<int, string> */
    public array $motorcycleBrands = [
        'Honda', 'Yamaha', 'Suzuki', 'Kawasaki', 'Vespa',
    ];

    public function mount(): void
    {
        $this->authorize('create', Listing::class);

        if (empty($this->inspection_slots)) {
            $this->inspection_slots = [
                [
                    'slot_start' => now()->addDays(2)->setHour(10)->setMinute(0)->format('Y-m-d\TH:i'),
                    'note' => '',
                ],
            ];
        }
    }

    public function updatedVehicleType(): void
    {
        // Reset brand if it does not fit the type
        $this->brand = '';
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

    public function setCover(int $index): void
    {
        $this->cover_index = $index;
    }

    public function removePhoto(int $index): void
    {
        if (isset($this->photos[$index])) {
            unset($this->photos[$index]);
            $this->photos = array_values($this->photos);

            if ($this->cover_index >= count($this->photos)) {
                $this->cover_index = max(0, count($this->photos) - 1);
            }
        }
    }

    public function nextStep(): void
    {
        if ($this->step === 1) {
            $this->validateStep1();
            $this->step = 2;
        } elseif ($this->step === 2) {
            $this->validateStep2();
            $this->step = 3;
        }
    }

    public function previousStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function validateStep1(): void
    {
        $currentYear = (int) date('Y');

        $this->validate([
            'vehicle_type' => ['required', 'string', 'in:car,motorcycle'],
            'title' => ['required', 'string', 'min:10', 'max:100'],
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'min:1', 'max:60'],
            'year' => ['required', 'integer', 'min:1990', 'max:'.$currentYear],
            'price' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'negotiable' => ['boolean'],
            'odometer_km' => ['required', 'integer', 'min:0', 'max:999999'],
            'transmission' => ['required', 'string', 'in:manual,automatic'],
            'fuel' => ['required', 'string', 'in:petrol,diesel,electric,hybrid'],
            'color' => ['required', 'string', 'min:1', 'max:30'],
            'chassis_number' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    public function defaultMapCentre(): array
    {
        return ['lat' => PekanbaruBoundary::CENTRE_LAT, 'lng' => PekanbaruBoundary::CENTRE_LNG];
    }

    public function validateStep2(): void
    {
        $this->validate([
            'photos' => ['required', 'array', 'min:3', 'max:10'],
            'photos.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'cover_index' => ['required', 'integer', 'min:0'],
        ]);
    }

    public function validateStep3(): void
    {
        $this->validate([
            'lat' => ['required', new WithinPekanbaru('lat', 'lng')],
            'lng' => ['required', new WithinPekanbaru('lng', 'lat')],
            'address_full' => ['required', 'string', 'min:10', 'max:500'],
            'area_name' => ['required', 'string', 'min:3', 'max:100'],
            'inspection_place' => ['required', 'string', 'max:255'],
            'inspection_slots' => ['required', 'array', 'min:1'],
            'inspection_slots.*.slot_start' => ['required', 'date', 'after:now'],
            'inspection_slots.*.note' => ['nullable', 'string', 'max:255'],
        ]);
    }

    public function submit(CreateListingAction $action): mixed
    {
        $this->authorize('create', Listing::class);

        $this->validateStep1();
        $this->validateStep2();
        $this->validateStep3();

        /** @var User $user */
        $user = Auth::user();

        $dto = new CreateListingDTO(
            vehicle_type: VehicleType::from($this->vehicle_type),
            title: trim($this->title),
            brand: trim($this->brand),
            model: trim($this->model),
            year: (int) $this->year,
            price: (int) $this->price,
            negotiable: $this->negotiable,
            odometer_km: (int) $this->odometer_km,
            transmission: Transmission::from($this->transmission),
            fuel: FuelType::from($this->fuel),
            color: trim($this->color),
            inspection_place: trim($this->inspection_place),
            photos: $this->photos,
            slots: $this->inspection_slots,
            chassis_number: $this->chassis_number !== '' ? trim($this->chassis_number) : null,
            description: $this->description !== '' ? trim($this->description) : null,
            area_name: trim($this->area_name),
            lat: (float) $this->lat,
            lng: (float) $this->lng,
            address_full: trim($this->address_full),
            cover_photo_index: $this->cover_index,
        );

        $action->handle($user, $dto);

        session()->flash('success', __('listing.create.success'));

        return redirect()->route('listings.mine');
    }

    public function render(): View
    {
        $brandSuggestions = $this->vehicle_type === 'car' ? $this->carBrands : $this->motorcycleBrands;

        return view('listing::livewire.create-listing', [
            'brandSuggestions' => $brandSuggestions,
            'mapCentre' => $this->defaultMapCentre(),
        ])->layout('components.layouts.app', ['title' => __('listing.create.title')]);
    }
}
