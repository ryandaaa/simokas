<?php

declare(strict_types=1);

namespace App\Domains\Admin\Livewire;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Contracts\ListingModerationContract;
use App\Domains\Listing\DTOs\ListingModerationDetail;
use App\Support\Exceptions\DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

final class ListingValidationQueue extends Component
{
    use WithPagination;

    public ?string $selectedListingId = null;

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

    public function openDetail(string $listingId): void
    {
        $this->resetAlerts();
        $this->selectedListingId = $listingId;
    }

    public function closeDetail(): void
    {
        $this->selectedListingId = null;
    }

    public function confirmApprove(string $listingId): void
    {
        $this->resetAlerts();
        $this->confirmingApproveId = $listingId;
    }

    public function cancelApprove(): void
    {
        $this->confirmingApproveId = null;
    }

    public function approve(ListingModerationContract $moderation): void
    {
        $this->resetAlerts();

        if ($this->confirmingApproveId === null) {
            return;
        }

        /** @var User $admin */
        $admin = Auth::user();

        Gate::authorize('listing.moderate');

        try {
            $moderation->approve($admin, $this->confirmingApproveId);
            $this->successMessage = __('admin.listing_queue.approved_success');
            $this->confirmingApproveId = null;

            if ($this->selectedListingId !== null) {
                $this->selectedListingId = null;
            }
        } catch (DomainException $e) {
            $this->errorMessage = $this->friendlyErrorMessage($e);
            $this->confirmingApproveId = null;
        }
    }

    public function confirmReject(string $listingId): void
    {
        $this->resetAlerts();
        $this->confirmingRejectId = $listingId;
        $this->rejectionReason = '';
        $this->resetValidation();
    }

    public function cancelReject(): void
    {
        $this->confirmingRejectId = null;
        $this->rejectionReason = '';
        $this->resetValidation();
    }

    public function reject(ListingModerationContract $moderation): void
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

        Gate::authorize('listing.moderate');

        try {
            $moderation->reject($admin, $this->confirmingRejectId, $this->rejectionReason);
            $this->successMessage = __('admin.listing_queue.rejected_success');
            $this->confirmingRejectId = null;
            $this->rejectionReason = '';

            if ($this->selectedListingId !== null) {
                $this->selectedListingId = null;
            }
        } catch (DomainException $e) {
            $this->errorMessage = $this->friendlyErrorMessage($e);
            $this->confirmingRejectId = null;
            $this->rejectionReason = '';
        }
    }

    public function render(ListingModerationContract $moderation): View
    {
        $queue = $moderation->queue(perPage: 12);

        $selectedDetail = null;
        if ($this->selectedListingId !== null) {
            try {
                $selectedDetail = $moderation->detail($this->selectedListingId);
            } catch (DomainException) {
                $selectedDetail = null;
            } catch (ModelNotFoundException) {
                $selectedDetail = null;
            }

            if ($selectedDetail instanceof ListingModerationDetail && $selectedDetail->status !== 'pending_review') {
                $selectedDetail = null;
                $this->selectedListingId = null;
            }
        }

        return view('admin::livewire.listing-validation-queue', [
            'queue' => $queue,
            'selectedDetail' => $selectedDetail,
        ])->layout('components.layouts.app', ['title' => __('admin.listing_queue.title')]);
    }

    private function friendlyErrorMessage(DomainException $e): string
    {
        if ($e->getErrorCode() === DomainException::CODE_CONFLICT) {
            return __('admin.listing_queue.already_processed');
        }

        return $e->getMessage();
    }

    private function resetAlerts(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;
    }
}
