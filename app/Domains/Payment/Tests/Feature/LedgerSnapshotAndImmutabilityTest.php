<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Payment\Actions\PostPayout;
use App\Domains\Payment\Contracts\Ledger;
use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Exceptions\InvalidLedgerPostingException;
use App\Domains\Payment\Exceptions\LedgerEntryImmutableException;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use App\Domains\Setting\Enums\SettingKey;
use App\Domains\Setting\Models\AppSetting;
use App\Support\Exceptions\DomainException;
use App\Support\Money;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function reviewInspector(): User
{
    return User::factory()->inspector()->create();
}

function reviewSuccessPayment(PaymentPurpose $purpose, int $amount): Payment
{
    return Payment::factory()->success()->create([
        'purpose' => $purpose,
        'amount' => $amount,
    ]);
}

it('PAY-T11 posts inspection payout with the job snapshot pct ignoring later setting changes', function (int $amount, int $platform, int $recipient) {
    $snapshotPct = 10;

    $inspector = reviewInspector();
    $payment = reviewSuccessPayment(PaymentPurpose::Inspection, $amount);

    // Setting berubah menjadi 20 SETELAH job dibuat, SEBELUM posting saat completed.
    AppSetting::updateOrCreate(['key' => SettingKey::PlatformFeePct->value], ['value' => 20]);

    app(Ledger::class)->postInspectionPayout($payment->id, $inspector->id, $snapshotPct);

    $entries = LedgerEntry::query()->where('payment_id', $payment->id)->get();

    expect($entries)->toHaveCount(2)
        ->and($entries->sum('amount'))->toBe($amount)
        ->and((int) $entries->firstWhere('entry_type', LedgerEntryType::PlatformFee)->amount)->toBe($platform)
        ->and((int) $entries->firstWhere('entry_type', LedgerEntryType::InspectionFee)->amount)->toBe($recipient)
        ->and($entries->firstWhere('entry_type', LedgerEntryType::InspectionFee)->account_user_id)->toBe($inspector->id);
})->with([
    'odd tariff 15.005 at 10pct' => [15005, 1501, 13504],
    'car inspection 50.000 at 10pct' => [50000, 5000, 45000],
    'motorcycle access tariff 15.000 at 10pct' => [15000, 1500, 13500],
]);

it('PAY-T13 keeps the invariant for explicit snapshot percentages across purposes', function (PaymentPurpose $purpose, LedgerEntryType $creditType, int $amount, int $pct) {
    // Pastikan pengaturan saat ini berbeda dari snapshot agar fallback tidak lolos diam-diam.
    AppSetting::updateOrCreate(['key' => SettingKey::PlatformFeePct->value], ['value' => $pct === 20 ? 10 : 20]);

    $inspector = reviewInspector();
    $payment = reviewSuccessPayment($purpose, $amount);

    $ledger = app(Ledger::class);

    match ($purpose) {
        PaymentPurpose::Access => $ledger->postAccessPayout($payment->id, $inspector->id, $pct),
        PaymentPurpose::Inspection => $ledger->postInspectionPayout($payment->id, $inspector->id, $pct),
        default => null,
    };

    $entries = LedgerEntry::query()->where('payment_id', $payment->id)->get();
    $split = Money::split($amount, $pct);

    expect($entries)->toHaveCount(2)
        ->and($entries->sum('amount'))->toBe($amount)
        ->and((int) $entries->firstWhere('entry_type', LedgerEntryType::PlatformFee)->amount)->toBe($split['platform'])
        ->and((int) $entries->firstWhere('entry_type', $creditType)->amount)->toBe($split['recipient']);
})->with([
    'access 15005 at 0pct' => [PaymentPurpose::Access, LedgerEntryType::AccessFee, 15005, 0],
    'access 15005 at 10pct' => [PaymentPurpose::Access, LedgerEntryType::AccessFee, 15005, 10],
    'access 50000 at 25pct' => [PaymentPurpose::Access, LedgerEntryType::AccessFee, 50000, 25],
    'access 50000 at 100pct' => [PaymentPurpose::Access, LedgerEntryType::AccessFee, 50000, 100],
    'inspection 15005 at 10pct' => [PaymentPurpose::Inspection, LedgerEntryType::InspectionFee, 15005, 10],
    'inspection 50000 at 15pct' => [PaymentPurpose::Inspection, LedgerEntryType::InspectionFee, 50000, 15],
    'inspection 999999 at 33pct' => [PaymentPurpose::Inspection, LedgerEntryType::InspectionFee, 999999, 33],
]);

it('PAY-T08 posts access payout with current settings when no snapshot pct is supplied', function () {
    AppSetting::updateOrCreate(['key' => SettingKey::PlatformFeePct->value], ['value' => 25]);

    $inspector = reviewInspector();
    $payment = reviewSuccessPayment(PaymentPurpose::Access, 50000);

    app(Ledger::class)->postAccessPayout($payment->id, $inspector->id);

    $entries = LedgerEntry::query()->where('payment_id', $payment->id)->get();

    expect((int) $entries->firstWhere('entry_type', LedgerEntryType::PlatformFee)->amount)->toBe(12500)
        ->and((int) $entries->firstWhere('entry_type', LedgerEntryType::AccessFee)->amount)->toBe(37500)
        ->and($entries->sum('amount'))->toBe(50000);
});

it('records the used percentage in the entry notes for audit', function () {
    $inspector = reviewInspector();
    $payment = reviewSuccessPayment(PaymentPurpose::Inspection, 50000);

    app(Ledger::class)->postInspectionPayout($payment->id, $inspector->id, 10);

    $entries = LedgerEntry::query()->where('payment_id', $payment->id)->get();

    expect($entries->firstWhere('entry_type', LedgerEntryType::PlatformFee)->note)->toContain('10')
        ->and($entries->firstWhere('entry_type', LedgerEntryType::InspectionFee)->note)->toContain('10');
});

it('rejects out-of-range percentages with a domain exception', function (int $pct) {
    $inspector = reviewInspector();
    $payment = reviewSuccessPayment(PaymentPurpose::Access, 50000);

    expect(fn () => app(Ledger::class)->postAccessPayout($payment->id, $inspector->id, $pct))
        ->toThrow(InvalidLedgerPostingException::class);

    expect(fn () => app(PostPayout::class)->handle($payment, $inspector->id, LedgerEntryType::AccessFee, $pct))
        ->toThrow(DomainException::class);

    expect(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(0);
})->with([
    'negative' => [-1],
    'above hundred' => [101],
    'far above hundred' => [150],
]);

it('rejects inspection posting without a job snapshot percentage', function () {
    $inspector = reviewInspector();
    $payment = reviewSuccessPayment(PaymentPurpose::Inspection, 50000);

    expect(fn () => app(PostPayout::class)->handle($payment, $inspector->id, LedgerEntryType::InspectionFee))
        ->toThrow(InvalidLedgerPostingException::class);

    expect(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(0);
});

it('rejects ledger entry updates through model events without changing data', function () {
    $inspector = reviewInspector();
    $payment = reviewSuccessPayment(PaymentPurpose::Access, 50000);

    app(Ledger::class)->postAccessPayout($payment->id, $inspector->id, 10);

    /** @var LedgerEntry $entry */
    $entry = LedgerEntry::query()->where('payment_id', $payment->id)->firstOrFail();
    $originalAmount = (int) $entry->amount;
    $originalNote = $entry->note;

    $entry->amount = $originalAmount + 1000;

    expect(fn () => $entry->save())->toThrow(LedgerEntryImmutableException::class, null, 'LEDGER_ENTRY_IMMUTABLE');

    expect((int) $entry->fresh()->amount)->toBe($originalAmount)
        ->and($entry->fresh()->note)->toBe($originalNote);
});

it('rejects ledger entry deletes through model events without changing data', function () {
    $inspector = reviewInspector();
    $payment = reviewSuccessPayment(PaymentPurpose::Access, 50000);

    app(Ledger::class)->postAccessPayout($payment->id, $inspector->id, 10);

    /** @var LedgerEntry $entry */
    $entry = LedgerEntry::query()->where('payment_id', $payment->id)->firstOrFail();

    expect(fn () => $entry->delete())->toThrow(LedgerEntryImmutableException::class, null, 'LEDGER_ENTRY_IMMUTABLE');

    expect(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(2);
});

it('still allows creating new ledger entries while updates and deletes are blocked', function () {
    $inspector = reviewInspector();
    $payment = reviewSuccessPayment(PaymentPurpose::Access, 50000);

    app(Ledger::class)->postAccessPayout($payment->id, $inspector->id, 10);

    expect(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(2);
});

it('rejects deleting a payment that has ledger entries', function () {
    $inspector = reviewInspector();
    $payment = reviewSuccessPayment(PaymentPurpose::Access, 50000);

    app(Ledger::class)->postAccessPayout($payment->id, $inspector->id, 10);

    expect(fn () => $payment->delete())->toThrow(QueryException::class);

    expect(Payment::query()->whereKey($payment->id)->exists())->toBeTrue()
        ->and(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(2);
});

it('rejects deleting a user that owns ledger entries', function () {
    $inspector = reviewInspector();
    $payment = reviewSuccessPayment(PaymentPurpose::Access, 50000);

    app(Ledger::class)->postAccessPayout($payment->id, $inspector->id, 10);

    expect(fn () => $inspector->delete())->toThrow(QueryException::class);

    expect(User::query()->whereKey($inspector->id)->exists())->toBeTrue()
        ->and(LedgerEntry::query()->where('account_user_id', $inspector->id)->count())->toBe(1);
});
