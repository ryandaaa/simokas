<?php

declare(strict_types=1);

namespace App\Domains\Result\Contracts;

use App\Domains\Result\Models\InspectionTemplate;

/**
 * Service for updating inspection template items and versions.
 */
interface TemplateEditor
{
    /**
     * Update template items and increment version.
     *
     * @param  array<int, array{id: string, label: string, weight: int, is_required: bool, requires_photo: bool, is_critical: bool, sort_order: int}>  $itemsData
     */
    public function handle(InspectionTemplate $template, array $itemsData): InspectionTemplate;
}
