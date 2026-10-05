<?php

declare(strict_types=1);

use App\Domains\Payment\Enums\PaymentStatus;
use Tests\TestCase;

uses(TestCase::class);

it('accepts all valid state transitions in payment state machine', function (PaymentStatus $from, PaymentStatus $to) {
    expect($from->canTransitionTo($to))->toBeTrue();
})->with([
    'pending to success' => [PaymentStatus::Pending, PaymentStatus::Success],
    'pending to failed' => [PaymentStatus::Pending, PaymentStatus::Failed],
    'pending to pending' => [PaymentStatus::Pending, PaymentStatus::Pending],
    'failed to pending' => [PaymentStatus::Failed, PaymentStatus::Pending],
    'success to refunded' => [PaymentStatus::Success, PaymentStatus::Refunded],
]);

it('rejects all invalid state transitions in payment state machine', function (PaymentStatus $from, PaymentStatus $to) {
    expect($from->canTransitionTo($to))->toBeFalse();
})->with([
    'pending to refunded' => [PaymentStatus::Pending, PaymentStatus::Refunded],
    'failed to success' => [PaymentStatus::Failed, PaymentStatus::Success],
    'failed to failed' => [PaymentStatus::Failed, PaymentStatus::Failed],
    'failed to refunded' => [PaymentStatus::Failed, PaymentStatus::Refunded],
    'success to pending' => [PaymentStatus::Success, PaymentStatus::Pending],
    'success to success' => [PaymentStatus::Success, PaymentStatus::Success],
    'success to failed' => [PaymentStatus::Success, PaymentStatus::Failed],
    'refunded to pending' => [PaymentStatus::Refunded, PaymentStatus::Pending],
    'refunded to success' => [PaymentStatus::Refunded, PaymentStatus::Success],
    'refunded to failed' => [PaymentStatus::Refunded, PaymentStatus::Failed],
    'refunded to refunded' => [PaymentStatus::Refunded, PaymentStatus::Refunded],
]);

it('provides expected Indonesian labels for payment statuses', function (PaymentStatus $status, string $expectedLabel) {
    expect($status->label())->toBe($expectedLabel);
})->with([
    [PaymentStatus::Pending, 'Menunggu Pembayaran'],
    [PaymentStatus::Success, 'Berhasil'],
    [PaymentStatus::Failed, 'Gagal'],
    [PaymentStatus::Refunded, 'Dikembalikan'],
]);

it('provides UI guideline variants and icons for payment statuses', function (PaymentStatus $status, string $expectedColor, string $expectedIcon) {
    expect($status->color())->toBe($expectedColor)
        ->and($status->icon())->toBe($expectedIcon);
})->with([
    [PaymentStatus::Pending, 'warning', 'jam'],
    [PaymentStatus::Success, 'success', 'centang'],
    [PaymentStatus::Failed, 'danger', 'silang'],
    [PaymentStatus::Refunded, 'muted', 'dua-panah'],
]);

it('correctly reports status check helper methods', function () {
    expect(PaymentStatus::Pending->isPending())->toBeTrue()
        ->and(PaymentStatus::Pending->isSuccess())->toBeFalse()
        ->and(PaymentStatus::Success->isSuccess())->toBeTrue()
        ->and(PaymentStatus::Failed->isFailed())->toBeTrue()
        ->and(PaymentStatus::Refunded->isRefunded())->toBeTrue();
});
