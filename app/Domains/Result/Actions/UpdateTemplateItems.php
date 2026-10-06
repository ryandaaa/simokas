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
        return DB::transaction(function () use ($template, $itemsData) {
            // Increment version
            $newVersion = $template->version + 1;
            $template->update(['version' => $newVersion]);

            // Update each item
            foreach ($itemsData as $itemData) {
                TemplateItem::query()
                    ->whereKey($itemData['id'])
                    ->where('template_id', $template->id)
                    ->update([
                        'label' => $itemData['label'],
                        'weight' => $itemData['weight'],
                        'is_required' => $itemData['is_required'],
                        'requires_photo' => $itemData['requires_photo'],
                        'is_critical' => $itemData['is_critical'],
                        'sort_order' => $itemData['sort_order'],
                    ]);
            }

            return $template->refresh();
        });
    }
}
