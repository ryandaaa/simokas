<?php

declare(strict_types=1);

namespace App\Domains\Admin\Livewire;

use App\Domains\Admin\Actions\RecordAuditLog;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Contracts\InspectionJobModerationContract;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Result\Contracts\ResultModerationContract;
use App\Domains\Result\Enums\ResultStatus;
use App\Domains\Result\Models\InspectionResult;
use App\Support\Exceptions\DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class JobDetail extends Component
{
    public InspectionJob $job;

    public bool $showCancelModal = false;

    public string $cancelReason = '';

    public bool $refund = false;

    public bool $showCompleteModal = false;

    public bool $showReopenModal = false;

    public string $reopenReason = '';

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function mount(InspectionJob $job): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user === null || ! $user->isAdmin()) {
            abort(403);
        }

        $this->job = $job->loadMissing(['listing.seller', 'inspector', 'requester']);
    }

    public function openCancelModal(): void
    {
        $this->resetAlerts();
        $this->cancelReason = '';
        $this->refund = in_array($this->job->status, [JobStatus::Paid, JobStatus::Taken, JobStatus::Scheduled], true);
        $this->showCancelModal = true;
        $this->dispatch('open-modal', 'cancel-job-modal');
    }

    public function closeCancelModal(): void
    {
        $this->showCancelModal = false;
        $this->cancelReason = '';
        $this->dispatch('close-modal', 'cancel-job-modal');
        $this->resetValidation();
    }

    public function cancelJob(InspectionJobModerationContract $moderation): void
    {
        $this->resetAlerts();

        $this->validate([
            'cancelReason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        /** @var User $admin */
        $admin = Auth::user();

        try {
            $moderation->cancelJob($admin, $this->job, $this->cancelReason, $this->refund);
            $this->job->refresh();
            $this->closeCancelModal();
            $this->successMessage = __('admin.jobs.cancel_success');
        } catch (DomainException $e) {
            $this->closeCancelModal();
            $this->errorMessage = $this->friendlyErrorMessage($e);
        }
    }

    public function openCompleteModal(): void
    {
        $this->resetAlerts();
        $this->showCompleteModal = true;
        $this->dispatch('open-modal', 'complete-job-modal');
    }

    public function closeCompleteModal(): void
    {
        $this->showCompleteModal = false;
        $this->dispatch('close-modal', 'complete-job-modal');
    }

    public function completeJob(InspectionJobModerationContract $moderation, RecordAuditLog $recordAuditLog): void
    {
        $this->resetAlerts();

        /** @var User $admin */
        $admin = Auth::user();

        try {
            $moderation->completeJob($this->job);

            $recordAuditLog->handle(
                actor: $admin,
                action: 'complete_job',
                entity: 'job',
                entityId: (string) $this->job->id,
                meta: [
                    'listing_id' => (string) $this->job->listing_id,
                    'inspector_id' => $this->job->inspector_id,
                ],
            );

            $this->job->refresh();
            $this->closeCompleteModal();
            $this->successMessage = __('admin.jobs.complete_success');
        } catch (DomainException $e) {
            $this->closeCompleteModal();
            $this->errorMessage = $this->friendlyErrorMessage($e);
        }
    }

    public function openReopenModal(): void
    {
        $this->resetAlerts();
        $this->reopenReason = '';
        $this->showReopenModal = true;
        $this->dispatch('open-modal', 'reopen-result-modal');
    }

    public function closeReopenModal(): void
    {
        $this->showReopenModal = false;
        $this->reopenReason = '';
        $this->dispatch('close-modal', 'reopen-result-modal');
        $this->resetValidation();
    }

    public function reopenResult(ResultModerationContract $moderation): void
    {
        $this->resetAlerts();

        $this->validate([
            'reopenReason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        /** @var User $admin */
        $admin = Auth::user();

        /** @var InspectionResult|null $result */
        $result = InspectionResult::query()->where('job_id', $this->job->id)->first();

        if ($result === null) {
            $this->closeReopenModal();
            $this->errorMessage = __('admin.jobs.not_found');

            return;
        }

        try {
            $moderation->reopen($admin, $result, $this->reopenReason);
            $this->job->refresh();
            $this->closeReopenModal();
            $this->successMessage = __('admin.jobs.reopen_success');
        } catch (DomainException $e) {
            $this->closeReopenModal();
            $this->errorMessage = $this->friendlyErrorMessage($e);
        }
    }

    public function render(): View
    {
        /** @var InspectionResult|null $result */
        $result = InspectionResult::query()
            ->with(['items', 'photos'])
            ->where('job_id', $this->job->id)
            ->first();

        $canCancel = ! $this->job->isFinal();
        $canComplete = $this->job->status === JobStatus::ResultSubmitted;
        $canReopen = $this->job->status === JobStatus::ResultSubmitted
            && $result !== null
            && $result->status === ResultStatus::Submitted;

        return view('admin::livewire.job-detail', [
            'result' => $result,
            'canCancel' => $canCancel,
            'canComplete' => $canComplete,
            'canReopen' => $canReopen,
        ])->layout('components.layouts.app', [
            'title' => __('admin.jobs.detail_title', ['id' => $this->job->id]),
        ]);
    }

    private function friendlyErrorMessage(DomainException $e): string
    {
        if (
            $e->getErrorCode() === DomainException::CODE_CONFLICT
            || $e->getCode() === 409
            || $e instanceof \App\Domains\Inspection\Exceptions\InvalidJobStatusTransitionException
        ) {
            return __('admin.jobs.already_processed');
        }

        return $e->getMessage();
    }

    private function resetAlerts(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;
    }
}
