<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\Livewire;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Models\Listing;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class BuyerSellerDashboard extends Component
{
    public User $user;

    public function render(): View
    {
        // Running inspections: jobs requested/paid/taken/scheduled/in_progress by this user
        $runningInspections = InspectionJob::query()
            ->where('requester_id', $this->user->id)
            ->whereIn('status', [
                JobStatus::Requested,
                JobStatus::Paid,
                JobStatus::Taken,
                JobStatus::Scheduled,
                JobStatus::InProgress,
            ])
            ->count();

        // Accessible results: completed jobs where user is requester
        $accessibleResults = InspectionJob::query()
            ->where('requester_id', $this->user->id)
            ->where('status', JobStatus::Completed)
            ->count();

        // Active listings (seller view)
        $activeListings = Listing::query()
            ->where('seller_id', $this->user->id)
            ->where('status', ListingStatus::Active->value)
            ->count();

        return view('dashboard::livewire.buyer-seller-dashboard', [
            'runningInspections' => $runningInspections,
            'accessibleResults' => $accessibleResults,
            'activeListings' => $activeListings,
            'user' => $this->user,
        ]);
    }
}
