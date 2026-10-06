<?php

declare(strict_types=1);

namespace App\Domains\Result\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Payment\Contracts\Ledger;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\ResultAccess;
use Illuminate\Support\Facades\DB;

final class GrantResultAccess
{
    public function __construct(private readonly Ledger $ledger) {}

    /**
     * Grant access to inspection result for a user.
     * Idempotent: if access already exists, return existing access without posting ledger again.
     * If payment_id is provided, posts ledger entries for inspector and platform.
     *
     * @param  ?int  $platformFeePct  Percentage for platform, or null to use current setting.
     */
    public function handle(
        InspectionResult $result,
        User $user,
        ?string $paymentId = null,
        ?int $platformFeePct = null,
    ): ResultAccess {
        return DB::transaction(function () use ($result, $user, $paymentId, $platformFeePct) {
            // Check if access already exists
            $existingAccess = ResultAccess::query()
                ->where('result_id', $result->getKey())
                ->where('user_id', $user->getKey())
                ->first();

            if ($existingAccess instanceof ResultAccess) {
                return $existingAccess;
            }

            // Create new access
            $access = ResultAccess::create([
                'result_id' => $result->getKey(),
                'user_id' => $user->getKey(),
                'payment_id' => $paymentId,
            ]);

            // Post ledger if payment is provided
            if ($paymentId !== null) {
                $this->ledger->postAccessPayout($paymentId, $result->inspector_id, $platformFeePct);
            }

            return $access;
        });
    }
}
