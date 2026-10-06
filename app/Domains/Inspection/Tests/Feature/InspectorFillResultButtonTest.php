<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Feature;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Livewire\InspectorJobsPage;
use App\Domains\Inspection\Livewire\JobDetailPage;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function createFillButtonInspector(): User
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

function createFillButtonJob(User $inspector, JobStatus $status): InspectionJob
{
    $listing = Listing::factory()->active()->create([
        'brand' => 'Toyota',
        'model' => 'Avanza',
        'year' => 2018,
        'vehicle_type' => VehicleType::Car,
    ]);

    return InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'inspector_id' => $inspector->id,
        'status' => $status,
        'fee_amount' => 50000,
        'taken_at' => now()->subDay(),
        'scheduled_at' => now()->subHour(),
        'started_at' => now()->subMinutes(30),
    ]);
}

it('RES-T01 job detail shows the fill-result button to the assigned inspector while in progress', function () {
    $inspector = createFillButtonInspector();
    $job = createFillButtonJob($inspector, JobStatus::InProgress);

    actingAs($inspector);

    Livewire::test(JobDetailPage::class, ['job' => $job])
        ->assertSee(route('result.fill', ['job' => $job->id]))
        ->assertSee(__('inspection.detail.fill_button'));
});

it('RES-T01 job detail hides the fill-result button before the inspection starts', function (JobStatus $status) {
    $inspector = createFillButtonInspector();
    $job = createFillButtonJob($inspector, $status);

    actingAs($inspector);

    Livewire::test(JobDetailPage::class, ['job' => $job])
        ->assertDontSee(route('result.fill', ['job' => $job->id]));
})->with([
    'taken' => [JobStatus::Taken],
    'scheduled' => [JobStatus::Scheduled],
]);

it('RES-T01 job detail does not show the fill-result button to an admin viewer', function () {
    $inspector = createFillButtonInspector();
    $job = createFillButtonJob($inspector, JobStatus::InProgress);
    $admin = User::factory()->admin()->create();

    actingAs($admin);

    Livewire::test(JobDetailPage::class, ['job' => $job])
        ->assertDontSee(route('result.fill', ['job' => $job->id]));
});

it('RES-T01 job detail links to the submitted result after the inspector sends it', function (JobStatus $status) {
    $inspector = createFillButtonInspector();
    $job = createFillButtonJob($inspector, $status);

    actingAs($inspector);

    Livewire::test(JobDetailPage::class, ['job' => $job])
        ->assertSee(route('results.show', ['listing' => $job->listing_id]))
        ->assertSee(__('inspection.detail.view_result_button'))
        ->assertDontSee(route('result.fill', ['job' => $job->id]));
})->with([
    'result submitted' => [JobStatus::ResultSubmitted],
    'completed' => [JobStatus::Completed],
]);

it('RES-T01 my jobs list shows the fill-result button only for jobs in progress', function () {
    $inspector = createFillButtonInspector();
    $running = createFillButtonJob($inspector, JobStatus::InProgress);
    $waiting = createFillButtonJob($inspector, JobStatus::Scheduled);

    actingAs($inspector);

    Livewire::test(InspectorJobsPage::class)
        ->assertSee(route('result.fill', ['job' => $running->id]))
        ->assertDontSee(route('result.fill', ['job' => $waiting->id]));
});

it('RES-T01 my jobs list links to the result for submitted jobs', function () {
    $inspector = createFillButtonInspector();
    $job = createFillButtonJob($inspector, JobStatus::ResultSubmitted);

    actingAs($inspector);

    Livewire::test(InspectorJobsPage::class)
        ->assertSee(route('results.show', ['listing' => $job->listing_id]))
        ->assertSee(__('inspection.inspector_jobs.view_result'))
        ->assertDontSee(route('result.fill', ['job' => $job->id]));
});
