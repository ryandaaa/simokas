<?php

declare(strict_types=1);

namespace App\Domains\Chat\Models;

use App\Domains\Chat\Database\factories\MessageFactory;
use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $conversation_id
 * @property string $sender_id
 * @property string $body
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Conversation $conversation
 * @property-read User $sender
 */
#[Fillable(['id', 'conversation_id', 'sender_id', 'body', 'created_at', 'updated_at'])]
final class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory, HasUuids;

    protected $table = 'messages';

    protected static function newFactory(): MessageFactory
    {
        return MessageFactory::new();
    }

    /**
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
