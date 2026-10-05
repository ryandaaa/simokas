<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Actions\RequestInspection;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Exceptions\ListingHasActiveJobException;
use App\Domains\Inspection\Exceptions\ListingNotActiveException;
use App\Domains\Inspection\Exceptions\RequestNotAllowedException;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Listing\Models\Listing;
use App\Domains\Payment\DTOs\BillData;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Setting\Contracts\SettingsReader;
use App\Domains\Setting\DTOs\SettingsData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('JOB-T01 creates a requested job with fee according to vehicle type', function (VehicleType $vehicleType, int $expectedFee) {
    $requester = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['vehicle_type' => $vehicleType]);

    /** @var RequestInspection $action */
    $action = app(RequestInspection::class);
    $result = $action->handle($requester, $listing);

    expect($result['reused'])->toBeFalse()
        ->and($result['job'])->toBeInstanceOf(InspectionJob::class)
        ->and($result['job']->status)->toBe(JobStatus::Requested)
        ->and($result['job']->fee_amount)->toBe($expectedFee)
        ->and($result['job']->platform_fee_pct)->toBe(10)
        ->and($result['job']->listing_id)->toBe($listing->id)
        ->and($result['job']->requester_id)->toBe($requester->id)
        ->and($result['job']->expires_at->diffInMinutes(now()))->toBeLessThanOrEqual(30)
        ->and($result['bill'])->toBeInstanceOf(BillData::class)
        ->and($result['bill']->amount)->toBe($expectedFee)
        ->and($result['bill']->refId)->toBe($result['job']->id)
        ->and($result['bill']->purpose)->toBe(PaymentPurpose::Inspection);
})->with([
    'car' => [VehicleType::Car, 50000],
    'motorcycle' => [VehicleType::Motorcycle, 15000],
]);

it('JOB-T02 allows requesting inspection only on active listings (dataset of non-active statuses)', function (ListingStatus $status) {
    $requester = User::factory()->user()->create();
    $listing = Listing::factory()->create(['status' => $status]);

    /** @var RequestInspection $action */
    $action = app(RequestInspection::class);

    expect(fn () => $action->handle($requester, $listing))
        ->toThrow(ListingNotActiveException::class);
})->with([
    [ListingStatus::PendingReview],
    [ListingStatus::Rejected],
    [ListingStatus::InInspection],
    [ListingStatus::InTransaction],
    [ListingStatus::Sold],
    [ListingStatus::Inactive],
]);

it('JOB-T17 rejects inspection requests by inspectors with 403', function () {
    $inspector = User::factory()->inspector()->create();
    $listing = Listing::factory()->active()->create();

    $action = app(RequestInspection::class);

    expect(fn () => $action->handle($inspector, $listing))
        ->toThrow(RequestNotAllowedException::class);
});

it('rejects inspection requests by admins', function () {
    $admin = User::factory()->admin()->create();
    $listing = Listing::factory()->active()->create();

    $action = app(RequestInspection::class);

    expect(fn () => $action->handle($admin, $listing))
        ->toThrow(RequestNotAllowedException::class);
});

it('rejects inspection requests by blocked or inactive users', function () {
    $blocked = User::factory()->user()->blocked()->create();
    $listing = Listing::factory()->active()->create();

    $action = app(RequestInspection::class);

    expect(fn () => $action->handle($blocked, $listing))
        ->toThrow(RequestNotAllowedException::class);
});

it('re-uses the existing job and bill on double submission or reload without duplicating', function () {
    $requester = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['vehicle_type' => VehicleType::Car]);

    /** @var RequestInspection $action */
    $action = app(RequestInspection::class);

    $first = $action->handle($requester, $listing);
    $second = $action->handle($requester, $listing);

    expect($first['reused'])->toBeFalse()
        ->and($second['reused'])->toBeTrue()
        ->and($second['job']->id)->toBe($first['job']->id)
        ->and($second['bill']->id)->toBe($first['bill']->id)
        ->and(InspectionJob::where('listing_id', $listing->id)->count())->toBe(1);
});

it('JOB-E2 throws ListingHasActiveJobException (409) when a second requester applies on the same listing', function () {
    $requester1 = User::factory()->user()->create();
    $requester2 = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create();

    /** @var RequestInspection $action */
    $action = app(RequestInspection::class);

    $action->handle($requester1, $listing);

    try {
        $action->handle($requester2, $listing);
        expect(false)->toBeTrue();
    } catch (ListingHasActiveJobException $e) {
        expect($e->statusCode())->toBe(409)
            ->and($e->errorCode())->toBe('LISTING_HAS_ACTIVE_JOB');
    }
});

it('JOB-T16, ADM-T06, PAY-T11 keeps old job and bill fee unchanged when admin changes settings afterwards', function () {
    $requester = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['vehicle_type' => VehicleType::Car]);

    /** @var RequestInspection $action */
    $action = app(RequestInspection::class);
    $result = $action->handle($requester, $listing);

    expect($result['job']->fee_amount)->toBe(50000);

    // Mock / change settings reader to return new fees
    $fakeSettings = new class implements SettingsReader
    {
        public function getInspectionFeeCar(): int
        {
            return 90000;
        }

        public function getInspectionFeeMotorcycle(): int
        {
            return 30000;
        }

        public function getPlatformFeePct(): int
        {
            return 15;
        }

        public function getSaleCommissionPct(): int
        {
            return 2;
        }

        public function getAutoAcceptDays(): int
        {
            return 3;
        }

        public function getPaidJobTimeoutDays(): int
        {
            return 7;
        }

        public function all(): SettingsData
        {
            return new SettingsData(
                inspectionFeeCar: 90000,
                inspectionFeeMotorcycle: 30000,
                platformFeePct: 15,
                saleCommissionPct: 2,
                autoAcceptDays: 3,
                paidJobTimeoutDays: 7,
                updatedBy: null,
                updatedByName: null,
                updatedAt: null,
            );
        }
    };
    app()->instance(SettingsReader::class, $fakeSettings);

    // Re-check existing job and bill
    $freshJob = $result['job']->fresh();
    expect($freshJob->fee_amount)->toBe(50000)
        ->and($freshJob->platform_fee_pct)->toBe(10);

    // Reusing the job for same requester still yields old fee bill
    $reused = app(RequestInspection::class)->handle($requester, $listing);
    expect($reused['reused'])->toBeTrue()
        ->and($reused['job']->fee_amount)->toBe(50000)
        ->and($reused['bill']->amount)->toBe(50000);
});
