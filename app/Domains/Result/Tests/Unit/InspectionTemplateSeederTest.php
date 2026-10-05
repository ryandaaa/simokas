<?php

declare(strict_types=1);

namespace App\Domains\Result\Tests\Unit;

use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Result\Database\seeders\InspectionTemplateSeeder;
use App\Domains\Result\Models\InspectionTemplate;
use App\Domains\Result\Models\TemplateItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class InspectionTemplateSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_car_and_motorcycle_templates_with_distinct_items(): void
    {
        $this->seed(InspectionTemplateSeeder::class);

        $carTemplate = InspectionTemplate::query()
            ->active()
            ->forVehicleType(VehicleType::Car)
            ->first();

        $this->assertNotNull($carTemplate);
        $this->assertSame(1, $carTemplate->version);
        $this->assertCount(29, $carTemplate->items);

        $carCategories = $carTemplate->items->pluck('category')->unique()->values()->toArray();
        $this->assertContains('Interior', $carCategories);
        $this->assertContains('Test drive', $carCategories);
        $this->assertNotContains('Jok dan panel', $carCategories);
        $this->assertNotContains('Test ride', $carCategories);

        $carLabels = $carTemplate->items->pluck('label')->toArray();
        $this->assertContains('AC', $carLabels);

        $motorTemplate = InspectionTemplate::query()
            ->active()
            ->forVehicleType(VehicleType::Motorcycle)
            ->first();

        $this->assertNotNull($motorTemplate);
        $this->assertSame(1, $motorTemplate->version);
        $this->assertCount(27, $motorTemplate->items);

        $motorCategories = $motorTemplate->items->pluck('category')->unique()->values()->toArray();
        $this->assertContains('Jok dan panel', $motorCategories);
        $this->assertContains('Test ride', $motorCategories);
        $this->assertNotContains('Interior', $motorCategories);
        $this->assertNotContains('Test drive', $motorCategories);

        $motorLabels = $motorTemplate->items->pluck('label')->toArray();
        $this->assertNotContains('AC', $motorLabels);
        $this->assertContains('Starter elektrik', $motorLabels);
    }

    public function test_it_is_idempotent_when_run_multiple_times(): void
    {
        $this->seed(InspectionTemplateSeeder::class);
        $this->seed(InspectionTemplateSeeder::class);

        $this->assertSame(2, InspectionTemplate::query()->count());
        $this->assertSame(56, TemplateItem::query()->count());
    }
}
