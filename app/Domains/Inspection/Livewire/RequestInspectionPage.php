<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Livewire;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Actions\RequestInspection;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Models\Listing;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use App\Domains\Setting\Contracts\SettingsReader;
use App\Support\Exceptions\DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class RequestInspectionPage extends Component
{
    public string $listingId = '';

    public bool $confirming = false;

    public ?string $errorMessage = null;

    public function mount(Listing $listing): void
    {
        Gate::authorize('inspection.request', $listing);

        if ($listing->status !== ListingStatus::Active) {
            abort(422, __('inspection.errors.listing_not_active', ['status' => $listing->status->value]));
        }

        $this->listingId = $listing->getKey();
    }

    public function confirm(): void
    {
        $this->errorMessage = null;
        $this->confirming = true;
    }

    public function cancel(): void
    {
        $this->confirming = false;
    }

    public function submit(RequestInspection $action): RedirectResponse|bool
    {
        $listing = Listing::query()->findOrFail($this->listingId);

        Gate::authorize('inspection.request', $listing);

        /** @var User $requester */
        $requester = Auth::user();

        try {
            $result = $action->handle($requester, $listing);
        } catch (DomainException $e) {
            $this->confirming = false;
            $this->errorMessage = $e->getMessage();

            return false;
        }

        $this->confirming = false;

        return redirect()->route('payments.show', ['payment' => $result['bill']->id]);
    }

    public function render(SettingsReader $settings): View
    {
        /** @var Listing $listing */
        $listing = Listing::query()->findOrFail($this->listingId);

        /** @var User $requester */
        $requester = Auth::user();

        $fee = $listing->vehicle_type->value === 'motorcycle'
            ? $settings->getInspectionFeeMotorcycle()
            : $settings->getInspectionFeeCar();

        $existingJob = InspectionJob::query()
            ->where('listing_id', $listing->getKey())
            ->where('requester_id', $requester->getKey())
            ->active()
            ->latest()
            ->first();

        $existingBillId = null;

        if ($existingJob instanceof InspectionJob) {
            $existingBillId = Payment::query()
                ->where('purpose', PaymentPurpose::Inspection->value)
                ->where('ref_id', $existingJob->getKey())
                ->whereIn('status', [
                    PaymentStatus::Pending->value,
                    PaymentStatus::Failed->value,
                    PaymentStatus::Success->value,
                ])
                ->latest()
                ->first()
                ?->getKey();
        }

        return view('inspection::livewire.request-inspection-page', [
            'listing' => $listing,
            'fee' => $fee,
            'existingJob' => $existingJob,
            'existingBillId' => $existingBillId,
        ])->layout('components.layouts.app', ['title' => __('inspection.request.title')]);
    }
}
