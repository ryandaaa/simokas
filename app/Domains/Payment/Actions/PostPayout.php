<?php

declare(strict_types=1);

namespace App\Domains\Payment\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Exceptions\InvalidLedgerPostingException;
use App\Domains\Payment\Exceptions\LedgerAlreadyPostedException;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use App\Domains\Setting\Contracts\SettingsReader;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PostPayout
{
    public function __construct(private readonly SettingsReader $settings) {}

    public function handle(Payment $payment, string $inspectorId, LedgerEntryType $creditType, ?int $platformFeePct = null): void
    {
        DB::transaction(function () use ($payment, $inspectorId, $creditType, $platformFeePct) {
            /** @var Payment $fresh */
            $fresh = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($fresh->status !== PaymentStatus::Success) {
                throw InvalidLedgerPostingException::notSuccess($fresh->status);
            }

            $this->ensurePairMatches($fresh->purpose, $creditType);

            /** @var User|null $inspector */
            $inspector = User::query()->whereKey($inspectorId)->first();

            if ($inspector === null || ! $inspector->isInspector()) {
                throw InvalidLedgerPostingException::invalidInspector($inspectorId);
            }

            if (LedgerEntry::query()->where('payment_id', $fresh->id)->exists()) {
                throw LedgerAlreadyPostedException::forPayment($fresh->id);
            }

            if ($creditType === LedgerEntryType::InspectionFee && $platformFeePct === null) {
                throw InvalidLedgerPostingException::snapshotRequired();
            }

            $pct = $platformFeePct ?? $this->settings->getPlatformFeePct();

            if ($pct < 0 || $pct > 100) {
                throw InvalidLedgerPostingException::invalidPercentage($pct);
            }

            $split = Money::split((int) $fresh->amount, $pct);
            $now = now();

            // Single statement so a mid-operation failure can never leave one entry behind.
            LedgerEntry::query()->insert([
                [
                    'id' => (string) Str::uuid(),
                    'payment_id' => $fresh->id,
                    'account_user_id' => null,
                    'entry_type' => LedgerEntryType::PlatformFee->value,
                    'amount' => $split['platform'],
                    'note' => __('payment.ledger.notes.platform_fee', ['pct' => $pct]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'id' => (string) Str::uuid(),
                    'payment_id' => $fresh->id,
                    'account_user_id' => $inspector->id,
                    'entry_type' => $creditType->value,
                    'amount' => $split['recipient'],
                    'note' => __('payment.ledger.notes.payout', ['type' => $creditType->label(), 'pct' => $pct]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        });
    }

    private function ensurePairMatches(PaymentPurpose $purpose, LedgerEntryType $creditType): void
    {
        $matches = ($purpose === PaymentPurpose::Inspection && $creditType === LedgerEntryType::InspectionFee)
            || ($purpose === PaymentPurpose::Access && $creditType === LedgerEntryType::AccessFee);

        if (! $matches) {
            throw InvalidLedgerPostingException::purposeMismatch($purpose->value, $creditType->value);
        }
    }
}
