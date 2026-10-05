<?php

declare(strict_types=1);

namespace App\Domains\Chat\Database\factories;

use App\Domains\Chat\Enums\ConversationContext;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Models\Listing;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'context_type' => ConversationContext::Listing,
            'context_id' => (string) Str::uuid(),
        ];
    }

    public function forListing(Listing|string $listing): static
    {
        $id = $listing instanceof Listing ? $listing->id : $listing;

        return $this->state(fn () => [
            'context_type' => ConversationContext::Listing,
            'context_id' => $id,
        ]);
    }

    public function forJob(InspectionJob|string $job): static
    {
        $id = $job instanceof InspectionJob ? $job->id : $job;

        return $this->state(fn () => [
            'context_type' => ConversationContext::Job,
            'context_id' => $id,
        ]);
    }

    public function withMembers(User $user1, User $user2): static
    {
        return $this->afterCreating(function (Conversation $conversation) use ($user1, $user2) {
            $conversation->members()->createMany([
                ['user_id' => $user1->id],
                ['user_id' => $user2->id],
            ]);
        });
    }
}
