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
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class AdminDashboard extends Component
{
    public User $user;

    public function mount(): void
    {
        // Defense-in-depth: agregat ini hanya untuk admin. Blade induk
        // memang sudah switch by role, tetapi render/update susulan Livewire
        // memanggil komponen ini langsung — tanpa cek server, datanya bocor.
        /** @var User|null $viewer */
        $viewer = Auth::user();

        if ($viewer === null || ! $viewer->isAdmin()) {
            abort(403);
        }
    }

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

        // Include submitted inspection results alongside listings and inspector verification.
        $jobsNeedingAction = InspectionJob::query()->requiresAdminAction()->count();
        $itemsNeedingAction = $listingQueue + $inspectorQueue + $jobsNeedingAction;

        // Recent jobs across the platform
        $recentJobs = InspectionJob::query()
            ->with(['listing', 'requester'])
            ->latest()
            ->take(5)
            ->get();

        // Monthly trend: platform completed inspections (single grouped query)
        $monthlyCounts = InspectionJob::query()
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

        return view('dashboard::livewire.admin-dashboard', [
            'totalUsers' => $totalUsers,
            'totalInspectors' => $totalInspectors,
            'jobsByStatus' => $jobsByStatus,
            'platformEarnings' => $platformEarnings,
            'listingQueue' => $listingQueue,
            'inspectorQueue' => $inspectorQueue,
            'itemsNeedingAction' => $itemsNeedingAction,
            'recentJobs' => $recentJobs,
            'monthlySeries' => $monthlySeries,
            'user' => $this->user,
        ]);
    }
}
