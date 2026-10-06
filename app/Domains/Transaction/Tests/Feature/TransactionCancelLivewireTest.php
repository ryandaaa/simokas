<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Tests\Feature;

use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Listing\Models\Listing;
use App\Domains\Payment\Actions\ProcessPaymentResult;
use App\Domains\Payment\Enums\PaymentMethod;
use App\Domains\Payment\Enums\PaymentSimulationOutcome;
use App\Domains\Payment\Models\Payment;
use App\Domains\Transaction\Actions\OpenTransactionPayment;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Livewire\TransactionDetailPage;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function makeTransactionForCancelPage(TransactionStatus $status): Transaction
{
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

    $transaction = Transaction::factory()->create([
        'listing_id' => $listing->id,
        'buyer_id' => $buyer->id,
        'status' => $status === TransactionStatus::PaidHeld ? TransactionStatus::Accepted : $status,
        'price_amount' => 100000000,
    ]);

    if ($status === TransactionStatus::PaidHeld) {
        $bill = app(OpenTransactionPayment::class)->handle($buyer, $transaction);
        $payment = Payment::query()->findOrFail($bill->id);
        app(ProcessPaymentResult::class)->handle($payment, PaymentMethod::Qris, PaymentSimulationOutcome::Success);
    }

    return $transaction->fresh();
}

it('TRX-T14 forbids cancel for unauthorized roles', function () {
    $transaction = makeTransactionForCancelPage(TransactionStatus::Accepted);
    $outsider = User::factory()->user()->create();

    actingAs($outsider);

    // Outsider cannot even view, so the page itself is forbidden.
    Livewire::test(TransactionDetailPage::class, ['transaction' => $transaction])
        ->assertForbidden();
});

it('TRX-T14 seller cannot request cancellation (buyer-only action)', function () {
    $transaction = makeTransactionForCancelPage(TransactionStatus::PaidHeld);

    actingAs($transaction->listing->seller);

    Livewire::test(TransactionDetailPage::class, ['transaction' => $transaction])
        ->call('requestCancellation')
        ->assertForbidden();
});

it('TRX-T14 buyer cannot approve their own cancellation request', function () {
    $transaction = makeTransactionForCancelPage(TransactionStatus::PaidHeld);

    actingAs($transaction->buyer);

    Livewire::test(TransactionDetailPage::class, ['transaction' => $transaction])
        ->call('approveCancellation')
        ->assertForbidden();
});

it('TRX-T14 blocked buyer cannot even view the page', function () {
    $transaction = makeTransactionForCancelPage(TransactionStatus::Offered);
    $buyer = $transaction->buyer;
    $buyer->forceFill(['status' => UserStatus::Blocked])->save();

    actingAs($buyer);

    // Blocked parties cannot act at all (TRX-E12); the page itself is forbidden.
    Livewire::test(TransactionDetailPage::class, ['transaction' => $transaction])
        ->assertForbidden();
});

it('buyer cancels an offered transaction from the page', function () {
    $transaction = makeTransactionForCancelPage(TransactionStatus::Offered);

    actingAs($transaction->buyer);

    Livewire::test(TransactionDetailPage::class, ['transaction' => $transaction])
        ->call('cancel')
        ->assertSee(__('transaction.detail.cancel_success'));

    expect($transaction->fresh()->status)->toBe(TransactionStatus::Cancelled);
});

it('buyer requests cancellation from the paid_held page', function () {
    $transaction = makeTransactionForCancelPage(TransactionStatus::PaidHeld);

    actingAs($transaction->buyer);

    Livewire::test(TransactionDetailPage::class, ['transaction' => $transaction])
        ->call('requestCancellation')
        ->assertSee(__('transaction.detail.cancel_request_success'));

    $fresh = $transaction->fresh();
    expect($fresh->status)->toBe(TransactionStatus::PaidHeld)
        ->and($fresh->cancel_requested_at)->not->toBeNull();
});

it('seller approves the cancellation request from the page', function () {
    $transaction = makeTransactionForCancelPage(TransactionStatus::PaidHeld);

    actingAs($transaction->buyer);
    Livewire::test(TransactionDetailPage::class, ['transaction' => $transaction])
        ->call('requestCancellation');

    actingAs($transaction->listing->seller);

    Livewire::test(TransactionDetailPage::class, ['transaction' => $transaction->fresh()])
        ->set('cancelReason', 'setuju')
        ->call('approveCancellation')
        ->assertSee(__('transaction.detail.cancel_success'));

    expect($transaction->fresh()->status)->toBe(TransactionStatus::Cancelled);
});

it('seller cancels paid_held from the page with a required reason', function () {
    $transaction = makeTransactionForCancelPage(TransactionStatus::PaidHeld);

    actingAs($transaction->listing->seller);

    // Without a reason: domain error, still paid_held.
    Livewire::test(TransactionDetailPage::class, ['transaction' => $transaction])
        ->call('cancel')
        ->assertSee(__('transaction.errors.cancel_reason_required'));

    expect($transaction->fresh()->status)->toBe(TransactionStatus::PaidHeld);

    // With a reason: cancelled and refunded.
    Livewire::test(TransactionDetailPage::class, ['transaction' => $transaction->fresh()])
        ->set('cancelReason', 'unit tidak sesuai')
        ->call('cancel')
        ->assertSee(__('transaction.detail.cancel_success'));

    expect($transaction->fresh()->status)->toBe(TransactionStatus::Cancelled);
});

it('active admin cancels paid_held from the page', function () {
    $transaction = makeTransactionForCancelPage(TransactionStatus::PaidHeld);
    $admin = User::factory()->admin()->create();

    actingAs($admin);

    Livewire::test(TransactionDetailPage::class, ['transaction' => $transaction])
        ->set('cancelReason', 'keputusan admin')
        ->call('cancel')
        ->assertSee(__('transaction.detail.cancel_success'));

    expect($transaction->fresh()->status)->toBe(TransactionStatus::Cancelled);
});
