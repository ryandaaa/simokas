<?php

declare(strict_types=1);

namespace App\Domains\Admin\Livewire;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Models\Listing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class UnifiedAdminQueue extends Component
{
    use WithPagination;

    #[Url(as: 'type')]
    public string $filterType = 'all';

    public int $listingCount = 0;

    public int $inspectorCount = 0;

    public int $jobCount = 0;

    public int $totalCount = 0;

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user === null || ! $user->isAdmin()) {
            abort(403);
        }
    }

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $this->listingCount = Listing::query()
            ->where('status', ListingStatus::PendingReview->value)
            ->count();

        $this->inspectorCount = InspectorProfile::query()
            ->readyForVerification()
            ->count();

        $this->jobCount = InspectionJob::query()
            ->whereNotNull('needs_attention_at')
            ->whereNotIn('status', [JobStatus::Completed->value, JobStatus::Cancelled->value, JobStatus::Expired->value])
            ->count();

        $this->totalCount = $this->listingCount + $this->inspectorCount + $this->jobCount;

        $queueItems = $this->buildQueueItems();

        return view('admin::livewire.unified-admin-queue', [
            'queueItems' => $queueItems,
            'listingCount' => $this->listingCount,
            'inspectorCount' => $this->inspectorCount,
            'jobCount' => $this->jobCount,
            'totalCount' => $this->totalCount,
        ])->layout('components.layouts.app', ['title' => __('admin.unified_queue.title')]);
    }

    private function buildQueueItems(): LengthAwarePaginator
    {
        $perPage = 12;
        $page = $this->getPage();

        if ($this->filterType === 'listing') {
            $paginator = Listing::query()
                ->where('status', ListingStatus::PendingReview->value)
                ->with(['seller', 'photos'])
                ->orderBy('created_at', 'asc')
                ->paginate($perPage);

            $transformed = $paginator->getCollection()->map(fn (Listing $l): array => [
                'type' => 'listing',
                'id' => (string) $l->id,
                'title' => $l->title,
                'subtitle' => 'Penjual: '.$l->seller->name,
                'time' => $l->created_at,
                'status_label' => (string) __('listing.status.pending_review'),
                'status_variant' => 'warning',
                'status_icon' => 'jam',
                'url' => route('admin.listings.index'),
                'model' => $l,
            ]);

            return $paginator->setCollection($transformed);
        }

        if ($this->filterType === 'inspector') {
            $paginator = InspectorProfile::query()
                ->readyForVerification()
                ->with(['user', 'documents'])
                ->orderBy('created_at', 'asc')
                ->paginate($perPage);

            $transformed = $paginator->getCollection()->map(fn (InspectorProfile $p): array => [
                'type' => 'inspector',
                'id' => (string) $p->id,
                'title' => 'Verifikasi Inspektor: '.$p->user->name,
                'subtitle' => $p->user->email,
                'time' => $p->created_at,
                'status_label' => 'Menunggu Verifikasi',
                'status_variant' => 'warning',
                'status_icon' => 'jam',
                'url' => route('admin.inspectors.index'),
                'model' => $p,
            ]);

            return $paginator->setCollection($transformed);
        }

        if ($this->filterType === 'job') {
            $paginator = InspectionJob::query()
                ->whereNotNull('needs_attention_at')
                ->whereNotIn('status', [JobStatus::Completed->value, JobStatus::Cancelled->value, JobStatus::Expired->value])
                ->with(['listing', 'inspector', 'requester'])
                ->orderBy('needs_attention_at', 'asc')
                ->paginate($perPage);

            $transformed = $paginator->getCollection()->map(fn (InspectionJob $j): array => [
                'type' => 'job',
                'id' => (string) $j->id,
                'title' => 'Job Perlu Tindakan: '.$j->listing->title,
                'subtitle' => 'Status: '.$j->status->label().' • Pemohon: '.$j->requester->name,
                'time' => $j->needs_attention_at ?? $j->created_at,
                'status_label' => 'Perlu Tindakan',
                'status_variant' => 'danger',
                'status_icon' => 'segitiga-peringatan',
                'url' => route('admin.jobs.show', $j->id),
                'model' => $j,
            ]);

            return $paginator->setCollection($transformed);
        }

        // 'all': fetch lightweight keys + timestamps
        $listingList = Listing::query()
            ->where('status', ListingStatus::PendingReview->value)
            ->select(['id', 'created_at'])
            ->get()
            ->map(fn (Listing $l): array => [
                'type' => 'listing',
                'id' => (string) $l->id,
                'timestamp' => $l->created_at->timestamp,
            ]);

        $inspectorList = InspectorProfile::query()
            ->readyForVerification()
            ->select(['id', 'created_at'])
            ->get()
            ->map(fn (InspectorProfile $p): array => [
                'type' => 'inspector',
                'id' => (string) $p->id,
                'timestamp' => $p->created_at->timestamp,
            ]);

        $jobList = InspectionJob::query()
            ->whereNotNull('needs_attention_at')
            ->whereNotIn('status', [JobStatus::Completed->value, JobStatus::Cancelled->value, JobStatus::Expired->value])
            ->select(['id', 'needs_attention_at', 'created_at'])
            ->get()
            ->map(fn (InspectionJob $j): array => [
                'type' => 'job',
                'id' => (string) $j->id,
                'timestamp' => ($j->needs_attention_at ?? $j->created_at)->timestamp,
            ]);

        $merged = $listingList->concat($inspectorList)->concat($jobList)
            ->sortBy('timestamp')
            ->values();

        $total = $merged->count();
        $slice = $merged->forPage($page, $perPage);

        $listingIds = $slice->where('type', 'listing')->pluck('id')->all();
        $inspectorIds = $slice->where('type', 'inspector')->pluck('id')->all();
        $jobIds = $slice->where('type', 'job')->pluck('id')->all();

        $listings = ! empty($listingIds)
            ? Listing::query()->whereIn('id', $listingIds)->with(['seller', 'photos'])->get()->keyBy('id')
            : collect();

        $inspectors = ! empty($inspectorIds)
            ? InspectorProfile::query()->whereIn('id', $inspectorIds)->with(['user', 'documents'])->get()->keyBy('id')
            : collect();

        $jobs = ! empty($jobIds)
            ? InspectionJob::query()->whereIn('id', $jobIds)->with(['listing', 'inspector', 'requester'])->get()->keyBy('id')
            : collect();

        $items = $slice->map(function (array $item) use ($listings, $inspectors, $jobs): ?array {
            if ($item['type'] === 'listing') {
                /** @var Listing|null $model */
                $model = $listings->get($item['id']);
                if ($model === null) {
                    return null;
                }

                return [
                    'type' => 'listing',
                    'id' => (string) $model->id,
                    'title' => $model->title,
                    'subtitle' => 'Penjual: '.$model->seller->name,
                    'time' => $model->created_at,
                    'status_label' => (string) __('listing.status.pending_review'),
                    'status_variant' => 'warning',
                    'status_icon' => 'jam',
                    'url' => route('admin.listings.index'),
                    'model' => $model,
                ];
            }

            if ($item['type'] === 'inspector') {
                /** @var InspectorProfile|null $model */
                $model = $inspectors->get($item['id']);
                if ($model === null) {
                    return null;
                }

                return [
                    'type' => 'inspector',
                    'id' => (string) $model->id,
                    'title' => 'Verifikasi Inspektor: '.$model->user->name,
                    'subtitle' => $model->user->email,
                    'time' => $model->created_at,
                    'status_label' => 'Menunggu Verifikasi',
                    'status_variant' => 'warning',
                    'status_icon' => 'jam',
                    'url' => route('admin.inspectors.index'),
                    'model' => $model,
                ];
            }

            /** @var InspectionJob|null $model */
            $model = $jobs->get($item['id']);
            if ($model === null) {
                return null;
            }

            return [
                'type' => 'job',
                'id' => (string) $model->id,
                'title' => 'Job Perlu Tindakan: '.$model->listing->title,
                'subtitle' => 'Status: '.$model->status->label().' • Pemohon: '.$model->requester->name,
                'time' => $model->needs_attention_at ?? $model->created_at,
                'status_label' => 'Perlu Tindakan',
                'status_variant' => 'danger',
                'status_icon' => 'segitiga-peringatan',
                'url' => route('admin.jobs.show', $model->id),
                'model' => $model,
            ];
        })->filter()->values();

        return new Paginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }
}
