<?php

declare(strict_types=1);

namespace App\Domains\Result\Database\factories;

use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Result\Models\InspectionTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InspectionTemplate>
 */
class InspectionTemplateFactory extends Factory
{
    protected $model = InspectionTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vehicle_type' => VehicleType::Car->value,
            'name' => 'Template Inspeksi Mobil v1',
            'version' => 1,
            'is_active' => true,
        ];
    }

    public function car(): static
    {
        return $this->state(fn () => [
            'vehicle_type' => VehicleType::Car->value,
            'name' => 'Template Inspeksi Mobil v1',
        ]);
    }

    public function motorcycle(): static
    {
        return $this->state(fn () => [
            'vehicle_type' => VehicleType::Motorcycle->value,
            'name' => 'Template Inspeksi Motor v1',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    /**
     * Use firstOrCreate pattern to avoid unique constraint violations
     * when multiple records share the same vehicle_type and version.
     */
    public function firstOrCreate(): InspectionTemplate
    {
        $attributes = $this->definition();

        return InspectionTemplate::query()->firstOrCreate(
            [
                'vehicle_type' => $attributes['vehicle_type'],
                'version' => $attributes['version'],
            ],
            $attributes
        );
    }
}
