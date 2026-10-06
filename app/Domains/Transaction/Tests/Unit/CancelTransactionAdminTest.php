<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Tests\Unit;

use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Listing\Models\Listing;
use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Payment\Enums\PaymentMethod;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use App\Domains\Transaction\Actions\CancelTransaction;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Exceptions\TransactionNotCancellableException;
use App\Domains\Transaction\Exceptions\UnauthorizedTransactionActionException;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeTx(TransactionStatus $status, User $buyer, User $seller): Transaction
{
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

    return Transaction::factory()->create([
        'listing_id' => $listing->id,
        'buyer_id' => $buyer->id,
        'status' => $status,
        'expires_at' => now()->addDay(),
    ]);
}

describe('ADM-4: admin cancels transactions on offered, accepted, paid_held', function () {
    it('ADM-4 admin cancels offered and accepted without refund rows', function (TransactionStatus $status) {
        $admin = User::factory()->admin()->active()->create();
        $buyer = User::factory()->user()->active()->create();
        $seller = User::factory()->user()->active()->create();

        $transaction = makeTx($status, $buyer, $seller);

        $result = app(CancelTransaction::class)->handle($admin, $transaction, 'Spam');

        expect($result->status)->toBe(TransactionStatus::Cancelled);
        expect(LedgerEntry::query()->count())->toBe(0);
    })->with([TransactionStatus::Offered, TransactionStatus::Accepted]);

    it('ADM-4 admin cancels paid_held with 100% refund and reason required', function () {
        $admin = User::factory()->admin()->active()->create();
        $buyer = User::factory()->user()->active()->create();
        $seller = User::factory()->user()->active()->create();

        $transaction = makeTx(TransactionStatus::PaidHeld, $buyer, $seller);

        $payment = Payment::factory()->create([
            'payer_id' => $buyer->id,
            'purpose' => PaymentPurpose::Purchase,
            'ref_id' => $transaction->id,
            'amount' => 75000000,
            'status' => PaymentStatus::Success,
            'method' => PaymentMethod::Qris,
        ]);
        $transaction->forceFill(['payment_id' => $payment->id])->save();

        // Reason required on paid_held, even for admin.
        expect(fn () => app(CancelTransaction::class)->handle($admin, $transaction))
            ->toThrow(TransactionNotCancellableException::class);

        $result = app(CancelTransaction::class)->handle($admin, $transaction, 'Akun bermasalah');

        expect($result->status)->toBe(TransactionStatus::Cancelled);

        $refunds = LedgerEntry::query()
            ->where('payment_id', $payment->id)
            ->where('entry_type', LedgerEntryType::Refund)
            ->get();
        expect($refunds)->toHaveCount(1);
    });

    it('ADM-4 non-admin parties are unchanged', function () {
        $admin = User::factory()->admin()->active()->create();
        $buyer = User::factory()->user()->active()->create();
        $seller = User::factory()->user()->active()->create();
        $stranger = User::factory()->user()->active()->create();

        // Stranger still cannot cancel offered.
        $t1 = makeTx(TransactionStatus::Offered, $buyer, $seller);
        expect(fn () => app(CancelTransaction::class)->handle($stranger, $t1))
            ->toThrow(UnauthorizedTransactionActionException::class);

        // Seller still cannot cancel offered (only buyer or admin).
        $t2 = makeTx(TransactionStatus::Offered, $buyer, $seller);
        expect(fn () => app(CancelTransaction::class)->handle($seller, $t2))
            ->toThrow(UnauthorizedTransactionActionException::class);

        // Buyer still cannot cancel paid_held (only seller or admin).
        $t3 = makeTx(TransactionStatus::PaidHeld, $buyer, $seller);
        expect(fn () => app(CancelTransaction::class)->handle($buyer, $t3, 'alasan'))
            ->toThrow(UnauthorizedTransactionActionException::class);

        // Admin gate still enforced: blocked admin cannot cancel.
        $blockedAdmin = User::factory()->admin()->blocked()->create();
        $t4 = makeTx(TransactionStatus::Accepted, $buyer, $seller);
        expect(fn () => app(CancelTransaction::class)->handle($blockedAdmin, $t4, 'alasan'))
            ->toThrow(UnauthorizedTransactionActionException::class);

        expect($admin->fresh()->status)->toBe(UserStatus::Active);
    });
});
