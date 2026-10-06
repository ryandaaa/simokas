<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Tests\Feature;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Models\Listing;
use App\Domains\Payment\Actions\ProcessPaymentResult;
use App\Domains\Payment\Enums\PaymentMethod;
use App\Domains\Payment\Enums\PaymentSimulationOutcome;
use App\Domains\Payment\Models\Payment;
use App\Domains\Transaction\Actions\ConfirmHandover;
use App\Domains\Transaction\Actions\OpenTransactionPayment;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Livewire\TransactionDetailPage;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

function makeAcceptedTransactionForPage(?int $commissionPct = 3): Transaction
{
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

    $inspectorId = $commissionPct === null ? null : User::factory()->inspector()->create()->id;

    return Transaction::factory()->accepted()->create([
        'listing_id' => $listing->id,
        'buyer_id' => $buyer->id,
        'price_amount' => 100000000,
        'commission_pct' => $commissionPct,
        'inspector_id' => $inspectorId,
    ]);
}

it('TRX-T14 shows the pay button to the buyer on an accepted transaction', function () {
    $transaction = makeAcceptedTransactionForPage();

    actingAs($transaction->buyer);

    Livewire::test(TransactionDetailPage::class, ['transaction' => $transaction])
        ->assertOk()
        ->assertSee(__('transaction.detail.pay'));
});

it('does not show the pay button to the seller', function () {
    $transaction = makeAcceptedTransactionForPage();

    actingAs($transaction->listing->seller);

    Livewire::test(TransactionDetailPage::class, ['transaction' => $transaction])
        ->assertOk()
        ->assertDontSee(__('transaction.detail.pay'));
});

it('shows a waiting message instead of the pay button while a job is active (TRX-E4)', function () {
    $transaction = makeAcceptedTransactionForPage();

    InspectionJob::factory()->paid()->create(['listing_id' => $transaction->listing_id]);

    actingAs($transaction->buyer);

    Livewire::test(TransactionDetailPage::class, ['transaction' => $transaction])
        ->assertOk()
        ->assertSee(__('transaction.detail.pay_waiting_job'))
        ->assertDontSee(__('transaction.detail.pay'));
});

it('forbids pay action for non-buyers', function () {
    $transaction = makeAcceptedTransactionForPage();

    // The seller can view the page but must not be able to pay.
    actingAs($transaction->listing->seller);

    Livewire::test(TransactionDetailPage::class, ['transaction' => $transaction])
        ->call('pay')
        ->assertForbidden();
});

it('pay opens the bill and redirects to the payment page', function () {
    $transaction = makeAcceptedTransactionForPage();

    actingAs($transaction->buyer);

    $bill = app(OpenTransactionPayment::class)->handle($transaction->buyer, $transaction);

    Livewire::test(TransactionDetailPage::class, ['transaction' => $transaction])
        ->call('pay')
        ->assertRedirect(route('payments.show', ['payment' => $bill->id]));
});

it('shows the handover button to the buyer on a paid_held transaction', function () {
    $transaction = makeAcceptedTransactionForPage();
    $bill = app(OpenTransactionPayment::class)->handle($transaction->buyer, $transaction);
    $payment = Payment::query()->findOrFail($bill->id);
    app(ProcessPaymentResult::class)->handle($payment, PaymentMethod::Qris, PaymentSimulationOutcome::Success);

    actingAs($transaction->buyer);

    Livewire::test(TransactionDetailPage::class, ['transaction' => $transaction->fresh()])
        ->assertOk()
        ->assertSee(__('transaction.detail.confirm_handover'));
});

it('lets an active admin confirm the handover through the action and policy', function () {
    $transaction = makeAcceptedTransactionForPage();
    $bill = app(OpenTransactionPayment::class)->handle($transaction->buyer, $transaction);
    $payment = Payment::query()->findOrFail($bill->id);
    app(ProcessPaymentResult::class)->handle($payment, PaymentMethod::Qris, PaymentSimulationOutcome::Success);

    $admin = User::factory()->admin()->create();

    expect($admin->can('confirmHandover', $transaction->fresh()))->toBeTrue();

    $completed = app(ConfirmHandover::class)
        ->handle($admin, $transaction->fresh());

    expect($completed->status)->toBe(TransactionStatus::Completed);
});

it('forbids handover confirmation for outsiders', function () {
    $transaction = makeAcceptedTransactionForPage();
    $bill = app(OpenTransactionPayment::class)->handle($transaction->buyer, $transaction);
    $payment = Payment::query()->findOrFail($bill->id);
    app(ProcessPaymentResult::class)->handle($payment, PaymentMethod::Qris, PaymentSimulationOutcome::Success);

    $outsider = User::factory()->user()->create();

    actingAs($outsider);

    // Outsider cannot even view the page.
    get(route('transactions.show', ['transaction' => $transaction->id]))->assertForbidden();
});

it('confirmHandover completes the transaction from the page', function () {
    $transaction = makeAcceptedTransactionForPage();
    $bill = app(OpenTransactionPayment::class)->handle($transaction->buyer, $transaction);
    $payment = Payment::query()->findOrFail($bill->id);
    app(ProcessPaymentResult::class)->handle($payment, PaymentMethod::Qris, PaymentSimulationOutcome::Success);

    actingAs($transaction->buyer);

    Livewire::test(TransactionDetailPage::class, ['transaction' => $transaction->fresh()])
        ->call('confirmHandover')
        ->assertSee(__('transaction.detail.handover_success'));

    expect($transaction->fresh()->status)->toBe(TransactionStatus::Completed);
});
