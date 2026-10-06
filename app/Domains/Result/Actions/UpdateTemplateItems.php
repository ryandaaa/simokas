<?php

declare(strict_types=1);

namespace App\Domains\Result\Actions;

use App\Domains\Result\Contracts\TemplateEditor;
use App\Domains\Result\Models\InspectionTemplate;
use App\Domains\Result\Models\TemplateItem;
use Illuminate\Support\Facades\DB;

/**
 * Update template items (properties and sort order) and increment template version.
 * Old results and in-progress drafts maintain their original template version.
 */
final class UpdateTemplateItems implements TemplateEditor
{
    /**
     * @param  array<int, array{id: string, label: string, weight: int, is_required: bool, requires_photo: bool, is_critical: bool, sort_order: int}>  $itemsData
     */
    public function handle(InspectionTemplate $template, array $itemsData): InspectionTemplate
    {
        return DB::transaction(function () use ($template, $itemsData): InspectionTemplate {
            /** @var InspectionTemplate $source */
            $source = InspectionTemplate::query()
                ->whereKey($template->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // Lock and get the highest version for this vehicle type to ensure serialization
            $highestVersion = (int) InspectionTemplate::query()
                ->where('vehicle_type', $source->vehicle_type)
                ->lockForUpdate()
                ->max('version');

            $newVersion = $highestVersion + 1;

            // Deactivate source template and all older versions for this vehicle type
            InspectionTemplate::query()
                ->where('vehicle_type', $source->vehicle_type)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // Create new template version
            /** @var InspectionTemplate $newTemplate */
            $newTemplate = InspectionTemplate::query()->create([
                'vehicle_type' => $source->vehicle_type,
                'name' => $source->name,
                'version' => $newVersion,
                'is_active' => true,
            ]);

            // Load original items from the source template
            $sourceItems = TemplateItem::query()
                ->where('template_id', $source->id)
                ->orderBy('sort_order')
                ->get();

            // Map edited item data by source item id
            $itemsById = collect($itemsData)->keyBy('id');

            // Copy all items to the new template with edited values
            foreach ($sourceItems as $sourceItem) {
                /** @var array{id?: string, label?: string, weight?: int, is_required?: bool, requires_photo?: bool, is_critical?: bool, sort_order?: int}|null $edited */
                $edited = $itemsById->get($sourceItem->id);

                TemplateItem::query()->create([
                    'template_id' => $newTemplate->id,
                    'category' => $sourceItem->category,
                    'label' => $edited['label'] ?? $sourceItem->label,
                    'weight' => isset($edited['weight']) ? (int) $edited['weight'] : $sourceItem->weight,
                    'is_required' => isset($edited['is_required']) ? (bool) $edited['is_required'] : $sourceItem->is_required,
                    'requires_photo' => isset($edited['requires_photo']) ? (bool) $edited['requires_photo'] : $sourceItem->requires_photo,
                    'is_critical' => isset($edited['is_critical']) ? (bool) $edited['is_critical'] : $sourceItem->is_critical,
                    'sort_order' => isset($edited['sort_order']) ? (int) $edited['sort_order'] : $sourceItem->sort_order,
                ]);
            }

            return $newTemplate->load('items');
        });
    }
}
