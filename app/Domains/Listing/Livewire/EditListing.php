<?php

declare(strict_types=1);

namespace App\Domains\Listing\Livewire;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Actions\ResubmitListing;
use App\Domains\Listing\Models\Listing;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class EditListing extends Component
{
    public Listing $listing;

    public ?string $errorMessage = null;

    public function mount(Listing $listing): void
    {
        $this->listing = $listing;

        // Authorize via ListingPolicy::update
        $this->authorize('update', $this->listing);
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
        return view('listing::livewire.edit-listing', [
            'listing' => $this->listing->fresh(),
        ])->layout('components.layouts.app', ['title' => __('listing.edit.title')]);
    }
}
