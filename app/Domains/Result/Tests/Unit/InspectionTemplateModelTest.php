<?php

declare(strict_types=1);

namespace App\Domains\Result\Tests\Unit;

use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Result\Models\InspectionTemplate;
use App\Domains\Result\Models\TemplateItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class InspectionTemplateModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_template_and_items(): void
    {
        $template = InspectionTemplate::factory()->car()->create([
            'name' => 'Car Template v1',
            'version' => 1,
            'is_active' => true,
        ]);

        $item1 = TemplateItem::factory()->create([
            'template_id' => $template->id,
            'category' => 'Dokumen',
            'label' => 'STNK',
            'sort_order' => 1,
        ]);

        $item2 = TemplateItem::factory()->critical()->create([
            'template_id' => $template->id,
            'category' => 'Dokumen',
            'label' => 'Kecocokan nomor rangka dan mesin',
            'sort_order' => 2,
        ]);

        $this->assertCount(2, $template->items);
        $this->assertSame('Car Template v1', $template->name);
        $this->assertTrue($template->is_active);
        $this->assertSame(1, $template->version);
        $this->assertTrue($item2->is_critical);
        $this->assertTrue($item1->is_required);
    }

    public function test_it_scopes_active_and_vehicle_type(): void
    {
        InspectionTemplate::factory()->car()->create(['version' => 1, 'is_active' => true]);
        InspectionTemplate::factory()->car()->inactive()->create(['version' => 2]);
        InspectionTemplate::factory()->motorcycle()->create(['version' => 1, 'is_active' => true]);

        $activeTemplates = InspectionTemplate::query()->active()->get();
        $this->assertCount(2, $activeTemplates);

        $activeCarTemplates = InspectionTemplate::query()
            ->active()
            ->forVehicleType(VehicleType::Car)
            ->get();

        $this->assertCount(1, $activeCarTemplates);
        $this->assertSame('car', $activeCarTemplates->first()->vehicle_type);
    }
}
