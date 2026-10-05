<?php

declare(strict_types=1);

namespace App\Domains\Listing\Livewire;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Actions\ResubmitListing;
use App\Domains\Listing\Models\Listing;
use App\Support\Exceptions\DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

final class SellerListings extends Component
{
    use WithPagination;

    public ?string $confirmingResubmitId = null;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function confirmResubmit(string $listingId): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;
        $this->confirmingResubmitId = $listingId;
    }

    public function cancelResubmit(): void
    {
        $this->confirmingResubmitId = null;
    }

    public function resubmit(ResubmitListing $action): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        if ($this->confirmingResubmitId === null) {
            return;
        }

        /** @var Listing|null $listing */
        $listing = Listing::query()->find($this->confirmingResubmitId);

        if ($listing === null) {
            $this->errorMessage = __('listing.mine.not_found');
            $this->confirmingResubmitId = null;

            return;
        }

        $this->authorize('resubmit', $listing);

        /** @var User $seller */
        $seller = Auth::user();

        try {
            $action->handle($seller, $listing);
            $this->successMessage = __('listing.mine.resubmitted_success');
            $this->confirmingResubmitId = null;
        } catch (DomainException $e) {
            $this->errorMessage = $e->getMessage();
            $this->confirmingResubmitId = null;
        }
    }

    public function render(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $listings = Listing::query()
            ->where('seller_id', $user->id)
            ->with(['coverPhoto', 'photos'])
            ->latest()
            ->paginate(12);

        return view('listing::livewire.seller-listings', [
            'listings' => $listings,
        ])->layout('components.layouts.app', ['title' => __('listing.mine.title')]);
    }
}
