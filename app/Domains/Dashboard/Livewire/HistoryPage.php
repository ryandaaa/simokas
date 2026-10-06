<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\Livewire;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspector\Contracts\InspectorEligibility;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\ResultAccess;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class HistoryPage extends Component
{
    use WithPagination;

    #[Url(except: 'all')]
    public string $tab = 'all';

    public function setTab(string $tab): void
    {
        $allowedTabs = ['all', 'buying', 'selling', 'results', 'jobs'];
        if (in_array($tab, $allowedTabs, true)) {
            $this->tab = $tab;
        }
    }

    public function render(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $isApprovedInspector = $user->isInspector()
            && app(InspectorEligibility::class)->canViewAndTakeJobs($user);

        // (a) Transactions as buyer
        $buyingTransactions = Transaction::query()
            ->where('buyer_id', $user->id)
            ->with(['listing'])
            ->latest('created_at')
            ->paginate(10, ['*'], 'buying_page')
            ->withQueryString();

        // (a) Transactions as seller (on user's listings)
        $sellingTransactions = Transaction::query()
            ->whereHas('listing', function ($q) use ($user): void {
                $q->where('seller_id', $user->id);
            })
            ->with(['listing', 'buyer'])
            ->latest('created_at')
            ->paginate(10, ['*'], 'selling_page')
            ->withQueryString();

        // (b) Inspection results accessed or paid by user (also results where user is the job requester)
        // Remains visible even if listing is sold
        $paidResultIds = ResultAccess::query()
            ->where('user_id', $user->id)
            ->pluck('result_id');

        $inspectionResults = InspectionResult::query()
            ->where(function ($query) use ($user, $paidResultIds): void {
                $query->whereIn('id', $paidResultIds)
                    ->orWhereHas('job', function ($jobQuery) use ($user): void {
                        $jobQuery->where('requester_id', $user->id);
                    });
            })
            ->with(['listing', 'job', 'inspector'])
            ->latest('submitted_at')
            ->latest('created_at')
            ->paginate(10, ['*'], 'results_page')
            ->withQueryString();

        // (c) Inspector job history (completed, cancelled, expired jobs for approved inspector)
        $inspectorJobs = null;
        if ($isApprovedInspector) {
            $inspectorJobs = InspectionJob::query()
                ->where('inspector_id', $user->id)
                ->whereIn('status', [
                    JobStatus::Completed,
                    JobStatus::Cancelled,
                    JobStatus::Expired,
                ])
                ->with(['listing'])
                ->latest('updated_at')
                ->paginate(10, ['*'], 'jobs_page')
                ->withQueryString();
        }

        return view('dashboard::livewire.history-page', [
            'user' => $user,
            'isApprovedInspector' => $isApprovedInspector,
            'buyingTransactions' => $buyingTransactions,
            'sellingTransactions' => $sellingTransactions,
            'inspectionResults' => $inspectionResults,
            'inspectorJobs' => $inspectorJobs,
        ])->layout('components.layouts.app', [
            'title' => __('dashboard.history.title'),
        ]);
    }
}
