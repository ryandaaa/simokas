<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspection\Services\InspectionJobAccessService;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('allows only the taking inspector of an active job to view private listing data', function (JobStatus $status, bool $expected) {
    $inspector = User::factory()->inspector()->create();
    $listing = Listing::factory()->active()->create();

    InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'inspector_id' => $inspector->id,
        'status' => $status,
    ]);

    $service = new InspectionJobAccessService;
    expect($service->canViewPrivate($inspector, $listing))->toBe($expected);
})->with([
    'taken job' => [JobStatus::Taken, true],
    'scheduled job' => [JobStatus::Scheduled, true],
    'in progress job' => [JobStatus::InProgress, true],
    'result submitted job' => [JobStatus::ResultSubmitted, true],
    'completed job' => [JobStatus::Completed, false],
    'cancelled job' => [JobStatus::Cancelled, false],
    'expired job' => [JobStatus::Expired, false],
]);

it('denies access to non-inspectors, blocked inspectors, other inspectors, and null viewers', function (string $case) {
    $inspector = User::factory()->inspector()->create();
    $otherInspector = User::factory()->inspector()->create();
    $user = User::factory()->user()->create();
    $admin = User::factory()->admin()->create();
    $blockedInspector = tap(User::factory()->inspector()->create(), fn ($u) => $u->update(['status' => 'blocked']));

    $listing = Listing::factory()->active()->create();
    InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'inspector_id' => $inspector->id,
        'status' => JobStatus::Taken,
    ]);

    $viewer = match ($case) {
        'null' => null,
        'other_inspector' => $otherInspector,
        'user' => $user,
        'admin' => $admin,
        'blocked_inspector' => $blockedInspector,
        default => throw new \InvalidArgumentException("Unknown case: {$case}"),
    };

    $service = new InspectionJobAccessService;
    expect($service->canViewPrivate($viewer, $listing))->toBeFalse();
})->with([
    'null viewer' => 'null',
    'other inspector' => 'other_inspector',
    'regular user' => 'user',
    'admin' => 'admin',
    'blocked inspector' => 'blocked_inspector',
]);
