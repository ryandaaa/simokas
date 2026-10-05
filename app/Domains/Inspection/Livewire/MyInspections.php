<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Livewire;

use App\Domains\Inspection\Models\InspectionJob;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

final class MyInspections extends Component
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
                ->where('requester_id', Auth::id())
                ->with('listing')
                ->latest()
                ->paginate(12);
        } catch (Throwable) {
            $this->errorMessage = __('inspection.mine.load_failed');
        }

        return view('inspection::livewire.my-inspections', [
            'jobs' => $jobs,
        ])->layout('components.layouts.app', ['title' => __('inspection.mine.title')]);
    }
}
