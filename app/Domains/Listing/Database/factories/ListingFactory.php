<?php

declare(strict_types=1);

namespace App\Domains\Listing\Database\factories;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Enums\FuelType;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Enums\Transmission;
use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Listing\Models\Listing;
use App\Domains\Listing\Models\ListingInspectionSlot;
use App\Domains\Listing\Models\ListingPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Listing>
 */
class ListingFactory extends Factory
{
    protected $model = Listing::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'seller_id' => User::factory()->user(),
            'vehicle_type' => VehicleType::Car,
            'title' => fake()->randomElement(['Toyota Avanza 1.3 G 2018', 'Honda Brio Satya E 2020', 'Mitsubishi Xpander Ultimate 2019']),
            'brand' => 'Toyota',
            'model' => 'Avanza',
            'year' => fake()->numberBetween(2015, (int) date('Y')),
            'price' => fake()->numberBetween(50_000_000, 250_000_000),
            'negotiable' => true,
            'odometer_km' => fake()->numberBetween(10_000, 120_000),
            'transmission' => Transmission::Manual,
            'fuel' => FuelType::Petrol,
            'color' => 'Putih',
            'chassis_number' => 'MHKA1234567890123',
            'description' => 'Kondisi kendaraan sangat terawat, servis rutin di bengkel resmi, surat lengkap.',
            'area_name' => 'Tampan',
            'lat' => 0.4851234,
            'lng' => 101.4012345,
            'address_full' => 'Jl. HR. Soebrantas No. 12, Tampan, Pekanbaru',
            'inspection_place' => 'Garasi rumah penjual',
            'status' => ListingStatus::PendingReview,
            'rejection_reason' => null,
            'is_inspected' => false,
            'last_inspected_at' => null,
            'validated_by' => null,
            'validated_at' => null,
        ];
    }

    public function pendingReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ListingStatus::PendingReview,
            'rejection_reason' => null,
            'validated_by' => null,
            'validated_at' => null,
        ]);
    }

    public function active(?User $admin = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ListingStatus::Active,
            'rejection_reason' => null,
            'validated_by' => $admin !== null ? $admin->id : User::factory()->admin(),
            'validated_at' => now(),
        ]);
    }

    public function rejected(string $reason = 'Foto kurang jelas atau data tidak sesuai'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ListingStatus::Rejected,
            'rejection_reason' => $reason,
            'validated_by' => User::factory()->admin(),
            'validated_at' => now(),
        ]);
    }

    public function inInspection(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ListingStatus::InInspection,
        ]);
    }

    public function inTransaction(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ListingStatus::InTransaction,
        ]);
    }

    public function sold(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ListingStatus::Sold,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ListingStatus::Inactive,
        ]);
    }

    public function car(): static
    {
        return $this->state(fn (array $attributes) => [
            'vehicle_type' => VehicleType::Car,
            'brand' => 'Toyota',
            'model' => 'Avanza',
            'transmission' => Transmission::Manual,
            'fuel' => FuelType::Petrol,
        ]);
    }

    public function motorcycle(): static
    {
        return $this->state(fn (array $attributes) => [
            'vehicle_type' => VehicleType::Motorcycle,
            'title' => 'Honda Vario 150 CBS 2021',
            'brand' => 'Honda',
            'model' => 'Vario 150',
            'transmission' => Transmission::Automatic,
            'fuel' => FuelType::Petrol,
        ]);
    }

    public function inspected(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_inspected' => true,
            'last_inspected_at' => now(),
        ]);
    }

    public function withPhotos(int $count = 3): static
    {
        return $this->has(
            ListingPhoto::factory()
                ->count($count)
                ->sequence(fn ($sequence) => [
                    'sort_order' => $sequence->index,
                    'is_cover' => $sequence->index === 0,
                ]),
            'photos'
        );
    }

    public function withInspectionSlots(int $count = 1): static
    {
        return $this->has(
            ListingInspectionSlot::factory()->count($count),
            'inspectionSlots'
        );
    }
}
