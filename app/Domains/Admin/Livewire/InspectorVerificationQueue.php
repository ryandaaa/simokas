<?php

declare(strict_types=1);

namespace App\Domains\Admin\Livewire;

use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Contracts\InspectorVerificationContract;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Support\Exceptions\DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class InspectorVerificationQueue extends Component
{
    public ?string $selectedProfileId = null;

    public ?string $confirmingApproveId = null;

    public ?string $confirmingRejectId = null;

    public string $rejectionReason = '';

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user === null || ! $user->isAdmin()) {
            abort(403);
        }
    }

    public function openDetail(string $profileId): void
    {
        $this->resetAlerts();
        $this->selectedProfileId = $profileId;
    }

    public function closeDetail(): void
    {
        $this->selectedProfileId = null;
    }

    public function confirmApprove(string $profileId): void
    {
        $this->resetAlerts();
        $this->confirmingApproveId = $profileId;
    }

    public function cancelApprove(): void
    {
        $this->confirmingApproveId = null;
    }

    public function approve(InspectorVerificationContract $service): void
    {
        $this->resetAlerts();

        if ($this->confirmingApproveId === null) {
            return;
        }

        /** @var User $admin */
        $admin = Auth::user();

        /** @var InspectorProfile|null $profile */
        $profile = InspectorProfile::query()->find($this->confirmingApproveId);

        if ($profile === null) {
            $this->errorMessage = __('admin.inspector_queue.not_found');
            $this->confirmingApproveId = null;

            return;
        }

        try {
            $service->approve($admin, $profile);
            $this->successMessage = __('admin.inspector_queue.approved_success');
            $this->confirmingApproveId = null;

            if ($this->selectedProfileId === $profile->id) {
                $this->selectedProfileId = null;
            }
        } catch (DomainException $e) {
            $this->errorMessage = $e->getMessage();
            $this->confirmingApproveId = null;
        }
    }

    public function confirmReject(string $profileId): void
    {
        $this->resetAlerts();
        $this->confirmingRejectId = $profileId;
        $this->rejectionReason = '';
        $this->resetValidation();
    }

    public function cancelReject(): void
    {
        $this->confirmingRejectId = null;
        $this->rejectionReason = '';
        $this->resetValidation();
    }

    public function reject(InspectorVerificationContract $service): void
    {
        $this->resetAlerts();

        $this->validate([
            'rejectionReason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        if ($this->confirmingRejectId === null) {
            return;
        }

        /** @var User $admin */
        $admin = Auth::user();

        /** @var InspectorProfile|null $profile */
        $profile = InspectorProfile::query()->find($this->confirmingRejectId);

        if ($profile === null) {
            $this->errorMessage = __('admin.inspector_queue.not_found');
            $this->confirmingRejectId = null;
            $this->rejectionReason = '';

            return;
        }

        try {
            $service->reject($admin, $profile, $this->rejectionReason);
            $this->successMessage = __('admin.inspector_queue.rejected_success');
            $this->confirmingRejectId = null;
            $this->rejectionReason = '';

            if ($this->selectedProfileId === $profile->id) {
                $this->selectedProfileId = null;
            }
        } catch (DomainException $e) {
            $this->errorMessage = $e->getMessage();
            $this->confirmingRejectId = null;
            $this->rejectionReason = '';
        }
    }

    public function render(): View
    {
        $profiles = InspectorProfile::query()
            ->readyForVerification()
            ->with(['user', 'documents'])
            ->latest()
            ->get();

        $selectedProfile = null;
        if ($this->selectedProfileId !== null) {
            $selectedProfile = $profiles->firstWhere('id', $this->selectedProfileId)
                ?? InspectorProfile::query()->with(['user', 'documents'])->find($this->selectedProfileId);
        }

        return view('admin::livewire.inspector-verification-queue', [
            'profiles' => $profiles,
            'selectedProfile' => $selectedProfile,
        ])->layout('components.layouts.app', ['title' => __('admin.inspector_queue.title')]);
    }

    private function resetAlerts(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;
    }
}
