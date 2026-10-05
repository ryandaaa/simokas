<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Unit;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Actions\BrowseOpenJobs;
use App\Domains\Inspection\DTOs\OpenJobCard;
use App\Domains\Inspection\DTOs\OpenJobFilter;
use App\Domains\Inspection\Exceptions\InspectorNotVerifiedException;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\ListingPrivacyHelper;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('rejects unverified inspectors from browsing open jobs', function () {
    $inspector = User::factory()->create([
        'role' => UserRole::Inspector,
        'status' => UserStatus::Pending,
    ]);
    InspectorProfile::factory()->create([
        'user_id' => $inspector->id,
        'verification_status' => VerificationStatus::Pending,
    ]);

    $action = app(BrowseOpenJobs::class);
    expect(fn () => $action->handle($inspector, new OpenJobFilter))
        ->toThrow(InspectorNotVerifiedException::class);
});

it('JOB-E5 excludes jobs on listings owned by the inspector themselves', function () {
    $inspector = User::factory()->create([
        'role' => UserRole::Inspector,
        'status' => UserStatus::Active,
    ]);
    InspectorProfile::factory()->create([
        'user_id' => $inspector->id,
        'verification_status' => VerificationStatus::Approved,
        'vehicle_types' => ['car', 'motorcycle'],
    ]);

    // Own listing job
    $ownListing = Listing::factory()->active()->create([
        'seller_id' => $inspector->id,
        'vehicle_type' => VehicleType::Car,
    ]);
    InspectionJob::factory()->paid()->create(['listing_id' => $ownListing->id]);

    // Other seller listing job
    $otherListing = Listing::factory()->active()->create([
        'vehicle_type' => VehicleType::Car,
    ]);
    $otherJob = InspectionJob::factory()->paid()->create(['listing_id' => $otherListing->id]);

    $action = app(BrowseOpenJobs::class);
    $results = $action->handle($inspector, new OpenJobFilter);

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->id)->toBe((string) $otherJob->id);
});

it('ONB-R5 filters jobs according to inspector allowed specialties', function () {
    $carInspector = User::factory()->create(['role' => UserRole::Inspector, 'status' => UserStatus::Active]);
    InspectorProfile::factory()->create([
        'user_id' => $carInspector->id,
        'verification_status' => VerificationStatus::Approved,
        'vehicle_types' => ['car'],
    ]);

    $carListing = Listing::factory()->active()->create(['vehicle_type' => VehicleType::Car]);
    $carJob = InspectionJob::factory()->paid()->create(['listing_id' => $carListing->id]);

    $motorListing = Listing::factory()->active()->create(['vehicle_type' => VehicleType::Motorcycle]);
    $motorJob = InspectionJob::factory()->paid()->create(['listing_id' => $motorListing->id]);

    $action = app(BrowseOpenJobs::class);
    $results = $action->handle($carInspector, new OpenJobFilter);

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->id)->toBe((string) $carJob->id);
});

it('filters open jobs by vehicle_type, area, and fee range', function () {
    $inspector = User::factory()->create(['role' => UserRole::Inspector, 'status' => UserStatus::Active]);
    InspectorProfile::factory()->create([
        'user_id' => $inspector->id,
        'verification_status' => VerificationStatus::Approved,
        'vehicle_types' => ['car', 'motorcycle'],
    ]);

    $l1 = Listing::factory()->active()->create(['vehicle_type' => VehicleType::Car, 'area_name' => 'Tampan']);
    InspectionJob::factory()->paid()->create(['listing_id' => $l1->id, 'fee_amount' => 50000]);

    $l2 = Listing::factory()->active()->create(['vehicle_type' => VehicleType::Motorcycle, 'area_name' => 'Sukajadi']);
    InspectionJob::factory()->paid()->create(['listing_id' => $l2->id, 'fee_amount' => 15000]);

    $l3 = Listing::factory()->active()->create(['vehicle_type' => VehicleType::Car, 'area_name' => 'Marpoyan']);
    InspectionJob::factory()->paid()->create(['listing_id' => $l3->id, 'fee_amount' => 60000]);

    $action = app(BrowseOpenJobs::class);

    // Filter by vehicle_type = 'car'
    $carResults = $action->handle($inspector, new OpenJobFilter(vehicleType: 'car'));
    expect($carResults->total())->toBe(2);

    // Filter by area = 'Tampan'
    $areaResults = $action->handle($inspector, new OpenJobFilter(area: 'Tampan'));
    expect($areaResults->total())->toBe(1);

    // Filter by fee range: min 55000
    $feeResults = $action->handle($inspector, new OpenJobFilter(feeMin: 55000));
    expect($feeResults->total())->toBe(1)
        ->and($feeResults->items()[0]->feeAmount)->toBe(60000);
});

it('sorts open jobs by latest and highest_fee', function () {
    $inspector = User::factory()->create(['role' => UserRole::Inspector, 'status' => UserStatus::Active]);
    InspectorProfile::factory()->create([
        'user_id' => $inspector->id,
        'verification_status' => VerificationStatus::Approved,
        'vehicle_types' => ['car', 'motorcycle'],
    ]);

    $l1 = Listing::factory()->active()->create();
    $job1 = InspectionJob::factory()->paid()->create([
        'listing_id' => $l1->id,
        'fee_amount' => 15000,
        'available_since' => now()->subHours(2),
    ]);

    $l2 = Listing::factory()->active()->create();
    $job2 = InspectionJob::factory()->paid()->create([
        'listing_id' => $l2->id,
        'fee_amount' => 50000,
        'available_since' => now()->subHour(),
    ]);

    $action = app(BrowseOpenJobs::class);

    // Highest fee sort
    $byFee = $action->handle($inspector, new OpenJobFilter(sort: OpenJobFilter::SORT_HIGHEST_FEE));
    expect($byFee->items()[0]->id)->toBe((string) $job2->id)
        ->and($byFee->items()[1]->id)->toBe((string) $job1->id);

    // Latest sort
    $byLatest = $action->handle($inspector, new OpenJobFilter(sort: OpenJobFilter::SORT_LATEST));
    expect($byLatest->items()[0]->id)->toBe((string) $job2->id)
        ->and($byLatest->items()[1]->id)->toBe((string) $job1->id);
});

it('returns OpenJobCard without any private listing columns', function () {
    $inspector = User::factory()->create(['role' => UserRole::Inspector, 'status' => UserStatus::Active]);
    InspectorProfile::factory()->create([
        'user_id' => $inspector->id,
        'verification_status' => VerificationStatus::Approved,
        'vehicle_types' => ['car'],
    ]);

    $listing = Listing::factory()->active()->create([
        'vehicle_type' => VehicleType::Car,
        'address_full' => 'Jl. Rahasia No. 99',
        'lat' => '0.5071000',
        'lng' => '101.4478000',
        'chassis_number' => 'MHKA1234567890123',
        'inspection_place' => 'seller_home',
    ]);
    InspectionJob::factory()->paid()->create(['listing_id' => $listing->id]);

    $action = app(BrowseOpenJobs::class);
    $results = $action->handle($inspector, new OpenJobFilter);

    /** @var OpenJobCard $card */
    $card = $results->items()[0];

    expect($card)->toBeInstanceOf(OpenJobCard::class);
    ListingPrivacyHelper::assertNoPrivateListingData(
        (string) json_encode($card),
        ['Jl. Rahasia No. 99', '0.5071000', '101.4478000', 'MHKA1234567890123', 'seller_home']
    );
});
