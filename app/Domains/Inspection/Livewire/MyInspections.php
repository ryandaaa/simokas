<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Livewire;

use App\Domains\Inspection\Actions\CancelJobByRequester;
use App\Domains\Inspection\Models\InspectionJob;
use App\Support\Exceptions\DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

final class MyInspections extends Component
{
    use WithPagination;

    public ?string $errorMessage = null;

    public ?string $actionError = null;

    public ?string $actionSuccess = null;

    public ?string $cancellingJobId = null;

    public string $cancel_reason = '';

    public function retry(): void
    {
        $this->errorMessage = null;
        $this->actionError = null;
        $this->resetPage();
    }

    public function confirmCancel(string $jobId): void
    {
        $this->actionError = null;
        $this->actionSuccess = null;
        $this->cancellingJobId = $jobId;
        $this->cancel_reason = '';
        $this->dispatch('open-modal', 'requester-cancel-modal');
    }

    public function cancelJob(CancelJobByRequester $cancelJob): void
    {
        if (! $this->cancellingJobId) {
            return;
        }

        /** @var InspectionJob $job */
        $job = InspectionJob::query()
            ->where('requester_id', Auth::id())
            ->findOrFail($this->cancellingJobId);

        Gate::authorize('cancelAsRequester', $job);

        try {
            $this->actionError = null;
            $cancelJob->handle(Auth::user(), $job, trim($this->cancel_reason) ?: null);
            $this->actionSuccess = __('inspection.mine.cancel_success');
            $this->cancellingJobId = null;
            $this->cancel_reason = '';
            $this->dispatch('close-modal', 'requester-cancel-modal');
        } catch (DomainException $e) {
            $this->actionError = $e->getMessage();
            $this->dispatch('close-modal', 'requester-cancel-modal');
        }
    }

    public function render(): View
    {
        $jobs = null;

        try {
            $jobs = InspectionJob::query()
                ->where('requester_id', Auth::id())
                ->with('listing')
                ->latest()
                ->paginate(12);
        } catch (Throwable) {
            $this->errorMessage = __('inspection.mine.load_failed');
        }

        $cancellingJob = $this->cancellingJobId
            ? InspectionJob::query()->where('requester_id', Auth::id())->find($this->cancellingJobId)
            : null;

        return view('inspection::livewire.my-inspections', [
            'jobs' => $jobs,
            'cancellingJob' => $cancellingJob,
        ])->layout('components.layouts.app', ['title' => __('inspection.mine.title')]);
    }
}
