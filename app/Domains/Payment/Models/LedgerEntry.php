<?php

declare(strict_types=1);

namespace App\Domains\Payment\Models;

use App\Domains\Identity\Models\User;
use App\Domains\Payment\Database\factories\LedgerEntryFactory;
use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Payment\Exceptions\LedgerEntryImmutableException;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only ledger entry. Entries are never updated or deleted; a refund is
 * recorded as a new entry. The unique payment and entry type guard prevents
 * double posting at the database level.
 *
 * @property string $id
 * @property string $payment_id
 * @property string|null $account_user_id
 * @property LedgerEntryType $entry_type
 * @property int $amount
 * @property string|null $note
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Payment $payment
 * @property-read User|null $account
 */
#[Fillable([
    'id',
    'payment_id',
    'account_user_id',
    'entry_type',
    'amount',
    'note',
])]
final class LedgerEntry extends Model
{
    /** @use HasFactory<LedgerEntryFactory> */
    use HasFactory, HasUuids;

    protected $table = 'ledger_entries';

    protected static function newFactory(): LedgerEntryFactory
    {
        return LedgerEntryFactory::new();
    }

    protected static function booted(): void
    {
        self::updating(function (LedgerEntry $entry): void {
            throw LedgerEntryImmutableException::forUpdate($entry->id);
        });

        self::deleting(function (LedgerEntry $entry): void {
            throw LedgerEntryImmutableException::forDelete($entry->id);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entry_type' => LedgerEntryType::class,
            'amount' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_user_id');
    }
}
