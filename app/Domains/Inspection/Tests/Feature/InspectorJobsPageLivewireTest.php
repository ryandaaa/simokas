<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Feature;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Livewire\InspectorJobsPage;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

function createTestInspector(): User
{
    $user = User::factory()->create([
        'role' => UserRole::Inspector,
        'status' => UserStatus::Active,
    ]);

    InspectorProfile::factory()->create([
        'user_id' => $user->id,
        'verification_status' => VerificationStatus::Approved,
        'vehicle_types' => ['car', 'motorcycle'],
    ]);

    return $user;
}

it('JOB-T18 restricts /inspector/jobs access by role', function (string $roleType) {
    $actingUser = match ($roleType) {
        'guest' => null,
        'user' => User::factory()->user()->create(),
        'admin' => User::factory()->admin()->create(),
        'unverified_inspector' => tap(User::factory()->inspector()->create(['status' => 'pending']), function ($u) {
            InspectorProfile::factory()->create([
                'user_id' => $u->id,
                'verification_status' => VerificationStatus::Pending,
            ]);
        }),
        'approved_inspector' => createTestInspector(),
        default => throw new \InvalidArgumentException("Unknown role type: {$roleType}"),
    };

    $request = $actingUser !== null ? actingAs($actingUser) : test();
    $response = get(route('jobs.inspector'));

    if ($roleType === 'guest') {
        $response->assertRedirect(route('login'));
    } elseif ($roleType === 'approved_inspector') {
        $response->assertOk();
    } else {
        $response->assertForbidden();
    }
})->with([
    'guest' => 'guest',
    'regular user' => 'user',
    'admin' => 'admin',
    'unverified inspector' => 'unverified_inspector',
    'approved inspector' => 'approved_inspector',
]);

it('renders assigned jobs for current inspector and excludes other inspectors jobs', function () {
    $inspector = createTestInspector();
    $otherInspector = createTestInspector();

    $myListing = Listing::factory()->active()->create([
        'brand' => 'Mitsubishi',
        'model' => 'Pajero',
        'year' => 2022,
        'vehicle_type' => VehicleType::Car,
    ]);
    $myJob = InspectionJob::factory()->create([
        'listing_id' => $myListing->id,
        'inspector_id' => $inspector->id,
        'status' => JobStatus::Taken,
        'fee_amount' => 50000,
    ]);

    $otherListing = Listing::factory()->active()->create([
        'brand' => 'Daihatsu',
        'model' => 'Xenia',
    ]);
    InspectionJob::factory()->create([
        'listing_id' => $otherListing->id,
        'inspector_id' => $otherInspector->id,
        'status' => JobStatus::Taken,
    ]);

    actingAs($inspector);

    Livewire::test(InspectorJobsPage::class)
        ->assertSee('Mitsubishi Pajero')
        ->assertSee('2022')
        ->assertSee(route('jobs.show', ['job' => $myJob->id]))
        ->assertDontSee('Daihatsu Xenia');
});

it('renders empty state when inspector has no taken jobs', function () {
    $inspector = createTestInspector();
    actingAs($inspector);

    Livewire::test(InspectorJobsPage::class)
        ->assertSee(__('inspection.inspector_jobs.empty_title'))
        ->assertSee(route('jobs.index'));
});
