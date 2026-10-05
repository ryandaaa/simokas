<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Models;

use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Database\factories\InspectorDocumentFactory;
use App\Domains\Inspector\Enums\DocumentType;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * @property string $id
 * @property string $user_id
 * @property DocumentType $type
 * @property string|null $storage_path
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'id',
    'user_id',
    'type',
    'storage_path',
    'metadata',
])]
final class InspectorDocument extends Model
{
    /** @use HasFactory<InspectorDocumentFactory> */
    use HasFactory, HasUuids;

    protected static function newFactory(): InspectorDocumentFactory
    {
        return InspectorDocumentFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isStoredFile(): bool
    {
        return ! empty($this->storage_path);
    }

    public function isUrl(): bool
    {
        return ! empty($this->metadata['url'] ?? null);
    }

    public function getUrl(): ?string
    {
        if ($this->isUrl()) {
            return (string) ($this->metadata['url'] ?? '');
        }

        if ($this->isStoredFile()) {
            return $this->getTemporarySignedUrl();
        }

        return null;
    }

    public function getTemporarySignedUrl(DateTimeInterface|Carbon|int $expiration = 30): string
    {
        $validUntil = is_int($expiration) ? now()->addMinutes($expiration) : $expiration;

        return URL::temporarySignedRoute(
            'inspector.documents.view',
            $validUntil,
            ['document' => $this->id]
        );
    }
}
