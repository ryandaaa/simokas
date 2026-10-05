<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Exceptions\InvalidInspectorProfileException;
use App\Domains\Inspector\Exceptions\InvalidVerificationStatusTransitionException;
use App\Domains\Inspector\Models\InspectorProfile;
use Illuminate\Support\Facades\DB;

final class ResubmitInspector
{
    public function handle(User $user, InspectorProfile $profile): InspectorProfile
    {
        if ($user->id !== $profile->user_id && ! $adminCheck = $user->isAdmin()) {
            throw InvalidInspectorProfileException::notAnInspector();
        }

        if (! $profile->verification_status->canTransitionTo(VerificationStatus::Pending)) {
            throw InvalidVerificationStatusTransitionException::fromStatus(
                $profile->verification_status,
                VerificationStatus::Pending
            );
        }

        return DB::transaction(function () use ($profile): InspectorProfile {
            /** @var array<int, array{reason: string, recorded_at: string}> $history */
            $history = $profile->rejection_history ?? [];

            if (! empty($profile->rejection_reason)) {
                $history[] = [
                    'reason' => $profile->rejection_reason,
                    'recorded_at' => now()->toIso8601String(),
                ];
            }

            $affected = InspectorProfile::query()
                ->whereKey($profile->getKey())
                ->where('verification_status', VerificationStatus::Rejected->value)
                ->update([
                    'verification_status' => VerificationStatus::Pending->value,
                    'rejection_reason' => null,
                    'rejection_history' => json_encode($history),
                    'updated_at' => now(),
                ]);

            if ($affected === 0) {
                /** @var InspectorProfile $fresh */
                $fresh = InspectorProfile::query()->findOrFail($profile->getKey());

                throw InvalidVerificationStatusTransitionException::cannotResubmit($fresh->verification_status);
            }

            $profile->refresh();

            return $profile;
        });
    }
}
