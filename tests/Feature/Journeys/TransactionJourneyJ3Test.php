<?php

declare(strict_types=1);

namespace Tests\Feature\Journeys;

use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Payment\Actions\ProcessPaymentResult;
use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Payment\Enums\PaymentMethod;
use App\Domains\Payment\Enums\PaymentSimulationOutcome;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use App\Domains\Rating\Actions\SubmitRating;
use App\Domains\Rating\Models\Rating;
use App\Domains\Transaction\Actions\AcceptOffer;
use App\Domains\Transaction\Actions\ConfirmHandover;
use App\Domains\Transaction\Actions\OpenTransactionPayment;
use App\Domains\Transaction\Actions\SubmitOffer;
use App\Domains\Transaction\Enums\TransactionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Journeys\Concerns\HasCompletedInspectionSetup;

uses(RefreshDatabase::class, HasCompletedInspectionSetup::class);

it('executes full transaction journey J3 from offer to rating with ledger verification', function () {
    // 0. Listing aktif yang sudah diinspeksi (rantai J1)
    $state = $this->setupCompletedInspectionState();
    $listing = $state['listing'];
    $seller = $state['seller'];
    $inspector = $state['inspector'];

    $buyer = User::factory()->user()->create([
        'name' => 'Pembeli J3',
        'email' => 'buyer-j3@example.com',
        'status' => UserStatus::Active,
    ]);

    $price = (int) $listing->price;

    // 1. Pembeli mengajukan penawaran (komisi disalin dari hasil inspeksi)
    $transaction = app(SubmitOffer::class)->handle($buyer, $listing, $price, 'Minat, bisa nego tipis.');

    expect($transaction->status)->toBe(TransactionStatus::Offered)
        ->and((string) $transaction->inspector_id)->toBe((string) $inspector->getKey());

    // 2. Penjual menyetujui
    $accepted = app(AcceptOffer::class)->handle($seller, $transaction->fresh());

    expect($accepted->status)->toBe(TransactionStatus::Accepted);

    // 3. Pembeli membuka tagihan dan membayar (simulasi sukses)
    $bill = app(OpenTransactionPayment::class)->handle($buyer, $accepted->fresh());
    $payment = Payment::query()->findOrFail($bill->id);

    app(ProcessPaymentResult::class)->handle($payment, PaymentMethod::Qris, PaymentSimulationOutcome::Success);

    $paid = $transaction->fresh();
    $gross = (int) round($price * 3 / 100);
    $platform = (int) round($gross * 10 / 100);

    expect($paid->status)->toBe(TransactionStatus::PaidHeld)
        ->and((int) $paid->inspector_commission)->toBe($gross - $platform)
        ->and((int) $paid->platform_commission)->toBe($platform);

    // 4. Pembeli konfirmasi serah terima: transaksi selesai, listing terjual
    $completed = app(ConfirmHandover::class)->handle($buyer, $paid->fresh());

    expect($completed->status)->toBe(TransactionStatus::Completed);
    expect($listing->fresh()->status)->toBe(ListingStatus::Sold);

    $entries = LedgerEntry::query()->where('payment_id', $paid->payment_id)->get();

    expect($entries)->toHaveCount(2);

    $platformEntry = $entries->firstWhere('entry_type', LedgerEntryType::PlatformFee);
    $inspectorEntry = $entries->firstWhere('entry_type', LedgerEntryType::SaleCommission);

    expect((int) $platformEntry->amount)->toBe($platform)
        ->and((int) $inspectorEntry->amount)->toBe($gross - $platform)
        ->and($inspectorEntry->account_user_id)->toBe((string) $inspector->getKey());

    // 5. Pembeli memberi rating, rata-rata inspektor diperbarui
    $rating = app(SubmitRating::class)->handle($buyer, $completed->fresh(), 5, 'Inspeksi teliti dan cepat.');

    expect($rating->score)->toBe(5)
        ->and(Rating::query()->where('transaction_id', $completed->getKey())->count())->toBe(1);

    $profile = InspectorProfile::query()->where('user_id', $inspector->getKey())->firstOrFail();

    expect((float) $profile->rating_avg)->toBe(5.0)
        ->and((int) $profile->rating_count)->toBe(1);
});
