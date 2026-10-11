<?php

declare(strict_types=1);

namespace App\Domains\Admin\Livewire;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class JobMonitor extends Component
{
    use WithPagination;

    #[Url(as: 'status')]
    public string $filterStatus = '';

    #[Url(as: 'attention')]
    public bool $filterAttentionOnly = false;

    #[Url(as: 'q')]
    public string $search = '';

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user === null || ! $user->isAdmin()) {
            abort(403);
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterAttentionOnly(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $query = InspectionJob::query()
            ->with(['listing.seller', 'inspector', 'requester']);

        if ($this->filterStatus !== '') {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterAttentionOnly) {
            $query->requiresAdminAction();
        }

        if (trim($this->search) !== '') {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($term): void {
                $q->where('id', 'like', $term)
                    ->orWhereHas('listing', fn ($lq) => $lq->where('title', 'like', $term))
                    ->orWhereHas('requester', fn ($rq) => $rq->where('name', 'like', $term)->orWhere('email', 'like', $term))
                    ->orWhereHas('inspector', fn ($iq) => $iq->where('name', 'like', $term)->orWhere('email', 'like', $term));
            });
        }

        $query->latest('created_at');

        $jobs = $query->paginate(12);

        return view('admin::livewire.job-monitor', [
            'jobs' => $jobs,
            'statuses' => JobStatus::cases(),
        ])->layout('components.layouts.app', ['title' => __('admin.jobs.title')]);
    }
}
