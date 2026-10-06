<?php

declare(strict_types=1);

namespace App\Domains\Admin\Livewire;

use App\Domains\Admin\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class AuditLogIndex extends Component
{
    use WithPagination;

    #[Url(as: 'action')]
    public string $filterAction = '';

    #[Url(as: 'entity')]
    public string $filterEntity = '';

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'sort')]
    public string $sort = 'desc';

    public function mount(): void
    {
        $this->authorize('viewAny', AuditLog::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterAction(): void
    {
        $this->resetPage();
    }

    public function updatingFilterEntity(): void
    {
        $this->resetPage();
    }

    public function updatingSort(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $query = AuditLog::query()->with('actor');

        if ($this->filterAction !== '') {
            $query->where('action', $this->filterAction);
        }

        if ($this->filterEntity !== '') {
            $query->where('entity', $this->filterEntity);
        }

        if (trim($this->search) !== '') {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($term): void {
                $q->where('entity_id', 'like', $term)
                    ->orWhereHas('actor', function ($actorQuery) use ($term): void {
                        $actorQuery->where('name', 'like', $term)
                            ->orWhere('email', 'like', $term);
                    });
            });
        }

        $query->orderBy('created_at', $this->sort === 'asc' ? 'asc' : 'desc');

        $logs = $query->paginate(15);

        return view('admin::livewire.audit-log-index', [
            'logs' => $logs,
        ])->layout('components.layouts.app', ['title' => __('admin.audit_logs.title')]);
    }
}
