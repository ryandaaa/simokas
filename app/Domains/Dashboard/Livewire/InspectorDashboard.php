<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\Livewire;

use App\Domains\Dashboard\ReadModels\InspectorDashboardReader;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Models\InspectionJob;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

final class InspectorDashboard extends Component
{
    public User $user;

    #[Url(as: 'bulan')]
    public string $month = '';

    public function previousMonth(): void
    {
        $selected = $this->resolveSelectedMonth();
        $this->month = $selected->subMonthNoOverflow()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $selected = $this->resolveSelectedMonth();
        $currentMonth = CarbonImmutable::now()->startOfMonth();

        if ($selected->greaterThanOrEqualTo($currentMonth)) {
            return;
        }

        $next = $selected->addMonthNoOverflow()->startOfMonth();
        if ($next->greaterThanOrEqualTo($currentMonth)) {
            $this->month = '';
        } else {
            $this->month = $next->format('Y-m');
        }
    }

    public function render(?InspectorDashboardReader $reader = null): View
    {
        $reader ??= app(InspectorDashboardReader::class);

        $selectedMonth = $this->resolveSelectedMonth();
        $currentMonth = CarbonImmutable::now()->startOfMonth();
        $canGoNext = $selectedMonth->lessThan($currentMonth);

        $summary = $reader->summary($this->user, $selectedMonth);
        $tasks = $reader->tasks($this->user);
        $earningsBreakdown = $reader->earningsBreakdown($this->user, $selectedMonth);
        $trendSeries = $reader->monthlySeries($this->user, $selectedMonth);

        // Legacy format for current Blade view (supports both 'value' and 'completed')
        $legacyMonthlySeries = array_map(static fn (array $item): array => [
            'label' => $item['label'],
            'value' => $item['completed'],
            'ym' => $item['ym'],
            'completed' => $item['completed'],
            'earnings' => $item['earnings'],
        ], $trendSeries);

        // Preserved for existing Blade view
        $recentJobs = InspectionJob::query()
            ->where('inspector_id', $this->user->id)
            ->with(['listing', 'requester'])
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard::livewire.inspector-dashboard', [
            // New reader variables
            'summary' => $summary,
            'tasks' => $tasks,
            'earningsBreakdown' => $earningsBreakdown,
            'trendSeries' => $trendSeries,
            'selectedMonth' => $selectedMonth,
            'canGoNext' => $canGoNext,

            // Preserved legacy variables for current view & existing tests
            'availableJobs' => $summary->availableJobs,
            'activeJobs' => $summary->activeJobs,
            'netEarnings' => $summary->netEarnings,
            'totalInspections' => $summary->totalCompleted,
            'rating' => $summary->ratingAvg,
            'ratingCount' => $summary->ratingCount,
            'recentJobs' => $recentJobs,
            'monthlySeries' => $legacyMonthlySeries,
            'user' => $this->user,
        ]);
    }

    private function resolveSelectedMonth(): CarbonImmutable
    {
        $currentMonth = CarbonImmutable::now()->startOfMonth();

        if ($this->month === '') {
            return $currentMonth;
        }

        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $this->month)) {
            $this->month = '';

            return $currentMonth;
        }

        try {
            $parsed = CarbonImmutable::createFromFormat('!Y-m', $this->month)->startOfMonth();
        } catch (\Throwable) {
            $this->month = '';

            return $currentMonth;
        }

        if ($parsed->greaterThan($currentMonth)) {
            $this->month = '';

            return $currentMonth;
        }

        return $parsed;
    }
}
