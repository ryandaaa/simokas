<?php

declare(strict_types=1);

use App\Domains\Payment\Enums\LedgerEntryType;
use Tests\TestCase;

uses(TestCase::class);

it('defines the ledger entry types from PRD 18.12', function () {
    expect(LedgerEntryType::cases())->toHaveCount(5)
        ->and(array_map(fn (LedgerEntryType $t): string => $t->value, LedgerEntryType::cases()))
        ->toBe(['inspection_fee', 'access_fee', 'sale_commission', 'platform_fee', 'refund']);
});

it('provides Indonesian labels, colors and icons for every entry type', function (LedgerEntryType $type) {
    expect($type->label())->not->toBeEmpty();
    expect(['success', 'warning', 'danger', 'primary', 'muted'])->toContain($type->color());
    expect($type->icon())->not->toBeEmpty();
})->with('ledger entry types');

dataset('ledger entry types', [
    'inspection_fee' => [LedgerEntryType::InspectionFee],
    'access_fee' => [LedgerEntryType::AccessFee],
    'sale_commission' => [LedgerEntryType::SaleCommission],
    'platform_fee' => [LedgerEntryType::PlatformFee],
    'refund' => [LedgerEntryType::Refund],
]);
