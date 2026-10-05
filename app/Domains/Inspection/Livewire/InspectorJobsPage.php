<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Livewire;

use App\Domains\Inspection\Actions\BrowseOpenJobs;
use App\Domains\Inspection\Models\InspectionJob;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

final class InspectorJobsPage extends Component
{
    use WithPagination;

    public ?string $errorMessage = null;

    public function retry(): void
    {
        $this->errorMessage = null;
        $this->resetPage();
    }

    public function render(): View
    {
        $jobs = null;

        try {
            $jobs = InspectionJob::query()
                ->select(BrowseOpenJobs::SAFE_JOB_COLUMNS)
                ->where('inspector_id', Auth::id())
                ->with(['listing' => fn ($q) => $q->select(BrowseOpenJobs::SAFE_LISTING_COLUMNS)])
                ->orderByRaw("CASE WHEN status IN ('taken', 'scheduled', 'in_progress', 'result_submitted') THEN 0 ELSE 1 END")
                ->orderByDesc('taken_at')
                ->paginate(12);
        } catch (Throwable) {
            $this->errorMessage = __('inspection.inspector_jobs.load_failed');
        }

        return view('inspection::livewire.inspector-jobs-page', [
            'jobs' => $jobs,
        ])->layout('components.layouts.app', ['title' => __('inspection.inspector_jobs.title')]);
    }
}
