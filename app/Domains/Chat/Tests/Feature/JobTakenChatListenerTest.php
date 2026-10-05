<?php

declare(strict_types=1);

use App\Domains\Chat\Enums\ConversationContext;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Events\JobTaken;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(RefreshDatabase::class);

it('CHT-T13 creates job conversation upon JobTaken with inspector and seller', function () {
    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $inspector = User::factory()->create(['role' => UserRole::Inspector, 'status' => UserStatus::Active]);

    $listing = Listing::factory()->create(['seller_id' => $seller->id]);
    $job = InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'inspector_id' => $inspector->id,
    ]);

    // Dispatch the JobTaken event
    event(new JobTaken(
        jobId: $job->id,
        listingId: $listing->id,
        inspectorId: $inspector->id,
    ));

    // Verify conversation was created
    $conversation = Conversation::query()
        ->where('context_type', ConversationContext::Job)
        ->where('context_id', $job->id)
        ->first();

    expect($conversation)->not->toBeNull()
        ->and($conversation->hasMember($inspector->id))->toBeTrue()
        ->and($conversation->hasMember($seller->id))->toBeTrue()
        ->and($conversation->members()->count())->toBe(2);

    expect(Conversation::query()->count())->toBe(1);

    // Firing JobTaken again does not duplicate the conversation
    event(new JobTaken(
        jobId: $job->id,
        listingId: $listing->id,
        inspectorId: $inspector->id,
    ));

    expect(Conversation::query()->count())->toBe(1);
});
