<?php

declare(strict_types=1);

use App\Domains\Listing\Enums\ListingStatus;
use Tests\TestCase;

uses(TestCase::class);

it('has expected listing status cases', function () {
    expect(ListingStatus::PendingReview->value)->toBe('pending_review')
        ->and(ListingStatus::Active->value)->toBe('active')
        ->and(ListingStatus::Rejected->value)->toBe('rejected')
        ->and(ListingStatus::InInspection->value)->toBe('in_inspection')
        ->and(ListingStatus::InTransaction->value)->toBe('in_transaction')
        ->and(ListingStatus::Sold->value)->toBe('sold')
        ->and(ListingStatus::Inactive->value)->toBe('inactive');
});

it('returns labels, colors, and icons matching UI_GUIDELINES section 4', function (ListingStatus $status, string $expectedColor, string $expectedIcon) {
    expect($status->label())->not->toBeEmpty();
    expect($status->color())->toBe($expectedColor);
    expect($status->icon())->toBe($expectedIcon);
})->with([
    [ListingStatus::PendingReview, 'warning', 'jam'],
    [ListingStatus::Active, 'success', 'centang'],
    [ListingStatus::Rejected, 'danger', 'silang'],
    [ListingStatus::InInspection, 'primary', 'kaca-pembesar'],
    [ListingStatus::InTransaction, 'primary', 'dua-panah'],
    [ListingStatus::Sold, 'muted', 'tag'],
    [ListingStatus::Inactive, 'muted', 'jeda'],
]);

it('LST-T10 correctly evaluates all state transitions', function (ListingStatus $from, ListingStatus $to, bool $expected) {
    expect($from->canTransitionTo($to))->toBe($expected);
})->with([
    // From pending_review
    'pending_review to active is valid' => [ListingStatus::PendingReview, ListingStatus::Active, true],
    'pending_review to rejected is valid' => [ListingStatus::PendingReview, ListingStatus::Rejected, true],
    'pending_review to in_inspection is invalid' => [ListingStatus::PendingReview, ListingStatus::InInspection, false],
    'pending_review to in_transaction is invalid' => [ListingStatus::PendingReview, ListingStatus::InTransaction, false],
    'pending_review to sold is invalid' => [ListingStatus::PendingReview, ListingStatus::Sold, false],
    'pending_review to inactive is invalid' => [ListingStatus::PendingReview, ListingStatus::Inactive, false],
    'pending_review to pending_review is invalid' => [ListingStatus::PendingReview, ListingStatus::PendingReview, false],

    // From rejected
    'rejected to pending_review is valid (seller fixes and resubmits)' => [ListingStatus::Rejected, ListingStatus::PendingReview, true],
    'rejected to active is invalid' => [ListingStatus::Rejected, ListingStatus::Active, false],
    'rejected to in_inspection is invalid' => [ListingStatus::Rejected, ListingStatus::InInspection, false],
    'rejected to sold is invalid' => [ListingStatus::Rejected, ListingStatus::Sold, false],

    // From active
    'active to pending_review is valid (D-05 core field edit)' => [ListingStatus::Active, ListingStatus::PendingReview, true],
    'active to in_inspection is valid (job paid)' => [ListingStatus::Active, ListingStatus::InInspection, true],
    'active to in_transaction is valid (purchase payment)' => [ListingStatus::Active, ListingStatus::InTransaction, true],
    'active to inactive is valid (deactivated)' => [ListingStatus::Active, ListingStatus::Inactive, true],
    'active to rejected is invalid' => [ListingStatus::Active, ListingStatus::Rejected, false],
    'active to sold is invalid (must go through in_transaction)' => [ListingStatus::Active, ListingStatus::Sold, false],

    // From in_inspection
    'in_inspection to active is valid (inspection completed, cancelled, or expired)' => [ListingStatus::InInspection, ListingStatus::Active, true],
    'in_inspection to sold is invalid' => [ListingStatus::InInspection, ListingStatus::Sold, false],
    'in_inspection to pending_review is invalid' => [ListingStatus::InInspection, ListingStatus::PendingReview, false],
    'in_inspection to inactive is invalid' => [ListingStatus::InInspection, ListingStatus::Inactive, false],

    // From in_transaction
    'in_transaction to active is valid (transaction cancelled)' => [ListingStatus::InTransaction, ListingStatus::Active, true],
    'in_transaction to sold is valid (delivery confirmed)' => [ListingStatus::InTransaction, ListingStatus::Sold, true],
    'in_transaction to pending_review is invalid' => [ListingStatus::InTransaction, ListingStatus::PendingReview, false],
    'in_transaction to inactive is invalid' => [ListingStatus::InTransaction, ListingStatus::Inactive, false],

    // From inactive
    'inactive to active is valid (reactivated)' => [ListingStatus::Inactive, ListingStatus::Active, true],
    'inactive to pending_review is invalid' => [ListingStatus::Inactive, ListingStatus::PendingReview, false],
    'inactive to sold is invalid' => [ListingStatus::Inactive, ListingStatus::Sold, false],

    // From sold (terminal state)
    'sold to active is invalid' => [ListingStatus::Sold, ListingStatus::Active, false],
    'sold to pending_review is invalid' => [ListingStatus::Sold, ListingStatus::PendingReview, false],
    'sold to inactive is invalid' => [ListingStatus::Sold, ListingStatus::Inactive, false],
]);

it('correctly evaluates boolean status helpers and visibility', function () {
    expect(ListingStatus::PendingReview->isPendingReview())->toBeTrue()
        ->and(ListingStatus::PendingReview->isActive())->toBeFalse()
        ->and(ListingStatus::PendingReview->isPubliclyVisible())->toBeFalse()
        ->and(ListingStatus::Active->isActive())->toBeTrue()
        ->and(ListingStatus::Active->isPubliclyVisible())->toBeTrue()
        ->and(ListingStatus::InInspection->isPubliclyVisible())->toBeTrue()
        ->and(ListingStatus::InTransaction->isPubliclyVisible())->toBeTrue()
        ->and(ListingStatus::Rejected->isRejected())->toBeTrue()
        ->and(ListingStatus::Rejected->isPubliclyVisible())->toBeFalse()
        ->and(ListingStatus::Inactive->isPubliclyVisible())->toBeFalse()
        ->and(ListingStatus::Sold->isPubliclyVisible())->toBeFalse();
});
