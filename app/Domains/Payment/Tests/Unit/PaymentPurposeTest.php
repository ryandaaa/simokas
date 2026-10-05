<?php

declare(strict_types=1);

use App\Domains\Payment\Enums\PaymentPurpose;
use Tests\TestCase;

uses(TestCase::class);

it('provides expected labels for payment purposes', function (PaymentPurpose $purpose, string $expectedLabel) {
    expect($purpose->label())->toBe($expectedLabel);
})->with([
    [PaymentPurpose::Inspection, 'Biaya Inspeksi'],
    [PaymentPurpose::Access, 'Akses Hasil Laporan'],
    [PaymentPurpose::Purchase, 'Pembelian Kendaraan'],
]);
