<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Payment\Actions\PostPayout;
use App\Domains\Payment\Actions\ProcessPaymentResult;
use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Payment\Enums\PaymentMethod;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentSimulationOutcome;
use App\Domains\Payment\Exceptions\InvalidLedgerPostingException;
use App\Domains\Payment\Exceptions\LedgerAlreadyPostedException;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use App\Domains\Setting\Enums\SettingKey;
use App\Domains\Setting\Models\AppSetting;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeInspector(): User
{
    return User::factory()->inspector()->create();
}

function makeSuccessPayment(PaymentPurpose $purpose, int $amount): Payment
{
    return Payment::factory()->success()->create([
        'purpose' => $purpose,
        'amount' => $amount,
    ]);
}

it('PAY-T08 posts access payout split between inspector and platform following the flow doc table', function (int $amount, int $platform, int $recipient) {
    $inspector = makeInspector();
    $payment = makeSuccessPayment(PaymentPurpose::Access, $amount);

    app(PostPayout::class)->handle($payment, $inspector->id, LedgerEntryType::AccessFee);

    $entries = LedgerEntry::query()->where('payment_id', $payment->id)->orderBy('amount')->get();

    expect($entries)->toHaveCount(2)
        ->and($entries->sum('amount'))->toBe($amount);

    $platformEntry = $entries->firstWhere('entry_type', LedgerEntryType::PlatformFee);
    $inspectorEntry = $entries->firstWhere('entry_type', LedgerEntryType::AccessFee);

    expect((int) $platformEntry->amount)->toBe($platform)
        ->and($platformEntry->account_user_id)->toBeNull()
        ->and((int) $inspectorEntry->amount)->toBe($recipient)
        ->and($inspectorEntry->account_user_id)->toBe($inspector->id);
})->with([
    'car inspection 50.000' => [50000, 5000, 45000],
    'motorcycle access 15.000' => [15000, 1500, 13500],
    'odd tariff 15.005 rounds half up' => [15005, 1501, 13504],
]);

it('PAY-T08 uses the current platform_fee_pct from settings for new postings', function () {
    AppSetting::updateOrCreate(
        ['key' => SettingKey::PlatformFeePct->value],
        ['value' => 25],
    );

    $inspector = makeInspector();
    $payment = makeSuccessPayment(PaymentPurpose::Access, 50000);

    app(PostPayout::class)->handle($payment, $inspector->id, LedgerEntryType::AccessFee);

    $entries = LedgerEntry::query()->where('payment_id', $payment->id)->get();

    expect((int) $entries->firstWhere('entry_type', LedgerEntryType::PlatformFee)->amount)->toBe(12500)
        ->and((int) $entries->firstWhere('entry_type', LedgerEntryType::AccessFee)->amount)->toBe(37500);
});

it('keeps old ledger entries unchanged when platform_fee_pct changes later', function () {
    $inspector = makeInspector();
    $payment = makeSuccessPayment(PaymentPurpose::Access, 50000);

    app(PostPayout::class)->handle($payment, $inspector->id, LedgerEntryType::AccessFee);

    $before = LedgerEntry::query()->where('payment_id', $payment->id)->orderBy('id')->pluck('amount')->all();

    AppSetting::updateOrCreate(
        ['key' => SettingKey::PlatformFeePct->value],
        ['value' => 50],
    );

    $later = LedgerEntry::query()->where('payment_id', $payment->id)->orderBy('id')->pluck('amount')->all();

    expect($later)->toBe($before)
        ->and(array_sum($later))->toBe(50000);
});

it('PAY-T09 does not post inspection payout when the payment succeeds', function () {
    $payer = User::factory()->create();
    $payment = Payment::factory()->forPayer($payer)->pending()->create([
        'purpose' => PaymentPurpose::Inspection,
    ]);

    app(ProcessPaymentResult::class)->handle($payment, PaymentMethod::Qris, PaymentSimulationOutcome::Success);

    expect(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(0);
});

it('PAY-T09 posts inspection payout only through the Ledger contract when the job completes', function () {
    $inspector = makeInspector();
    $payment = makeSuccessPayment(PaymentPurpose::Inspection, 50000);

    app(PostPayout::class)->handle($payment, $inspector->id, LedgerEntryType::InspectionFee);

    $entries = LedgerEntry::query()->where('payment_id', $payment->id)->get();

    expect($entries)->toHaveCount(2)
        ->and($entries->sum('amount'))->toBe(50000)
        ->and((int) $entries->firstWhere('entry_type', LedgerEntryType::PlatformFee)->amount)->toBe(5000)
        ->and((int) $entries->firstWhere('entry_type', LedgerEntryType::InspectionFee)->amount)->toBe(45000)
        ->and($entries->firstWhere('entry_type', LedgerEntryType::InspectionFee)->account_user_id)->toBe($inspector->id);
});

it('rejects payout for a payment that is not success', function (string $state) {
    $inspector = makeInspector();
    $payment = Payment::factory()->create(['status' => $state, 'purpose' => PaymentPurpose::Access]);

    expect(fn () => app(PostPayout::class)->handle($payment, $inspector->id, LedgerEntryType::AccessFee))
        ->toThrow(InvalidLedgerPostingException::class);

    expect(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(0);
})->with([
    'pending' => ['pending'],
    'failed' => ['failed'],
    'refunded' => ['refunded'],
]);

it('rejects payout when purpose and entry type do not match', function () {
    $inspector = makeInspector();
    $payment = makeSuccessPayment(PaymentPurpose::Access, 50000);

    expect(fn () => app(PostPayout::class)->handle($payment, $inspector->id, LedgerEntryType::InspectionFee))
        ->toThrow(InvalidLedgerPostingException::class);

    expect(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(0);
});

it('rejects payout for purchase purpose until TRX-2 defines the commission base', function () {
    $inspector = makeInspector();
    $payment = makeSuccessPayment(PaymentPurpose::Purchase, 150000000);

    expect(fn () => app(PostPayout::class)->handle($payment, $inspector->id, LedgerEntryType::SaleCommission))
        ->toThrow(InvalidLedgerPostingException::class);

    expect(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(0);
});

it('rejects payout when the recipient is not an inspector', function (?string $scenario) {
    $payment = makeSuccessPayment(PaymentPurpose::Access, 50000);

    $inspectorId = match ($scenario) {
        'missing user' => (string) Str::uuid(),
        default => User::factory()->create()->id,
    };

    expect(fn () => app(PostPayout::class)->handle($payment, $inspectorId, LedgerEntryType::AccessFee))
        ->toThrow(InvalidLedgerPostingException::class);

    expect(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(0);
})->with([
    'missing user' => ['missing user'],
    'regular user' => ['regular user'],
]);

it('rejects double posting for the same payment', function () {
    $inspector = makeInspector();
    $payment = makeSuccessPayment(PaymentPurpose::Access, 50000);

    app(PostPayout::class)->handle($payment, $inspector->id, LedgerEntryType::AccessFee);

    expect(fn () => app(PostPayout::class)->handle($payment, $inspector->id, LedgerEntryType::AccessFee))
        ->toThrow(LedgerAlreadyPostedException::class);

    expect(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(2);
});

it('rejects direct duplicate ledger insert with QueryException on the unique payment and entry type', function () {
    $inspector = makeInspector();
    $payment = makeSuccessPayment(PaymentPurpose::Access, 50000);

    app(PostPayout::class)->handle($payment, $inspector->id, LedgerEntryType::AccessFee);

    LedgerEntry::query()->create([
        'payment_id' => $payment->id,
        'account_user_id' => null,
        'entry_type' => LedgerEntryType::PlatformFee,
        'amount' => 5000,
    ]);
})->throws(QueryException::class);
