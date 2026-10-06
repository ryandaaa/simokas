<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Models\Listing;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use App\Domains\Transaction\Actions\OpenTransactionPayment;
use App\Domains\Transaction\Exceptions\ActiveJobBlocksPaymentException;
use App\Domains\Transaction\Exceptions\TransactionNotPayableException;
use App\Domains\Transaction\Exceptions\UnauthorizedTransactionActionException;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeAcceptedTransaction(?int $commissionPct = 3): Transaction
{
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

    return Transaction::factory()->accepted()->create([
        'listing_id' => $listing->id,
        'buyer_id' => $buyer->id,
        'price_amount' => 100000000,
        'commission_pct' => $commissionPct,
    ]);
}

it('TRX-T08 opens a purchase bill with a stable idempotency key', function () {
    $transaction = makeAcceptedTransaction();
    $buyer = $transaction->buyer;

    $bill = app(OpenTransactionPayment::class)->handle($buyer, $transaction);

    expect($bill->purpose)->toBe(PaymentPurpose::Purchase)
        ->and($bill->refId)->toBe((string) $transaction->getKey())
        ->and($bill->amount)->toBe(100000000)
        ->and($bill->payerId)->toBe((string) $buyer->getKey())
        ->and($bill->idempotencyKey)->toBe("purchase-transaction-{$transaction->getKey()}")
        ->and($bill->status)->toBe(PaymentStatus::Pending);

    expect($transaction->fresh()->payment_id)->toBe($bill->id);
});

it('TRX-T08 opening payment twice is idempotent (one bill)', function () {
    $transaction = makeAcceptedTransaction();
    $buyer = $transaction->buyer;

    $first = app(OpenTransactionPayment::class)->handle($buyer, $transaction);
    $second = app(OpenTransactionPayment::class)->handle($buyer, $transaction);

    expect($second->id)->toBe($first->id)
        ->and(Payment::query()->where('ref_id', $transaction->getKey())->count())->toBe(1);
});

it('TRX-T07 blocks payment while an inspection job is active', function () {
    $transaction = makeAcceptedTransaction();
    $buyer = $transaction->buyer;

    InspectionJob::factory()->paid()->create(['listing_id' => $transaction->listing_id]);

    expect(fn () => app(OpenTransactionPayment::class)->handle($buyer, $transaction))
        ->toThrow(ActiveJobBlocksPaymentException::class);

    expect($transaction->fresh()->payment_id)->toBeNull();
});

it('TRX-T07 opens payment after the job reaches a final status', function () {
    $transaction = makeAcceptedTransaction();
    $buyer = $transaction->buyer;

    InspectionJob::factory()->cancelled()->create(['listing_id' => $transaction->listing_id]);

    $bill = app(OpenTransactionPayment::class)->handle($buyer, $transaction);

    expect($bill->id)->not->toBeEmpty();
});

it('rejects payment for a non-accepted transaction', function (string $state) {
    $transaction = makeAcceptedTransaction();
    $transaction->update(['status' => $state]);
    $buyer = $transaction->buyer;

    expect(fn () => app(OpenTransactionPayment::class)->handle($buyer, $transaction))
        ->toThrow(TransactionNotPayableException::class);
})->with(['offered', 'paid_held', 'completed', 'rejected', 'cancelled']);

it('rejects payment opened by someone other than the buyer', function () {
    $transaction = makeAcceptedTransaction();
    $stranger = User::factory()->user()->create();

    expect(fn () => app(OpenTransactionPayment::class)->handle($stranger, $transaction))
        ->toThrow(UnauthorizedTransactionActionException::class);
});

it('restarts the 24-hour payment counter when the bill is opened (D-04)', function () {
    $transaction = makeAcceptedTransaction();
    $buyer = $transaction->buyer;

    $before = now();
    app(OpenTransactionPayment::class)->handle($buyer, $transaction);

    $expiresAt = $transaction->fresh()->expires_at;

    expect($expiresAt->greaterThan($before->copy()->addHours(23)))->toBeTrue();
    expect($expiresAt->lessThanOrEqualTo($before->copy()->addHours(24)->addMinute()))->toBeTrue();
});
