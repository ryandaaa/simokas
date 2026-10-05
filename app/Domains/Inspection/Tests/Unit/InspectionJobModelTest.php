<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Models\Listing;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('casts attributes properly', function () {
    $job = InspectionJob::factory()->create([
        'fee_amount' => 50000,
        'platform_fee_pct' => 10,
        'status' => JobStatus::Requested,
    ]);

    expect($job->status)->toBe(JobStatus::Requested)
        ->and($job->fee_amount)->toBeInt()
        ->and($job->platform_fee_pct)->toBeInt()
        ->and($job->expires_at)->toBeInstanceOf(Carbon::class);
});

it('has relationships to listing, requester, and inspector', function () {
    $inspector = User::factory()->inspector()->create();
    $requester = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create();

    $job = InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'requester_id' => $requester->id,
        'inspector_id' => $inspector->id,
        'status' => JobStatus::Taken,
    ]);

    expect($job->listing->id)->toBe($listing->id)
        ->and($job->requester->id)->toBe($requester->id)
        ->and($job->inspector->id)->toBe($inspector->id);
});

it('JOB-T04 rejects inserting a second active job directly via query with QueryException', function () {
    $listing = Listing::factory()->active()->create();
    $requester1 = User::factory()->user()->create();
    $requester2 = User::factory()->user()->create();

    // Insert first active job directly via raw query
    DB::table('inspection_jobs')->insert([
        'id' => (string) Str::uuid(),
        'listing_id' => $listing->id,
        'requester_id' => $requester1->id,
        'inspector_id' => null,
        'status' => 'requested',
        'fee_amount' => 50000,
        'platform_fee_pct' => 10,
        'expires_at' => now()->addMinutes(30),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Insert second active job on the same listing directly via raw query
    expect(function () use ($listing, $requester2) {
        DB::table('inspection_jobs')->insert([
            'id' => (string) Str::uuid(),
            'listing_id' => $listing->id,
            'requester_id' => $requester2->id,
            'inspector_id' => null,
            'status' => 'paid',
            'fee_amount' => 50000,
            'platform_fee_pct' => 10,
            'expires_at' => now()->addMinutes(30),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    })->toThrow(QueryException::class);
});

it('allows multiple final jobs on the same listing to coexist with null active_listing_id', function () {
    $listing = Listing::factory()->active()->create();
    $requester = User::factory()->user()->create();

    $job1 = InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'requester_id' => $requester->id,
        'status' => JobStatus::Completed,
    ]);

    $job2 = InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'requester_id' => $requester->id,
        'status' => JobStatus::Cancelled,
    ]);

    $job3 = InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'requester_id' => $requester->id,
        'status' => JobStatus::Expired,
    ]);

    // An active job can now also be created for this listing
    $jobActive = InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'requester_id' => $requester->id,
        'status' => JobStatus::Requested,
    ]);

    expect(InspectionJob::where('listing_id', $listing->id)->count())->toBe(4)
        ->and(InspectionJob::where('listing_id', $listing->id)->active()->count())->toBe(1)
        ->and($job1->fresh()->active_listing_id)->toBeNull()
        ->and($job2->fresh()->active_listing_id)->toBeNull()
        ->and($job3->fresh()->active_listing_id)->toBeNull()
        ->and($jobActive->fresh()->active_listing_id)->toBe($listing->id);
});

it('filters active jobs via scopeActive', function () {
    $listing = Listing::factory()->active()->create();

    InspectionJob::factory()->create(['listing_id' => $listing->id, 'status' => JobStatus::Requested]);
    InspectionJob::factory()->create(['listing_id' => $listing->id, 'status' => JobStatus::Completed]);
    InspectionJob::factory()->create(['listing_id' => $listing->id, 'status' => JobStatus::Cancelled]);
    InspectionJob::factory()->create(['listing_id' => $listing->id, 'status' => JobStatus::Expired]);

    expect(InspectionJob::active()->count())->toBe(1);
});
