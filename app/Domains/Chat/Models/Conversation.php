<?php

declare(strict_types=1);

namespace App\Domains\Chat\Models;

use App\Domains\Chat\Database\factories\ConversationFactory;
use App\Domains\Chat\Enums\ConversationContext;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Models\Listing;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property ConversationContext $context_type
 * @property string $context_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ConversationMember> $members
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Message> $messages
 * @property-read Message|null $latestMessage
 */
final class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory, HasUuids;

    protected $table = 'conversations';

    protected $guarded = [];

    protected static function newFactory(): ConversationFactory
    {
        return ConversationFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'context_type' => ConversationContext::class,
        ];
    }

    /**
     * @return HasMany<ConversationMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(ConversationMember::class, 'conversation_id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_members', 'conversation_id', 'user_id')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'conversation_id');
    }

    /**
     * @return HasOne<Message, $this>
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class, 'conversation_id')->latestOfMany();
    }

    public function hasMember(User|string $user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->members()->where('user_id', $userId)->exists();
    }

    public function getOtherMember(User|string $user): ?User
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->users->first(fn (User $u) => $u->id !== $userId)
            ?? $this->users()->where('users.id', '!=', $userId)->first();
    }

    public function unreadCountFor(User|string $user): int
    {
        $userId = $user instanceof User ? $user->id : $user;

        $member = $this->members()->where('user_id', $userId)->first();

        if (! $member) {
            return 0;
        }

        $query = $this->messages()
            ->where('sender_id', '!=', $userId);

        if ($member->last_read_at !== null) {
            $query->where('created_at', '>', $member->last_read_at);
        }

        return $query->count();
    }

    /**
     * Context summary safe for public / participant view (no private listing data).
     *
     * @return array{title: string, subtitle: ?string, price: ?int}
     */
    public function contextSummary(): array
    {
        if ($this->context_type === ConversationContext::Listing) {
            $listing = Listing::query()->find($this->context_id);
            if ($listing) {
                return [
                    'title' => $listing->title,
                    'subtitle' => $listing->year ? (string) $listing->year : null,
                    'price' => $listing->price,
                ];
            }
        }

        if ($this->context_type === ConversationContext::Job) {
            $job = InspectionJob::query()->find($this->context_id);
            if ($job) {
                $listing = Listing::query()->find($job->listing_id);

                return [
                    'title' => $listing ? 'Inspeksi: '.$listing->title : 'Job Inspeksi',
                    'subtitle' => 'Status: '.$job->status->label(),
                    'price' => null,
                ];
            }
        }

        return [
            'title' => 'Percakapan',
            'subtitle' => null,
            'price' => null,
        ];
    }
}
