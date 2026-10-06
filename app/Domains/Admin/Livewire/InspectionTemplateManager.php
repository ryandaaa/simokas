<?php

declare(strict_types=1);

namespace App\Domains\Admin\Livewire;

use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Result\Contracts\TemplateEditor;
use App\Domains\Result\Models\InspectionTemplate;
use App\Domains\Result\Models\TemplateItem;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

final class InspectionTemplateManager extends Component
{
    public ?string $selectedTemplateId = null;

    /**
     * @var array<int, array{id: string, label: string, weight: int, is_required: bool, requires_photo: bool, is_critical: bool, sort_order: int}>
     */
    #[Validate('array')]
    public array $items = [];

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->authorize('viewAny', InspectionTemplate::class);
    }

    public function selectTemplate(string $templateId): void
    {
        $this->authorize('update', InspectionTemplate::class);
        $this->resetAlerts();

        $template = InspectionTemplate::findOrFail($templateId);
        $this->selectedTemplateId = $template->id;

        // Load items from selected template
        $this->items = $template->items()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (TemplateItem $item) => [
                'id' => $item->id,
                'label' => $item->label,
                'weight' => $item->weight,
                'is_required' => $item->is_required,
                'requires_photo' => $item->requires_photo,
                'is_critical' => $item->is_critical,
                'sort_order' => $item->sort_order,
            ])
            ->toArray();
    }

    public function saveItems(): void
    {
        $this->authorize('update', InspectionTemplate::class);
        $this->resetAlerts();

        $this->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|string|exists:template_items,id',
            'items.*.label' => 'required|string|max:255',
            'items.*.weight' => 'required|integer|min:1',
            'items.*.is_required' => 'required|boolean',
            'items.*.requires_photo' => 'required|boolean',
            'items.*.is_critical' => 'required|boolean',
            'items.*.sort_order' => 'required|integer|min:0',
        ]);

        try {
            if ($this->selectedTemplateId === null) {
                throw new \Exception('No template selected');
            }

            $template = InspectionTemplate::findOrFail($this->selectedTemplateId);
            app(TemplateEditor::class)->handle($template, $this->items);

            $this->successMessage = __('admin.templates.save_success');
            $this->selectedTemplateId = null;
            $this->items = [];
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render(): View
    {
        $templates = InspectionTemplate::all()
            ->groupBy('vehicle_type')
            ->mapWithKeys(function ($group, $type) {
                return [
                    VehicleType::from($type)->label() => $group,
                ];
            });

        return view('admin::livewire.inspection-template-manager', [
            'templates' => $templates,
            'selectedTemplate' => $this->selectedTemplateId
                ? InspectionTemplate::findOrFail($this->selectedTemplateId)
                : null,
        ])
            ->layout('components.layouts.app', ['title' => __('admin.templates.title')]);
    }

    private function resetAlerts(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;
    }
}
