<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Enums\VehicleType;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Events\InspectorSubmitted;
use App\Domains\Inspector\Exceptions\InvalidInspectorProfileException;
use App\Domains\Inspector\Models\InspectorProfile;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;

final class CreateInspectorProfile
{
    public function __construct(private readonly Dispatcher $events) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, array $data): InspectorProfile
    {
        if (! $user->isInspector()) {
            throw InvalidInspectorProfileException::notAnInspector();
        }

        $rawVehicleTypes = $data['vehicle_types'] ?? [];
        if (! is_array($rawVehicleTypes) || count($rawVehicleTypes) === 0) {
            throw InvalidInspectorProfileException::invalidVehicleTypes();
        }

        $validVehicleTypes = [];
        foreach ($rawVehicleTypes as $vt) {
            $value = $vt instanceof VehicleType ? $vt->value : (string) $vt;
            if (VehicleType::tryFrom($value) !== null && ! in_array($value, $validVehicleTypes, true)) {
                $validVehicleTypes[] = $value;
            }
        }

        if (count($validVehicleTypes) === 0) {
            throw InvalidInspectorProfileException::invalidVehicleTypes();
        }

        $experience = isset($data['experience_years']) ? (int) $data['experience_years'] : -1;
        if ($experience < 0 || $experience > 50) {
            throw InvalidInspectorProfileException::invalidExperienceYears();
        }

        return DB::transaction(function () use ($user, $validVehicleTypes, $experience): InspectorProfile {
            /** @var InspectorProfile $profile */
            $profile = InspectorProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'vehicle_types' => $validVehicleTypes,
                    'experience_years' => $experience,
                    'verification_status' => VerificationStatus::Pending,
                ]
            );

            $this->events->dispatch(new InspectorSubmitted(
                inspectorProfileId: $profile->id,
                userId: $profile->user_id,
            ));

            return $profile;
        });
    }
}
