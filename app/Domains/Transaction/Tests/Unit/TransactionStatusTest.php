<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Tests\Unit;

use App\Domains\Transaction\Enums\TransactionStatus;

it('TRX-T13 allows valid status transitions per state machine', function (TransactionStatus $from, TransactionStatus $to) {
    expect($from->canTransitionTo($to))->toBeTrue();
})->with([
    [TransactionStatus::Offered, TransactionStatus::Accepted],
    [TransactionStatus::Offered, TransactionStatus::Rejected],
    [TransactionStatus::Offered, TransactionStatus::Cancelled],
    [TransactionStatus::Offered, TransactionStatus::Expired],
    [TransactionStatus::Accepted, TransactionStatus::PaidHeld],
    [TransactionStatus::Accepted, TransactionStatus::Cancelled],
    [TransactionStatus::Accepted, TransactionStatus::Expired],
    [TransactionStatus::PaidHeld, TransactionStatus::Completed],
    [TransactionStatus::PaidHeld, TransactionStatus::Cancelled],
]);

it('TRX-T13 forbids invalid status transitions', function (TransactionStatus $from, TransactionStatus $to) {
    expect($from->canTransitionTo($to))->toBeFalse();
})->with([
    [TransactionStatus::Offered, TransactionStatus::PaidHeld],
    [TransactionStatus::Offered, TransactionStatus::Completed],
    [TransactionStatus::Accepted, TransactionStatus::Rejected],
    [TransactionStatus::Accepted, TransactionStatus::Offered],
    [TransactionStatus::PaidHeld, TransactionStatus::Accepted],
    [TransactionStatus::PaidHeld, TransactionStatus::Rejected],
    [TransactionStatus::Completed, TransactionStatus::Accepted],
    [TransactionStatus::Rejected, TransactionStatus::Accepted],
    [TransactionStatus::Cancelled, TransactionStatus::Accepted],
    [TransactionStatus::Expired, TransactionStatus::Accepted],
]);

it('identifies final statuses correctly', function (TransactionStatus $status, bool $expected) {
    expect($status->isFinal())->toBe($expected);
})->with([
    [TransactionStatus::Completed, true],
    [TransactionStatus::Rejected, true],
    [TransactionStatus::Cancelled, true],
    [TransactionStatus::Expired, true],
    [TransactionStatus::Offered, false],
    [TransactionStatus::Accepted, false],
    [TransactionStatus::PaidHeld, false],
]);

it('identifies active statuses correctly', function (TransactionStatus $status, bool $expected) {
    expect($status->isActive())->toBe($expected);
})->with([
    [TransactionStatus::Offered, true],
    [TransactionStatus::Accepted, true],
    [TransactionStatus::PaidHeld, true],
    [TransactionStatus::Completed, false],
    [TransactionStatus::Rejected, false],
    [TransactionStatus::Cancelled, false],
    [TransactionStatus::Expired, false],
]);
