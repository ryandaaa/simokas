<?php

declare(strict_types=1);

namespace App\Domains\Result\Models;

use App\Domains\Identity\Models\User;
use App\Domains\Payment\Models\Payment;
use App\Domains\Result\Database\factories\ResultAccessFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $result_id
 * @property string $user_id
 * @property string|null $payment_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read InspectionResult $result
 * @property-read User $user
 * @property-read Payment|null $payment
 */
final class ResultAccess extends Model
{
    /** @use HasFactory<ResultAccessFactory> */
    use HasFactory, HasUuids;

    protected $table = 'result_accesses';

    protected $fillable = [
        'result_id',
        'user_id',
        'payment_id',
    ];

    /**
     * @return BelongsTo<InspectionResult, $this>
     */
    public function result(): BelongsTo
    {
        return $this->belongsTo(InspectionResult::class, 'result_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }
}
