<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\Livewire;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Models\Listing;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Models\Transaction;
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

        // Active transactions (buyer view, P1): offered, accepted, paid_held
        $activeTransactions = Transaction::query()
            ->where('buyer_id', $this->user->id)
            ->active()
            ->count();

        // Incoming buy offers (seller view, P1): offered transactions on own listings
        $buyOffers = Transaction::query()
            ->where('status', TransactionStatus::Offered)
            ->whereHas('listing', fn ($query) => $query->where('seller_id', $this->user->id))
            ->count();

        // Recent inspections requested by this user
        $recentInspections = InspectionJob::query()
            ->where('requester_id', $this->user->id)
            ->with('listing')
            ->latest()
            ->take(5)
            ->get();

        // Recent transactions (as buyer or as seller via listing)
        $recentTransactions = Transaction::query()
            ->where(fn ($query) => $query
                ->where('buyer_id', $this->user->id)
                ->orWhereHas('listing', fn ($q) => $q->where('seller_id', $this->user->id)))
            ->with('listing')
            ->latest()
            ->take(5)
            ->get();

        // Monthly trend: completed inspections requested by this user (single grouped query)
        $monthlyCounts = InspectionJob::query()
            ->where('requester_id', $this->user->id)
            ->where('status', JobStatus::Completed)
            ->where('completed_at', '>=', now()->subMonthsNoOverflow(5)->startOfMonth())
            ->selectRaw("DATE_FORMAT(completed_at, '%Y-%m') as ym, COUNT(*) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $monthlySeries = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonthsNoOverflow($i);
            $monthlySeries[] = [
                'label' => $month->locale('id')->shortMonthName,
                'value' => (int) ($monthlyCounts[$month->format('Y-m')] ?? 0),
            ];
        }

        return view('dashboard::livewire.buyer-seller-dashboard', [
            'runningInspections' => $runningInspections,
            'accessibleResults' => $accessibleResults,
            'activeListings' => $activeListings,
            'activeTransactions' => $activeTransactions,
            'buyOffers' => $buyOffers,
            'recentInspections' => $recentInspections,
            'recentTransactions' => $recentTransactions,
            'monthlySeries' => $monthlySeries,
            'user' => $this->user,
        ]);
    }
}
