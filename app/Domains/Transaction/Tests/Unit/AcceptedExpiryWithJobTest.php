<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Events\JobCancelled;
use App\Domains\Inspection\Events\JobCompleted;
use App\Domains\Inspection\Events\JobExpired;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Models\Listing;
use App\Domains\Payment\Models\Payment;
use App\Domains\Transaction\Actions\ExpireAcceptedTransactions;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Listeners\RestartPaymentCountdownOnJobFinalized;
use App\Domains\Transaction\Models\Transaction;
use App\Support\Clock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeAcceptedForExpiryFix(?Carbon $expiresAt = null): Transaction
{
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

    return Transaction::factory()->accepted()->create([
        'listing_id' => $listing->id,
        'buyer_id' => $buyer->id,
        'expires_at' => $expiresAt ?? Clock::now()->addHours(24),
    ]);
}

function makeActiveJob(Listing $listing): InspectionJob
{
    return InspectionJob::factory()->inProgress()->create([
        'listing_id' => $listing->id,
    ]);
}

it('TRX-T11 accepted with an active job does not expire even when expires_at passed', function () {
    Carbon::setTestNow($now = Carbon::now());

    $transaction = makeAcceptedForExpiryFix($now->copy()->subHour());
    makeActiveJob($transaction->listing);

    $count = app(ExpireAcceptedTransactions::class)->handle();

    expect($count)->toBe(0);
    expect($transaction->fresh()->status)->toBe(TransactionStatus::Accepted);

    Carbon::setTestNow();
});

it('TRX-T11 restarts the 24h countdown when the job is completed, cancelled, or expired', function () {
    foreach (['completed', 'cancelled', 'expired'] as $kind) {
        Carbon::setTestNow($now = Carbon::now());

        $transaction = makeAcceptedForExpiryFix($now->copy()->subHours(5));
        $listingId = (string) $transaction->listing_id;

        $event = match ($kind) {
            'completed' => new JobCompleted(jobId: (string) Str::uuid(), listingId: $listingId),
            'cancelled' => new JobCancelled(jobId: (string) Str::uuid(), listingId: $listingId),
            'expired' => new JobExpired(jobId: (string) Str::uuid(), listingId: $listingId),
        };

        app(RestartPaymentCountdownOnJobFinalized::class)->handle($event);

        $fresh = $transaction->fresh();
        expect($fresh->status)->toBe(TransactionStatus::Accepted);

        $expected = $now->copy()->addHours(24);
        expect($fresh->expires_at->diffInMinutes($expected))->toBeLessThanOrEqual(1);

        // Not expirable yet...
        expect(app(ExpireAcceptedTransactions::class)->handle())->toBe(0);

        // ...but expires 24 hours later (±1 minute).
        Carbon::setTestNow($now->copy()->addHours(24)->addMinutes(2));

        expect(app(ExpireAcceptedTransactions::class)->handle())->toBe(1);
        expect($transaction->fresh()->status)->toBe(TransactionStatus::Expired);

        Carbon::setTestNow();
    }
});

it('TRX-T11 does not reset expiry for accepted transactions that already opened a bill', function () {
    Carbon::setTestNow($now = Carbon::now());

    $transaction = makeAcceptedForExpiryFix($original = $now->copy()->addHours(2));

    $payment = Payment::factory()->forPayer($transaction->buyer)->pending()->create();
    $transaction->forceFill(['payment_id' => $payment->getKey()])->save();
    $original = $transaction->fresh()->expires_at;

    app(RestartPaymentCountdownOnJobFinalized::class)->handle(
        new JobCompleted(jobId: (string) Str::uuid(), listingId: (string) $transaction->listing_id)
    );

    expect($transaction->fresh()->expires_at->equalTo($original))->toBeTrue();

    Carbon::setTestNow();
});

it('TRX-T11 the restart listener is idempotent when run twice', function () {
    Carbon::setTestNow($now = Carbon::now());

    $transaction = makeAcceptedForExpiryFix($now->copy()->subHour());

    $listener = app(RestartPaymentCountdownOnJobFinalized::class);
    $event = new JobCancelled(jobId: (string) Str::uuid(), listingId: (string) $transaction->listing_id);

    $listener->handle($event);
    $listener->handle($event);

    $fresh = $transaction->fresh();
    expect($fresh->status)->toBe(TransactionStatus::Accepted);
    expect($fresh->expires_at->diffInMinutes($now->copy()->addHours(24)))->toBeLessThanOrEqual(1);

    Carbon::setTestNow();
});

it('TRX-T11 accepted without an active job still expires per expires_at', function () {
    Carbon::setTestNow($now = Carbon::now());

    $transaction = makeAcceptedForExpiryFix($now->copy()->subMinutes(2));

    expect(app(ExpireAcceptedTransactions::class)->handle())->toBe(1);
    expect($transaction->fresh()->status)->toBe(TransactionStatus::Expired);

    Carbon::setTestNow();
});
