<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\Livewire;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Payment\Models\LedgerEntry;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class InspectorDashboard extends Component
{
    public User $user;

    public function render(): View
    {
        // Available jobs: jobs that are paid and not taken
        $availableJobs = InspectionJob::query()
            ->where('status', JobStatus::Paid)
            ->whereNull('inspector_id')
            ->count();

        // Active jobs: jobs assigned to this inspector that are not completed
        $activeJobs = InspectionJob::query()
            ->where('inspector_id', $this->user->id)
            ->whereIn('status', [
                JobStatus::Taken,
                JobStatus::Scheduled,
                JobStatus::InProgress,
                JobStatus::ResultSubmitted,
            ])
            ->count();

        // Net earnings: sum of all credit ledger entries for this inspector
        $netEarnings = LedgerEntry::query()
            ->where('account_user_id', $this->user->id)
            ->where(function ($q) {
                $q->where('entry_type', LedgerEntryType::InspectionFee)
                    ->orWhere('entry_type', LedgerEntryType::AccessFee)
                    ->orWhere('entry_type', LedgerEntryType::SaleCommission);
            })
            ->sum('amount');

        // Total inspections completed
        $totalInspections = InspectionJob::query()
            ->where('inspector_id', $this->user->id)
            ->where('status', JobStatus::Completed)
            ->count();

        // Rating: read from inspector_profiles
        /** @var InspectorProfile|null $profile */
        $profile = InspectorProfile::query()->where('user_id', $this->user->id)->first();
        $rating = $profile !== null ? (float) $profile->rating_avg : 0.0;
        $ratingCount = $profile !== null ? (int) $profile->rating_count : 0;

        // Recent jobs: latest jobs handled by this inspector
        $recentJobs = InspectionJob::query()
            ->where('inspector_id', $this->user->id)
            ->with(['listing', 'requester'])
            ->latest()
            ->take(5)
            ->get();

        // Monthly trend: completed inspections per month for the last 6 months (single grouped query)
        $monthlyCounts = InspectionJob::query()
            ->where('inspector_id', $this->user->id)
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

        return view('dashboard::livewire.inspector-dashboard', [
            'availableJobs' => $availableJobs,
            'activeJobs' => $activeJobs,
            'netEarnings' => $netEarnings,
            'totalInspections' => $totalInspections,
            'rating' => $rating,
            'ratingCount' => $ratingCount,
            'recentJobs' => $recentJobs,
            'monthlySeries' => $monthlySeries,
            'user' => $this->user,
        ]);
    }
}
