<?php

declare(strict_types=1);

namespace App\Domains\Identity\Tests\Unit;

use App\Domains\Identity\Actions\Moderation\BlockUser;
use App\Domains\Identity\Contracts\UserModerationContract;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Events\UserBlocked;
use App\Domains\Identity\Exceptions\CannotBlockSelfException;
use App\Domains\Identity\Exceptions\UserHasActiveItemsException;
use App\Domains\Identity\Exceptions\UserModerationException;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Contracts\InspectionJobModerationContract;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Models\Listing;
use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Payment\Enums\PaymentMethod;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use App\Domains\Transaction\Contracts\TransactionModerationContract;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Models\Transaction;
use App\Support\Exceptions\DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeAdmin(): User
{
    return User::factory()->admin()->active()->create();
}

function makeJobFor(User $user, JobStatus $status, array $overrides = []): InspectionJob
{
    return InspectionJob::factory()->create(array_merge([
        'requester_id' => $user->id,
        'status' => $status,
    ], $overrides));
}

function makePaidJobWithPayment(User $user): array
{
    $job = InspectionJob::factory()->paid()->create(['requester_id' => $user->id]);

    $payment = Payment::factory()->create([
        'payer_id' => $user->id,
        'purpose' => PaymentPurpose::Inspection,
        'ref_id' => $job->id,
        'amount' => 50000,
        'status' => PaymentStatus::Success,
        'method' => PaymentMethod::Qris,
    ]);

    return [$job, $payment];
}

function makePaidHeldTransactionWithPayment(User $buyer, User $seller): array
{
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

    $transaction = Transaction::factory()->paidHeld()->create([
        'listing_id' => $listing->id,
        'buyer_id' => $buyer->id,
    ]);

    $payment = Payment::factory()->create([
        'payer_id' => $buyer->id,
        'purpose' => PaymentPurpose::Purchase,
        'ref_id' => $transaction->id,
        'amount' => 100000000,
        'status' => PaymentStatus::Success,
        'method' => PaymentMethod::Qris,
    ]);

    $transaction->forceFill(['payment_id' => $payment->id])->save();

    return [$transaction, $payment, $listing];
}

describe('ADM-T10: block rejected with active items', function () {
    it('ADM-T10 rejects block when user has an active job as requester', function (JobStatus $status) {
        $admin = makeAdmin();
        $user = User::factory()->user()->active()->create();
        makeJobFor($user, $status);

        $action = app(BlockUser::class);

        try {
            $action->handle($admin, (string) $user->id, null, false);
            expect(false)->toBeTrue('expected UserHasActiveItemsException');
        } catch (UserHasActiveItemsException $e) {
            expect($e->getStatusCode())->toBe(409)
                ->and($e->getErrorCode())->toBe('USER_HAS_ACTIVE_ITEMS')
                ->and($e->items())->toHaveCount(1)
                ->and($e->items()[0]['type'])->toBe('job');
        }

        expect($user->fresh()->status)->toBe(UserStatus::Active);
    })->with([
        JobStatus::Requested,
        JobStatus::Paid,
        JobStatus::Taken,
        JobStatus::Scheduled,
        JobStatus::InProgress,
        JobStatus::ResultSubmitted,
    ]);

    it('ADM-T10 rejects block when user has an active job as inspector', function () {
        $admin = makeAdmin();
        $inspector = User::factory()->inspector()->active()->create();
        InspectionJob::factory()->taken($inspector)->create();

        $action = app(BlockUser::class);

        expect(fn () => $action->handle($admin, (string) $inspector->id, null, false))
            ->toThrow(UserHasActiveItemsException::class);

        expect($inspector->fresh()->status)->toBe(UserStatus::Active);
    });

    it('ADM-T10 rejects block when user has an active transaction as buyer', function (TransactionStatus $status) {
        $admin = makeAdmin();
        $buyer = User::factory()->user()->active()->create();
        Transaction::factory()->create([
            'buyer_id' => $buyer->id,
            'status' => $status,
            'expires_at' => now()->addDay(),
        ]);

        $action = app(BlockUser::class);

        try {
            $action->handle($admin, (string) $buyer->id, null, false);
            expect(false)->toBeTrue('expected UserHasActiveItemsException');
        } catch (UserHasActiveItemsException $e) {
            expect($e->items())->toHaveCount(1)
                ->and($e->items()[0]['type'])->toBe('transaction');
        }

        expect($buyer->fresh()->status)->toBe(UserStatus::Active);
    })->with([
        TransactionStatus::Offered,
        TransactionStatus::Accepted,
        TransactionStatus::PaidHeld,
    ]);

    it('ADM-T10 rejects block when user has an active transaction as seller', function () {
        $admin = makeAdmin();
        $seller = User::factory()->user()->active()->create();
        $buyer = User::factory()->user()->active()->create();
        $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);
        Transaction::factory()->accepted()->create([
            'listing_id' => $listing->id,
            'buyer_id' => $buyer->id,
        ]);

        $action = app(BlockUser::class);

        expect(fn () => $action->handle($admin, (string) $seller->id, null, false))
            ->toThrow(UserHasActiveItemsException::class);

        expect($seller->fresh()->status)->toBe(UserStatus::Active);
    });

    it('ADM-T10 rejects confirmed block without a reason', function () {
        $admin = makeAdmin();
        $user = User::factory()->user()->active()->create();
        makeJobFor($user, JobStatus::Requested);

        $action = app(BlockUser::class);

        expect(fn () => $action->handle($admin, (string) $user->id, null, true))
            ->toThrow(UserModerationException::class);

        expect($user->fresh()->status)->toBe(UserStatus::Active);
    });
});

describe('ADM-T10: block with confirmed cancellation', function () {
    it('ADM-T10 blocks with cancellation: items gone, one refund per payment, listings inactive', function () {
        $dispatched = 0;
        $lastEvent = null;
        Event::listen(UserBlocked::class, function (UserBlocked $event) use (&$dispatched, &$lastEvent): void {
            $dispatched++;
            $lastEvent = $event;
        });

        $admin = makeAdmin();
        $user = User::factory()->user()->active()->create();
        $buyer = User::factory()->user()->active()->create();

        // The blocked user is a seller: one plain active listing...
        $listingA = Listing::factory()->active()->create(['seller_id' => $user->id]);

        // ...plus a paid job as requester (refund expected)...
        [$job, $jobPayment] = makePaidJobWithPayment($user);

        // ...plus a paid_held transaction as seller on an in_transaction listing.
        [$transaction, $txPayment, $listingB] = makePaidHeldTransactionWithPayment($buyer, $user);
        $listingB->forceFill(['status' => ListingStatus::InTransaction])->save();

        $action = app(BlockUser::class);
        $action->handle($admin, (string) $user->id, 'Penipuan berulang', true);

        expect($user->fresh()->status)->toBe(UserStatus::Blocked);

        // No active items remain.
        /** @var UserModerationContract $moderation */
        $moderation = app(UserModerationContract::class);
        expect($moderation->activeItemsForUsers([(string) $user->id])[(string) $user->id])->toBe([]);

        // Exactly one refund entry per payment.
        $refunds = LedgerEntry::query()
            ->where('entry_type', LedgerEntryType::Refund)
            ->get();
        expect($refunds)->toHaveCount(2);
        expect($refunds->where('payment_id', $jobPayment->id))->toHaveCount(1);
        expect($refunds->where('payment_id', $txPayment->id))->toHaveCount(1);

        // Listings deactivated via the UserBlocked listener (B7 ordering:
        // the cancelled transaction returned listingB to active first).
        expect($listingA->fresh()->status)->toBe(ListingStatus::Inactive);
        expect($listingB->fresh()->status)->toBe(ListingStatus::Inactive);

        // The buyer is untouched.
        expect($buyer->fresh()->status)->toBe(UserStatus::Active);

        expect($dispatched)->toBe(1);
        expect($lastEvent->userId)->toBe((string) $user->id)
            ->and($lastEvent->blockedBy)->toBe((string) $admin->id)
            ->and($lastEvent->reason)->toBe('Penipuan berulang');
    });

    it('ADM-T10 is atomic: a failing second cancellation rolls everything back', function () {
        $admin = makeAdmin();
        $user = User::factory()->user()->active()->create();

        $job1 = makeJobFor($user, JobStatus::Requested);
        $job2 = makeJobFor($user, JobStatus::Requested);

        $mock = Mockery::mock(InspectionJobModerationContract::class);
        $mock->shouldReceive('activeJobsForUsers')
            ->andReturn(new Collection([$job1, $job2]));
        $calls = 0;
        $mock->shouldReceive('cancelJob')->twice()->andReturnUsing(function () use (&$calls, $job1) {
            $calls++;
            if ($calls === 1) {
                return $job1;
            }

            throw new DomainException('gagal', 'TEST_FAIL', 500);
        });
        /** @var InspectionJobModerationContract $jobModeration */
        $jobModeration = $mock;

        $action = new BlockUser($jobModeration, app(TransactionModerationContract::class));

        expect(fn () => $action->handle($admin, (string) $user->id, 'alasan', true))
            ->toThrow(DomainException::class);

        // Nothing changed: user still active, first job still active.
        expect($user->fresh()->status)->toBe(UserStatus::Active);
        expect($job1->fresh()->status)->toBe(JobStatus::Requested);
        expect($job2->fresh()->status)->toBe(JobStatus::Requested);
    });
});

describe('ADM-T10: idempotency', function () {
    it('ADM-T10 blocking twice applies once and fires the event once', function () {
        $dispatched = 0;
        Event::listen(UserBlocked::class, function () use (&$dispatched): void {
            $dispatched++;
        });

        $admin = makeAdmin();
        $user = User::factory()->user()->active()->create();

        $action = app(BlockUser::class);
        $action->handle($admin, (string) $user->id, 'alasan', false);
        $action->handle($admin, (string) $user->id, 'alasan', false);

        expect($user->fresh()->status)->toBe(UserStatus::Blocked);
        expect($dispatched)->toBe(1);
    });
});

describe('ADM-T11: self block and authorization', function () {
    it('ADM-T11 admin cannot block themselves', function () {
        $admin = makeAdmin();

        $action = app(BlockUser::class);

        expect(fn () => $action->handle($admin, (string) $admin->id, 'alasan', false))
            ->toThrow(CannotBlockSelfException::class);

        expect($admin->fresh()->status)->toBe(UserStatus::Active);
    });

    it('ADM-T11 non-admin cannot block', function () {
        $nonAdmin = User::factory()->user()->active()->create();
        $user = User::factory()->user()->active()->create();

        $action = app(BlockUser::class);

        expect(fn () => $action->handle($nonAdmin, (string) $user->id, 'alasan', false))
            ->toThrow(UserModerationException::class);

        expect($user->fresh()->status)->toBe(UserStatus::Active);
    });

    it('ADM-T11 blocked admin cannot block', function () {
        $blockedAdmin = User::factory()->admin()->blocked()->create();
        $user = User::factory()->user()->active()->create();

        $action = app(BlockUser::class);

        expect(fn () => $action->handle($blockedAdmin, (string) $user->id, 'alasan', false))
            ->toThrow(UserModerationException::class);
    });
});
