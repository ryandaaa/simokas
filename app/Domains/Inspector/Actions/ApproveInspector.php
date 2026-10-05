<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Enums\DocumentType;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Events\InspectorApproved;
use App\Domains\Inspector\Exceptions\IncompleteInspectorDocumentsException;
use App\Domains\Inspector\Exceptions\InvalidInspectorProfileException;
use App\Domains\Inspector\Exceptions\InvalidVerificationStatusTransitionException;
use App\Domains\Inspector\Models\InspectorProfile;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;

final class ApproveInspector
{
    public function __construct(private readonly Dispatcher $events) {}

    public function handle(User $admin, InspectorProfile $profile): InspectorProfile
    {
        if (! $admin->isAdmin()) {
            throw InvalidInspectorProfileException::notAnInspector();
        }

        if (! $profile->hasCompleteDocuments()) {
            $missing = array_map(
                fn (DocumentType $t): string => $t->label(),
                $profile->missingDocumentTypes()
            );

            throw IncompleteInspectorDocumentsException::withMissing($missing);
        }

        if ($profile->verification_status !== VerificationStatus::Pending) {
            throw InvalidVerificationStatusTransitionException::alreadyProcessed($profile->verification_status);
        }

        return DB::transaction(function () use ($admin, $profile): InspectorProfile {
            $affected = InspectorProfile::query()
                ->whereKey($profile->getKey())
                ->where('verification_status', VerificationStatus::Pending->value)
                ->update([
                    'verification_status' => VerificationStatus::Approved->value,
                    'verified_by' => $admin->id,
                    'verified_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($affected === 0) {
                /** @var InspectorProfile $fresh */
                $fresh = InspectorProfile::query()->findOrFail($profile->getKey());

                throw InvalidVerificationStatusTransitionException::alreadyProcessed($fresh->verification_status);
            }

            $profile->refresh();

            $this->events->dispatch(new InspectorApproved(
                inspectorProfileId: $profile->id,
                userId: $profile->user_id,
                verifiedBy: $admin->id,
            ));

            return $profile;
        });
    }
}
