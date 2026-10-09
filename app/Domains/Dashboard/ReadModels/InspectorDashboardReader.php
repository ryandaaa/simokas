<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\ReadModels;

use App\Domains\Dashboard\DTOs\EarningsSlice;
use App\Domains\Dashboard\DTOs\InspectorDashboardSummary;
use App\Domains\Dashboard\DTOs\InspectorTask;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Listing\Models\Listing;
use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Payment\Models\LedgerEntry;
use Carbon\CarbonImmutable;

final class InspectorDashboardReader
{
    /**
     * Relevant fee entry types contributing to inspector earnings.
     *
     * @var array<int, LedgerEntryType>
     */
    private const EARNING_ENTRY_TYPES = [
        LedgerEntryType::InspectionFee,
        LedgerEntryType::AccessFee,
        LedgerEntryType::SaleCommission,
    ];

    public function summary(User $inspector, CarbonImmutable $month): InspectorDashboardSummary
    {
        $startOfMonth = $month->startOfMonth();
        $endOfMonth = $month->endOfMonth();

        $prevMonth = $startOfMonth->subMonthNoOverflow();
        $startOfPrevMonth = $prevMonth->startOfMonth();
        $endOfPrevMonth = $prevMonth->endOfMonth();

        $netEarnings = (int) LedgerEntry::query()
            ->where('account_user_id', $inspector->id)
            ->whereIn('entry_type', self::EARNING_ENTRY_TYPES)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $previousMonthEarnings = (int) LedgerEntry::query()
            ->where('account_user_id', $inspector->id)
            ->whereIn('entry_type', self::EARNING_ENTRY_TYPES)
            ->whereBetween('created_at', [$startOfPrevMonth, $endOfPrevMonth])
            ->sum('amount');

        $changePercent = $previousMonthEarnings === 0
            ? null
            : round((($netEarnings - $previousMonthEarnings) / $previousMonthEarnings) * 100, 2);

        $availableJobs = InspectionJob::query()
            ->where('status', JobStatus::Paid)
            ->whereNull('inspector_id')
            ->count();

        $activeJobs = InspectionJob::query()
            ->where('inspector_id', $inspector->id)
            ->whereIn('status', [
                JobStatus::Taken,
                JobStatus::Scheduled,
                JobStatus::InProgress,
                JobStatus::ResultSubmitted,
            ])
            ->count();

        $completedInMonth = InspectionJob::query()
            ->where('inspector_id', $inspector->id)
            ->where('status', JobStatus::Completed)
            ->whereBetween('completed_at', [$startOfMonth, $endOfMonth])
            ->count();

        $totalCompleted = InspectionJob::query()
            ->where('inspector_id', $inspector->id)
            ->where('status', JobStatus::Completed)
            ->count();

        /** @var InspectorProfile|null $profile */
        $profile = InspectorProfile::query()->where('user_id', $inspector->id)->first();
        $ratingAvg = $profile !== null ? (float) $profile->rating_avg : 0.0;
        $ratingCount = $profile !== null ? (int) $profile->rating_count : 0;

        return new InspectorDashboardSummary(
            netEarnings: $netEarnings,
            previousMonthEarnings: $previousMonthEarnings,
            changePercent: $changePercent,
            availableJobs: $availableJobs,
            activeJobs: $activeJobs,
            completedInMonth: $completedInMonth,
            totalCompleted: $totalCompleted,
            ratingAvg: $ratingAvg,
            ratingCount: $ratingCount,
        );
    }

    /**
     * @return array<int, InspectorTask>
     */
    public function tasks(User $inspector, int $limit = 10): array
    {
        $jobs = InspectionJob::query()
            ->where('inspector_id', $inspector->id)
            ->whereIn('status', [
                JobStatus::Taken,
                JobStatus::Scheduled,
                JobStatus::InProgress,
            ])
            ->with(['listing'])
            ->orderByRaw('CASE WHEN scheduled_at IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('scheduled_at', 'asc')
            ->orderBy('id', 'asc')
            ->take($limit)
            ->get();

        $tasks = [];
        foreach ($jobs as $job) {
            $listing = $job->listing;
            $listingTitle = $listing instanceof Listing ? $listing->title : '';
            $city = $listing instanceof Listing ? $listing->area_name : null;

            $actionRoute = $job->status === JobStatus::InProgress
                ? 'result.fill'
                : 'jobs.show';

            $tasks[] = new InspectorTask(
                jobId: (string) $job->id,
                listingTitle: $listingTitle,
                city: $city,
                scheduledAt: $job->scheduled_at?->toImmutable(),
                status: $job->status,
                actionRoute: $actionRoute,
                actionParams: ['job' => (string) $job->id],
            );
        }

        return $tasks;
    }

    /**
     * @return array<int, EarningsSlice>
     */
    public function earningsBreakdown(User $inspector, CarbonImmutable $month): array
    {
        $startOfMonth = $month->startOfMonth();
        $endOfMonth = $month->endOfMonth();

        $entries = LedgerEntry::query()
            ->where('account_user_id', $inspector->id)
            ->whereIn('entry_type', self::EARNING_ENTRY_TYPES)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->selectRaw('entry_type, SUM(amount) as total_amount')
            ->groupBy('entry_type')
            ->pluck('total_amount', 'entry_type');

        $total = (int) $entries->sum();
        if ($total <= 0) {
            return [];
        }

        $slices = [];
        foreach (self::EARNING_ENTRY_TYPES as $type) {
            $amount = (int) ($entries[$type->value] ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $slices[] = new EarningsSlice(
                type: $type,
                amount: $amount,
                percent: round(($amount / $total) * 100, 2),
            );
        }

        return $slices;
    }

    /**
     * @return array<int, array{label: string, ym: string, completed: int, earnings: int}>
     */
    public function monthlySeries(User $inspector, CarbonImmutable $endMonth): array
    {
        $endMonthStart = $endMonth->startOfMonth();
        $startPeriod = $endMonthStart->subMonthsNoOverflow(5)->startOfMonth();
        $endPeriod = $endMonth->endOfMonth();

        $completedCounts = InspectionJob::query()
            ->where('inspector_id', $inspector->id)
            ->where('status', JobStatus::Completed)
            ->whereBetween('completed_at', [$startPeriod, $endPeriod])
            ->selectRaw("DATE_FORMAT(completed_at, '%Y-%m') as ym, COUNT(*) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $earningsSums = LedgerEntry::query()
            ->where('account_user_id', $inspector->id)
            ->whereIn('entry_type', self::EARNING_ENTRY_TYPES)
            ->whereBetween('created_at', [$startPeriod, $endPeriod])
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(amount) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $series = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $endMonthStart->subMonthsNoOverflow($i);
            $ym = $month->format('Y-m');

            $series[] = [
                'label' => $month->locale('id')->shortMonthName,
                'ym' => $ym,
                'completed' => (int) ($completedCounts[$ym] ?? 0),
                'earnings' => (int) ($earningsSums[$ym] ?? 0),
            ];
        }

        return $series;
    }
}
