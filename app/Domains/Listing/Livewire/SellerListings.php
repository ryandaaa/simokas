<?php

declare(strict_types=1);

namespace App\Domains\Listing\Livewire;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Models\Listing;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

final class SellerListings extends Component
{
    use WithPagination;

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
