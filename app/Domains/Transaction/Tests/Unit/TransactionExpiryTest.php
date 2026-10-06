<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Models\Listing;
use App\Domains\Payment\Actions\ProcessPaymentResult;
use App\Domains\Payment\Enums\PaymentMethod;
use App\Domains\Payment\Enums\PaymentSimulationOutcome;
use App\Domains\Payment\Models\Payment;
use App\Domains\Transaction\Actions\AcceptOffer;
use App\Domains\Transaction\Actions\ExpireAcceptedTransactions;
use App\Domains\Transaction\Actions\ExpireOfferedTransactions;
use App\Domains\Transaction\Actions\MarkStalePaidHeldTransactions;
use App\Domains\Transaction\Actions\OpenTransactionPayment;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Events\TransactionExpired;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeExpirableTransaction(TransactionStatus $status, Carbon $expiresAt): Transaction
{
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

    $transaction = Transaction::factory()->offered()->create([
        'listing_id' => $listing->id,
        'buyer_id' => $buyer->id,
        'expires_at' => $expiresAt,
    ]);

    if ($status === TransactionStatus::Accepted) {
        app(AcceptOffer::class)->handle($seller, $transaction);
        $transaction = $transaction->fresh();
        $transaction->forceFill(['expires_at' => $expiresAt])->save();
    }

    return $transaction->fresh();
}

it('TRX-T11 expires offered transactions past 48 hours (±1 minute tolerance)', function () {
    Carbon::setTestNow($now = Carbon::now());

    $overdue = makeExpirableTransaction(TransactionStatus::Offered, $now->copy()->subMinutes(1));
    $boundary = makeExpirableTransaction(TransactionStatus::Offered, $now->copy()->addMinutes(2));

    Event::fake([TransactionExpired::class]);

    $count = app(ExpireOfferedTransactions::class)->handle();

    expect($count)->toBe(1);
    expect($overdue->fresh()->status)->toBe(TransactionStatus::Expired);
    expect($boundary->fresh()->status)->toBe(TransactionStatus::Offered);

    Event::assertDispatched(TransactionExpired::class, 1);

    Carbon::setTestNow();
});

it('TRX-T11 expires accepted transactions past 24 hours and is idempotent', function () {
    Carbon::setTestNow($now = Carbon::now());

    $overdue = makeExpirableTransaction(TransactionStatus::Accepted, $now->copy()->subMinutes(1));

    $count = app(ExpireAcceptedTransactions::class)->handle();

    expect($count)->toBe(1);
    expect($overdue->fresh()->status)->toBe(TransactionStatus::Expired);

    // Second run: nothing left to expire, no duplicate event.
    Event::fake([TransactionExpired::class]);

    $again = app(ExpireAcceptedTransactions::class)->handle();

    expect($again)->toBe(0);

    Event::assertNotDispatched(TransactionExpired::class);

    Carbon::setTestNow();
});

it('TRX-T11 manual accept at the deadline wins over expiry (one winner)', function () {
    Carbon::setTestNow($now = Carbon::now());

    $transaction = makeExpirableTransaction(TransactionStatus::Offered, $now->copy()->subMinutes(1));
    $seller = $transaction->listing->seller;

    // Manual accept wins the race first.
    app(AcceptOffer::class)->handle($seller, $transaction);

    $count = app(ExpireOfferedTransactions::class)->handle();

    expect($count)->toBe(0);
    expect($transaction->fresh()->status)->toBe(TransactionStatus::Accepted);

    Carbon::setTestNow();
});

it('TRX-T12 flags paid_held past 7 days as needing attention without changing status', function () {
    Carbon::setTestNow($now = Carbon::now());

    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

    $transaction = Transaction::factory()->accepted()->create([
        'listing_id' => $listing->id,
        'buyer_id' => $buyer->id,
    ]);

    $bill = app(OpenTransactionPayment::class)->handle($buyer, $transaction);
    $payment = Payment::query()->findOrFail($bill->id);
    app(ProcessPaymentResult::class)->handle($payment, PaymentMethod::Qris, PaymentSimulationOutcome::Success);

    // Backdate the payment past 7 days.
    $payment->forceFill(['paid_at' => $now->copy()->subDays(8)])->save();

    $transaction = $transaction->fresh();
    expect($transaction->status)->toBe(TransactionStatus::PaidHeld);

    $count = app(MarkStalePaidHeldTransactions::class)->handle();

    expect($count)->toBe(1);

    $fresh = $transaction->fresh();
    expect($fresh->status)->toBe(TransactionStatus::PaidHeld)
        ->and($fresh->needs_attention_at)->not->toBeNull();

    // Idempotent: second run changes nothing.
    $again = app(MarkStalePaidHeldTransactions::class)->handle();

    expect($again)->toBe(0);

    // A recent payment is not flagged.
    $payment->forceFill(['paid_at' => $now->copy()->subDays(2)])->save();
    $fresh->forceFill(['needs_attention_at' => null])->save();

    expect(app(MarkStalePaidHeldTransactions::class)->handle())->toBe(0);

    Carbon::setTestNow();
});

it('TRX-T12 scheduler registers the three transaction commands', function () {
    /** @var Schedule $schedule */
    $schedule = app(Schedule::class);

    $commands = collect($schedule->events())
        ->map(fn ($event) => $event->command ?? '')
        ->filter()
        ->values()
        ->all();

    $joined = implode("\n", $commands);

    expect($joined)->toContain('transaction:expire-offered')
        ->and($joined)->toContain('transaction:expire-accepted')
        ->and($joined)->toContain('transaction:mark-stale-paid-held');
});
