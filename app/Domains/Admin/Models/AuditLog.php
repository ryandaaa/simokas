<?php

declare(strict_types=1);

namespace App\Domains\Admin\Models;

use App\Domains\Identity\Models\User;
use BadMethodCallException;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Audit log for sensitive admin actions (PRD 18.18, ADM-R8).
 *
 * @property string $id
 * @property string|null $actor_id
 * @property string $action
 * @property string $entity
 * @property string|null $entity_id
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $actor
 */
final class AuditLog extends Model
{
    use HasUuids;

    protected $table = 'audit_logs';

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        self::updating(function (): void {
            throw new BadMethodCallException('Audit log entries are immutable.');
        });

        self::deleting(function (): void {
            throw new BadMethodCallException('Audit log entries cannot be deleted.');
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
