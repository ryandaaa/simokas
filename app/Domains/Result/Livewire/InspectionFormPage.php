<?php

declare(strict_types=1);

namespace App\Domains\Result\Livewire;

use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Result\Actions\GetOrCreateDraftResult;
use App\Domains\Result\Actions\SaveDraftResult;
use App\Domains\Result\Enums\ItemRating;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\TemplateItem;
use App\Support\Clock;
use App\Support\Exceptions\DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class InspectionFormPage extends Component
{
    public InspectionJob $job;

    public ?InspectionResult $result = null;

    /** @var array<string, array{rating: string, note: string}> */
    public array $items = [];

    public string $summaryNote = '';

    public int $currentCategoryIndex = 0;

    public ?string $actionError = null;

    public ?string $actionSuccess = null;

    public bool $draftSaving = false;

    public ?string $lastSavedAt = null;

    /** @var array<string> */
    public array $categories = [];

    public function mount(InspectionJob $job, GetOrCreateDraftResult $getOrCreateDraft): void
    {
        // Authorization: only the assigned inspector on an in_progress job (RES-R2)
        Gate::authorize('fill-inspection', $job);

        $this->job = $job;

        try {
            $this->result = $getOrCreateDraft->handle(Auth::user(), $job);
        } catch (DomainException $e) {
            $this->actionError = $e->getMessage();

            return;
        }

        $this->loadFromResult();
    }

    private function loadFromResult(): void
    {
        if ($this->result === null) {
            return;
        }

        $this->summaryNote = $this->result->summary_note ?? '';
        $this->lastSavedAt = $this->result->updated_at
            ? Clock::toWib($this->result->updated_at)->format('H:i')
            : null;

        // Build categories list from template items
        /** @var Collection<int, TemplateItem> $templateItems */
        $templateItems = $this->result->template->items;
        $this->categories = $templateItems
            ->pluck('category')
            ->unique()
            ->values()
            ->toArray();

        // Initialize items state from existing draft items
        foreach ($templateItems as $templateItem) {
            $existingItem = $this->result->items
                ->firstWhere('template_item_id', $templateItem->id);

            $this->items[$templateItem->id] = [
                'rating' => ($existingItem !== null && $existingItem->rating !== null) ? $existingItem->rating->value : '',
                'note' => ($existingItem !== null && $existingItem->note !== null) ? $existingItem->note : '',
            ];
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'items' => ['array'],
            'items.*.rating' => ['nullable', 'string', 'in:good,fair,poor'],
            'items.*.note' => ['nullable', 'string', 'max:1000'],
            'summaryNote' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function saveDraft(SaveDraftResult $saveDraft): void
    {
        Gate::authorize('updateDraft', $this->result);

        $this->validate();

        $this->draftSaving = true;
        $this->actionError = null;
        $this->actionSuccess = null;

        try {
            $itemsData = $this->buildItemsData();
            $this->result = $saveDraft->handle(
                Auth::user(),
                $this->result,
                $itemsData,
                $this->summaryNote !== '' ? $this->summaryNote : null,
            );

            $this->lastSavedAt = Clock::toWib(Carbon::now())->format('H:i');
            $this->actionSuccess = __('inspection.form.draft_saved');
        } catch (DomainException $e) {
            $this->actionError = $e->getMessage();
        } finally {
            $this->draftSaving = false;
        }
    }

    public function nextCategory(SaveDraftResult $saveDraft): void
    {
        Gate::authorize('updateDraft', $this->result);

        $this->validate();

        // Auto-save draft when navigating categories (RES-R2)
        try {
            $itemsData = $this->buildItemsData();
            $this->result = $saveDraft->handle(
                Auth::user(),
                $this->result,
                $itemsData,
                $this->summaryNote !== '' ? $this->summaryNote : null,
            );
            $this->lastSavedAt = Clock::toWib(Carbon::now())->format('H:i');
        } catch (DomainException $e) {
            $this->actionError = $e->getMessage();

            return;
        }

        if ($this->currentCategoryIndex < count($this->categories) - 1) {
            $this->currentCategoryIndex++;
        }
    }

    public function previousCategory(): void
    {
        if ($this->currentCategoryIndex > 0) {
            $this->currentCategoryIndex--;
        }
    }

    /**
     * @return array<string, array{template_item_id: string, rating: string, note: string}>
     */
    private function buildItemsData(): array
    {
        $data = [];
        foreach ($this->items as $templateItemId => $values) {
            $data[$templateItemId] = [
                'template_item_id' => $templateItemId,
                'rating' => $values['rating'],
                'note' => $values['note'],
            ];
        }

        return $data;
    }

    /**
     * @return Collection<int, TemplateItem>
     */
    public function getCurrentCategoryItems(): Collection
    {
        if ($this->result === null) {
            return collect();
        }

        $currentCategory = $this->categories[$this->currentCategoryIndex] ?? null;
        if ($currentCategory === null) {
            return collect();
        }

        return $this->result->template->items
            ->where('category', $currentCategory)
            ->values();
    }

    public function getRatings(): array
    {
        return ItemRating::cases();
    }

    public function render(): View
    {
        $currentItems = $this->getCurrentCategoryItems();

        return view('result::livewire.inspection-form-page', [
            'job' => $this->job,
            'result' => $this->result,
            'currentItems' => $currentItems,
            'categories' => $this->categories,
            'currentCategoryIndex' => $this->currentCategoryIndex,
            'ratings' => ItemRating::cases(),
            'totalCategories' => count($this->categories),
        ])->layout('components.layouts.app', ['title' => __('inspection.form.title')]);
    }
}
