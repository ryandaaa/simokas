<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\Livewire;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Models\Listing;
use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Payment\Models\LedgerEntry;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class AdminDashboard extends Component
{
    public User $user;

    public function render(): View
    {
        // Total users (excluding admins)
        $totalUsers = User::query()
            ->where('role', UserRole::User)
            ->count();

        $totalInspectors = User::query()
            ->where('role', UserRole::Inspector)
            ->count();

        // Jobs by status
        $jobsByStatus = [
            'requested' => InspectionJob::where('status', JobStatus::Requested)->count(),
            'paid' => InspectionJob::where('status', JobStatus::Paid)->count(),
            'taken' => InspectionJob::where('status', JobStatus::Taken)->count(),
            'scheduled' => InspectionJob::where('status', JobStatus::Scheduled)->count(),
            'in_progress' => InspectionJob::where('status', JobStatus::InProgress)->count(),
            'result_submitted' => InspectionJob::where('status', JobStatus::ResultSubmitted)->count(),
            'completed' => InspectionJob::where('status', JobStatus::Completed)->count(),
            'cancelled' => InspectionJob::where('status', JobStatus::Cancelled)->count(),
        ];

        // Platform earnings: sum of platform_fee entries
        $platformEarnings = LedgerEntry::query()
            ->where('entry_type', LedgerEntryType::PlatformFee)
            ->sum('amount');

        // Listing queue: listings pending review
        $listingQueue = Listing::query()
            ->where('status', ListingStatus::PendingReview->value)
            ->count();

        // Inspector queue: inspectors pending verification
        $inspectorQueue = InspectorProfile::query()
            ->where('verification_status', VerificationStatus::Pending)
            ->count();

        // Items needing action: combination of pending listings and inspectors
        $itemsNeedingAction = $listingQueue + $inspectorQueue;

        return view('dashboard::livewire.admin-dashboard', [
            'totalUsers' => $totalUsers,
            'totalInspectors' => $totalInspectors,
            'jobsByStatus' => $jobsByStatus,
            'platformEarnings' => $platformEarnings,
            'listingQueue' => $listingQueue,
            'inspectorQueue' => $inspectorQueue,
            'itemsNeedingAction' => $itemsNeedingAction,
            'user' => $this->user,
        ]);
    }
}
