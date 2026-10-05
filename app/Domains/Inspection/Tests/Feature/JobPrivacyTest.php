<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Feature;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Actions\TakeJob;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\ListingPrivacyHelper;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

function createPrivacyInspector(array $specialties = ['car', 'motorcycle']): User
{
    $user = User::factory()->create([
        'role' => UserRole::Inspector,
        'status' => UserStatus::Active,
    ]);

    InspectorProfile::factory()->create([
        'user_id' => $user->id,
        'verification_status' => VerificationStatus::Approved,
        'vehicle_types' => $specialties,
    ]);

    return $user;
}

it('JOB-T06 exact address and chassis number do not appear in open jobs list or client data', function () {
    $inspector = createPrivacyInspector();

    $exactAddress = 'Jl. Terpencil No. 777 RT01/RW02';
    $exactChassis = 'CHASSIS-SECRET-12345';
    $exactLat = '0.5071234';
    $exactLng = '101.4478123';
    $exactPlace = 'Garasi Pribadi Belakang Rumah';

    $listing = Listing::factory()->active()->create([
        'vehicle_type' => VehicleType::Car,
        'address_full' => $exactAddress,
        'chassis_number' => $exactChassis,
        'lat' => $exactLat,
        'lng' => $exactLng,
        'inspection_place' => $exactPlace,
        'brand' => 'Toyota',
        'model' => 'Avanza',
        'year' => 2020,
    ]);

    $job = InspectionJob::factory()->paid()->create([
        'listing_id' => $listing->id,
        'fee_amount' => 50000,
    ]);

    $response = actingAs($inspector)->get(route('jobs.index'));

    $response->assertOk()
        ->assertSee('Toyota Avanza')
        ->assertDontSee($exactAddress)
        ->assertDontSee($exactChassis)
        ->assertDontSee($exactPlace)
        ->assertDontSee($exactLat)
        ->assertDontSee($exactLng);

    ListingPrivacyHelper::assertNoPrivateListingData($response, [
        $exactAddress,
        $exactChassis,
        $exactPlace,
        $exactLat,
        $exactLng,
    ]);
});

it('JOB-T06 exact address and chassis number are forbidden on detail before taken', function () {
    $inspector = createPrivacyInspector();
    $requester = User::factory()->user()->create();

    $listing = Listing::factory()->active()->create([
        'vehicle_type' => VehicleType::Car,
        'address_full' => 'Jl. Belum Diambil No. 10',
        'chassis_number' => 'CHASSIS-NOTYET-999',
    ]);

    $job = InspectionJob::factory()->paid()->create([
        'listing_id' => $listing->id,
        'requester_id' => $requester->id,
        'inspector_id' => null,
    ]);

    // Inspector before taking the job cannot access detail -> 403
    actingAs($inspector)->get(route('jobs.show', ['job' => $job->id]))
        ->assertForbidden();

    // Requester cannot access inspector detail page -> 403
    actingAs($requester)->get(route('jobs.show', ['job' => $job->id]))
        ->assertForbidden();
});

it('JOB-T06 exact address and chassis number appear after taken for taking inspector, but NOT for other inspectors or requester', function () {
    $takingInspector = createPrivacyInspector();
    $otherInspector = createPrivacyInspector();
    $requester = User::factory()->user()->create();
    $otherUser = User::factory()->user()->create();

    $exactAddress = 'Jl. Hangtuah No. 42 Pekanbaru';
    $exactChassis = 'MHKA-CONFIDENTIAL-8888';
    $exactPlace = 'seller_home';
    $exactLat = '0.5123456';
    $exactLng = '101.4567890';

    $listing = Listing::factory()->active()->create([
        'vehicle_type' => VehicleType::Car,
        'address_full' => $exactAddress,
        'chassis_number' => $exactChassis,
        'inspection_place' => $exactPlace,
        'lat' => $exactLat,
        'lng' => $exactLng,
    ]);

    $job = InspectionJob::factory()->paid()->create([
        'listing_id' => $listing->id,
        'requester_id' => $requester->id,
    ]);

    // Taking the job
    app(TakeJob::class)->handle($takingInspector, $job);

    // 1. Taking inspector opens detail -> 200 OK and sees all exact details
    $responseTaking = actingAs($takingInspector)->get(route('jobs.show', ['job' => $job->id]));
    $responseTaking->assertOk()
        ->assertSee($exactAddress)
        ->assertSee($exactChassis)
        ->assertSee($exactPlace)
        ->assertSee($exactLat)
        ->assertSee($exactLng);

    // 2. Other inspector opens detail -> 403 Forbidden
    actingAs($otherInspector)->get(route('jobs.show', ['job' => $job->id]))
        ->assertForbidden();

    // 3. Requester opens detail -> 403 Forbidden
    actingAs($requester)->get(route('jobs.show', ['job' => $job->id]))
        ->assertForbidden();

    // 4. Other user opens detail -> 403 Forbidden
    actingAs($otherUser)->get(route('jobs.show', ['job' => $job->id]))
        ->assertForbidden();
});

it('JOB-T06 Catalog detail displays private data for taking inspector and conceals it from other inspectors via InspectionJobAccess', function () {
    $takingInspector = createPrivacyInspector();
    $otherInspector = createPrivacyInspector();

    $exactAddress = 'Jl. Private Catalog Access No. 99';
    $exactChassis = 'VIN-CATALOG-PRIVATE-001';
    $exactPlace = 'seller_office';

    $listing = Listing::factory()->inInspection()->create([
        'vehicle_type' => VehicleType::Car,
        'address_full' => $exactAddress,
        'chassis_number' => $exactChassis,
        'inspection_place' => $exactPlace,
    ]);

    $job = InspectionJob::factory()->paid()->create([
        'listing_id' => $listing->id,
    ]);

    // Take job with $takingInspector
    app(TakeJob::class)->handle($takingInspector, $job);

    // Taking inspector on catalog detail page sees private data
    $responseTaking = actingAs($takingInspector)->get(route('catalog.show', ['listing' => $listing->id]));
    $responseTaking->assertOk()
        ->assertSee($exactAddress)
        ->assertSee($exactChassis)
        ->assertSee($exactPlace);

    // Other inspector on catalog detail page does NOT see private data
    $responseOther = actingAs($otherInspector)->get(route('catalog.show', ['listing' => $listing->id]));
    $responseOther->assertOk()
        ->assertDontSee($exactAddress)
        ->assertDontSee($exactChassis)
        ->assertDontSee($exactPlace);

    ListingPrivacyHelper::assertNoPrivateListingData($responseOther, [
        $exactAddress,
        $exactChassis,
        $exactPlace,
    ]);
});
