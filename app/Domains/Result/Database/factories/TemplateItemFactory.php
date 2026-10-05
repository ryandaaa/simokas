<?php

declare(strict_types=1);

namespace App\Domains\Result\Database\factories;

use App\Domains\Result\Models\InspectionTemplate;
use App\Domains\Result\Models\TemplateItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TemplateItem>
 */
class TemplateItemFactory extends Factory
{
    protected $model = TemplateItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'template_id' => InspectionTemplate::factory(),
            'category' => 'Dokumen',
            'label' => 'STNK',
            'weight' => 1,
            'is_required' => true,
            'requires_photo' => false,
            'is_critical' => false,
            'sort_order' => 1,
        ];
    }

    public function critical(): static
    {
        return $this->state(fn () => [
            'is_critical' => true,
        ]);
    }

    public function requiresPhoto(): static
    {
        return $this->state(fn () => [
            'requires_photo' => true,
        ]);
    }

    public function optional(): static
    {
        return $this->state(fn () => [
            'is_required' => false,
        ]);
    }
}
