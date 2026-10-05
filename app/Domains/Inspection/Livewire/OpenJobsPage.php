<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Livewire;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Actions\BrowseOpenJobs;
use App\Domains\Inspection\Actions\TakeJob;
use App\Domains\Inspection\DTOs\OpenJobFilter;
use App\Domains\Inspection\Exceptions\JobAlreadyTakenException;
use App\Domains\Inspection\Exceptions\MaxActiveJobsException;
use App\Domains\Inspection\Models\InspectionJob;
use App\Support\Exceptions\DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

final class OpenJobsPage extends Component
{
    use WithPagination;

    public ?string $vehicleType = null;

    public ?string $area = null;

    public ?int $feeMin = null;

    public ?int $feeMax = null;

    public string $sort = OpenJobFilter::SORT_LATEST;

    public ?string $confirmingJobId = null;

    public ?string $errorMessage = null;

    public function applyFilter(): void
    {
        $this->errorMessage = null;
        $this->resetPage();
    }

    public function resetFilter(): void
    {
        $this->reset(['vehicleType', 'area', 'feeMin', 'feeMax', 'errorMessage']);
        $this->sort = OpenJobFilter::SORT_LATEST;
        $this->resetPage();
    }

    public function confirmTake(string $jobId): void
    {
        $this->errorMessage = null;
        $this->confirmingJobId = $jobId;
    }

    public function cancelTake(): void
    {
        $this->confirmingJobId = null;
    }

    public function take(TakeJob $takeJob): void
    {
        if ($this->confirmingJobId === null) {
            return;
        }

        /** @var User $inspector */
        $inspector = Auth::user();

        /** @var InspectionJob|null $job */
        $job = InspectionJob::query()->find($this->confirmingJobId);

        if ($job === null) {
            $this->errorMessage = __('inspection.errors.job_already_taken');
            $this->confirmingJobId = null;
            $this->resetPage();

            return;
        }

        try {
            $updated = $takeJob->handle($inspector, $job);
            $this->confirmingJobId = null;
            session()->flash('success', __('inspection.take.success'));

            $this->redirect(route('jobs.show', ['job' => $updated->id]), navigate: true);
        } catch (JobAlreadyTakenException) {
            $this->errorMessage = __('inspection.errors.job_already_taken');
            $this->confirmingJobId = null;
            $this->resetPage();
        } catch (MaxActiveJobsException) {
            $this->errorMessage = __('inspection.errors.max_active_jobs');
            $this->confirmingJobId = null;
        } catch (DomainException $e) {
            $this->errorMessage = $e->getMessage();
            $this->confirmingJobId = null;
        }
    }

    public function render(BrowseOpenJobs $browseOpenJobs): View
    {
        /** @var User $inspector */
        $inspector = Auth::user();

        $filter = new OpenJobFilter(
            vehicleType: $this->vehicleType ?: null,
            area: $this->area ?: null,
            feeMin: $this->feeMin ? (int) $this->feeMin : null,
            feeMax: $this->feeMax ? (int) $this->feeMax : null,
            sort: $this->sort,
            page: $this->getPage(),
        );

        $jobs = null;

        try {
            $jobs = $browseOpenJobs->handle($inspector, $filter);
        } catch (Throwable) {
            $this->errorMessage = __('inspection.open_jobs.load_failed');
        }

        return view('inspection::livewire.open-jobs-page', [
            'jobs' => $jobs,
        ])->layout('components.layouts.app', ['title' => __('inspection.open_jobs.title')]);
    }
}
