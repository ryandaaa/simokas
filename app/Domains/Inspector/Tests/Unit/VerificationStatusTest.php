<?php

declare(strict_types=1);

use App\Domains\Inspector\Enums\VerificationStatus;
use Tests\TestCase;

uses(TestCase::class);

it('has expected verification status cases', function () {
    expect(VerificationStatus::Pending->value)->toBe('pending')
        ->and(VerificationStatus::Approved->value)->toBe('approved')
        ->and(VerificationStatus::Rejected->value)->toBe('rejected');
});

it('returns labels, colors, and icons for all verification statuses', function (VerificationStatus $status) {
    expect($status->label())->not->toBeEmpty();
    expect(['warning', 'success', 'danger'])->toContain($status->color());
    expect($status->icon())->not->toBeEmpty();
})->with(VerificationStatus::cases());

it('ONB-T09 correctly evaluates all state transitions', function (VerificationStatus $from, VerificationStatus $to, bool $expected) {
    expect($from->canTransitionTo($to))->toBe($expected);
})->with([
    'pending to approved is valid' => [VerificationStatus::Pending, VerificationStatus::Approved, true],
    'pending to rejected is valid' => [VerificationStatus::Pending, VerificationStatus::Rejected, true],
    'pending to pending is invalid' => [VerificationStatus::Pending, VerificationStatus::Pending, false],

    'approved to pending is invalid' => [VerificationStatus::Approved, VerificationStatus::Pending, false],
    'approved to rejected is invalid' => [VerificationStatus::Approved, VerificationStatus::Rejected, false],
    'approved to approved is invalid' => [VerificationStatus::Approved, VerificationStatus::Approved, false],

    'rejected to pending is valid (re-submission)' => [VerificationStatus::Rejected, VerificationStatus::Pending, true],
    'rejected to approved is invalid (must go via pending)' => [VerificationStatus::Rejected, VerificationStatus::Approved, false],
    'rejected to rejected is invalid' => [VerificationStatus::Rejected, VerificationStatus::Rejected, false],
]);

it('identifies status correctly with boolean helpers', function () {
    expect(VerificationStatus::Pending->isPending())->toBeTrue()
        ->and(VerificationStatus::Pending->isApproved())->toBeFalse()
        ->and(VerificationStatus::Pending->isRejected())->toBeFalse()
        ->and(VerificationStatus::Approved->isApproved())->toBeTrue()
        ->and(VerificationStatus::Approved->isPending())->toBeFalse()
        ->and(VerificationStatus::Approved->isRejected())->toBeFalse()
        ->and(VerificationStatus::Rejected->isRejected())->toBeTrue()
        ->and(VerificationStatus::Rejected->isPending())->toBeFalse()
        ->and(VerificationStatus::Rejected->isApproved())->toBeFalse();
});
