<?php

declare(strict_types=1);

namespace App\Domains\Result\Tests\Unit;

use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Result\Actions\UpdateTemplateItems;
use App\Domains\Result\Models\InspectionTemplate;
use App\Domains\Result\Models\TemplateItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UpdateTemplateItemsTest extends TestCase
{
    use RefreshDatabase;

    private UpdateTemplateItems $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new UpdateTemplateItems;
    }

    public function test_adm_t12_copies_template_to_new_version_and_deactivates_old_version(): void
    {
        $template = InspectionTemplate::factory()->car()->create([
            'name' => 'Template Mobil',
            'version' => 1,
            'is_active' => true,
        ]);

        $item1 = TemplateItem::factory()->create([
            'template_id' => $template->id,
            'category' => 'Dokumen',
            'label' => 'STNK',
            'weight' => 1,
            'is_required' => true,
            'requires_photo' => true,
            'is_critical' => false,
            'sort_order' => 1,
        ]);

        $item2 = TemplateItem::factory()->create([
            'template_id' => $template->id,
            'category' => 'Dokumen',
            'label' => 'BPKB',
            'weight' => 2,
            'is_required' => true,
            'requires_photo' => false,
            'is_critical' => true,
            'sort_order' => 2,
        ]);

        $itemsData = [
            [
                'id' => $item1->id,
                'label' => 'STNK Asli',
                'weight' => 3,
                'is_required' => false,
                'requires_photo' => false,
                'is_critical' => true,
                'sort_order' => 2,
            ],
            [
                'id' => $item2->id,
                'label' => 'BPKB Lengkap',
                'weight' => 4,
                'is_required' => true,
                'requires_photo' => true,
                'is_critical' => false,
                'sort_order' => 1,
            ],
        ];

        $newTemplate = $this->action->handle($template, $itemsData);

        // New template checks
        $this->assertNotSame($template->id, $newTemplate->id);
        $this->assertSame(2, $newTemplate->version);
        $this->assertTrue($newTemplate->is_active);
        $this->assertSame('Template Mobil', $newTemplate->name);
        $this->assertSame(VehicleType::Car->value, $newTemplate->vehicle_type);
        $this->assertCount(2, $newTemplate->items);

        // Old template checks
        $template->refresh();
        $this->assertSame(1, $template->version);
        $this->assertFalse($template->is_active);

        // Old items are unchanged
        $item1->refresh();
        $this->assertSame('STNK', $item1->label);
        $this->assertSame(1, $item1->weight);
        $this->assertTrue($item1->is_required);
        $this->assertTrue($item1->requires_photo);
        $this->assertFalse($item1->is_critical);
        $this->assertSame(1, $item1->sort_order);

        $item2->refresh();
        $this->assertSame('BPKB', $item2->label);
        $this->assertSame(2, $item2->weight);
        $this->assertTrue($item2->is_required);
        $this->assertFalse($item2->requires_photo);
        $this->assertTrue($item2->is_critical);
        $this->assertSame(2, $item2->sort_order);

        // New items have updated values and distinct IDs
        $newItem1 = $newTemplate->items()->where('label', 'STNK Asli')->first();
        $this->assertNotNull($newItem1);
        $this->assertNotSame($item1->id, $newItem1->id);
        $this->assertSame('Dokumen', $newItem1->category);
        $this->assertSame(3, $newItem1->weight);
        $this->assertFalse($newItem1->is_required);
        $this->assertFalse($newItem1->requires_photo);
        $this->assertTrue($newItem1->is_critical);
        $this->assertSame(2, $newItem1->sort_order);

        $newItem2 = $newTemplate->items()->where('label', 'BPKB Lengkap')->first();
        $this->assertNotNull($newItem2);
        $this->assertNotSame($item2->id, $newItem2->id);
        $this->assertSame('Dokumen', $newItem2->category);
        $this->assertSame(4, $newItem2->weight);
        $this->assertTrue($newItem2->is_required);
        $this->assertTrue($newItem2->requires_photo);
        $this->assertFalse($newItem2->is_critical);
        $this->assertSame(1, $newItem2->sort_order);
    }

    public function test_adm_t12_increments_version_based_on_highest_version_for_vehicle_type(): void
    {
        $v1 = InspectionTemplate::factory()->car()->inactive()->create([
            'version' => 1,
        ]);
        $v2 = InspectionTemplate::factory()->car()->create([
            'version' => 2,
            'is_active' => true,
        ]);

        $item = TemplateItem::factory()->create([
            'template_id' => $v1->id,
            'label' => 'Item v1',
        ]);

        $itemsData = [
            [
                'id' => $item->id,
                'label' => 'Item v3',
                'weight' => $item->weight,
                'is_required' => $item->is_required,
                'requires_photo' => $item->requires_photo,
                'is_critical' => $item->is_critical,
                'sort_order' => $item->sort_order,
            ],
        ];

        // Editing v1 when v2 already exists should produce v3
        $v3 = $this->action->handle($v1, $itemsData);

        $this->assertSame(3, $v3->version);
        $this->assertTrue($v3->is_active);

        $v2->refresh();
        $this->assertFalse($v2->is_active);

        $v1->refresh();
        $this->assertFalse($v1->is_active);
    }

    public function test_adm_t12_two_consecutive_saves_from_same_base_template_produce_distinct_versions(): void
    {
        $template = InspectionTemplate::factory()->car()->create([
            'version' => 1,
            'is_active' => true,
        ]);

        $item = TemplateItem::factory()->create([
            'template_id' => $template->id,
            'label' => 'Item Base',
        ]);

        $itemsData = [
            [
                'id' => $item->id,
                'label' => 'Item Edited',
                'weight' => $item->weight,
                'is_required' => $item->is_required,
                'requires_photo' => $item->requires_photo,
                'is_critical' => $item->is_critical,
                'sort_order' => $item->sort_order,
            ],
        ];

        $v2 = $this->action->handle($template, $itemsData);
        $this->assertSame(2, $v2->version);
        $this->assertTrue($v2->is_active);

        // Second save using the same base template instance
        $v3 = $this->action->handle($template, $itemsData);
        $this->assertSame(3, $v3->version);
        $this->assertTrue($v3->is_active);

        $v2->refresh();
        $this->assertFalse($v2->is_active);

        $template->refresh();
        $this->assertFalse($template->is_active);

        $versions = InspectionTemplate::where('vehicle_type', VehicleType::Car->value)
            ->pluck('version')
            ->sort()
            ->values()
            ->all();

        $this->assertSame([1, 2, 3], $versions);
    }
}
