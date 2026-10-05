<?php

declare(strict_types=1);

namespace App\Domains\Chat\Listeners;

use App\Domains\Chat\Actions\StartConversation;
use App\Domains\Chat\Exceptions\ChatWithSelfException;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Events\JobTaken;
use App\Domains\Listing\Models\Listing;
use Illuminate\Support\Facades\Log;

final class CreateJobConversation
{
    public function __construct(
        private readonly StartConversation $startConversation,
    ) {}

    /**
     * Handle the JobTaken event to create a job conversation between inspector and seller.
     * CHT-R2, CHT-T13
     */
    public function handle(JobTaken $event): void
    {
        $listing = Listing::query()->find($event->listingId);

        if (! $listing) {
            Log::warning('CreateJobConversation: Listing not found for JobTaken', ['listing_id' => $event->listingId]);

            return;
        }

        $inspector = User::query()->find($event->inspectorId);

        if (! $inspector) {
            Log::warning('CreateJobConversation: Inspector not found for JobTaken', ['inspector_id' => $event->inspectorId]);

            return;
        }

        try {
            $this->startConversation->forJobTaken(
                inspector: $inspector,
                jobId: $event->jobId,
                targetUserId: $listing->seller_id,
            );
        } catch (ChatWithSelfException $e) {
            Log::warning('CreateJobConversation: Cannot chat with self in job', [
                'job_id' => $event->jobId,
                'inspector_id' => $event->inspectorId,
                'seller_id' => $listing->seller_id,
            ]);
        }

    }
}
