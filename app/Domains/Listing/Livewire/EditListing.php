<?php

declare(strict_types=1);

namespace App\Domains\Listing\Livewire;

use App\Domains\Listing\Models\Listing;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class EditListing extends Component
{
    public Listing $listing;

    public function mount(Listing $listing): void
    {
        $this->listing = $listing;

        // Authorize via ListingPolicy::update
        $this->authorize('update', $this->listing);
    }

    public function render(): View
    {
        return view('listing::livewire.edit-listing', [
            'listing' => $this->listing,
        ])->layout('components.layouts.app', ['title' => __('listing.edit.title')]);
    }
}
