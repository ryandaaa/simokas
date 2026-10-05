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

/**
 * Full from-to matrix for docs/flows/listing.md section 1.
 * Every valid pair is accepted, every other pair is rejected.
 *
 * @return array<string, array{ListingStatus, ListingStatus, bool}>
 */
function listingStatusMatrixDataset(): array
{
    $valid = [
        ListingStatus::PendingReview->value => [ListingStatus::Active, ListingStatus::Rejected],
        ListingStatus::Rejected->value => [ListingStatus::PendingReview],
        ListingStatus::Active->value => [ListingStatus::PendingReview, ListingStatus::InInspection, ListingStatus::InTransaction, ListingStatus::Inactive],
        ListingStatus::InInspection->value => [ListingStatus::Active],
        ListingStatus::InTransaction->value => [ListingStatus::Active, ListingStatus::Sold],
        ListingStatus::Inactive->value => [ListingStatus::Active],
        ListingStatus::Sold->value => [],
    ];

    $dataset = [];
    foreach (ListingStatus::cases() as $from) {
        foreach (ListingStatus::cases() as $to) {
            $expected = in_array($to, $valid[$from->value], true);
            $dataset["{$from->value} to {$to->value} is ".($expected ? 'valid' : 'invalid')] = [$from, $to, $expected];
        }
    }

    return $dataset;
}

it('LST-T10 accepts every valid transition and rejects every other pair', function (ListingStatus $from, ListingStatus $to, bool $expected) {
    expect($from->canTransitionTo($to))->toBe($expected);
})->with(fn (): array => listingStatusMatrixDataset());

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

it('LST-T17 marks active, in_inspection, in_transaction, and sold as photo-public', function (ListingStatus $status, bool $expected) {
    expect($status->isPhotoPubliclyVisible())->toBe($expected);
})->with([
    'pending_review is not photo-public' => [ListingStatus::PendingReview, false],
    'active is photo-public' => [ListingStatus::Active, true],
    'rejected is not photo-public' => [ListingStatus::Rejected, false],
    'in_inspection is photo-public' => [ListingStatus::InInspection, true],
    'in_transaction is photo-public' => [ListingStatus::InTransaction, true],
    'sold is photo-public [USULAN]' => [ListingStatus::Sold, true],
    'inactive is not photo-public' => [ListingStatus::Inactive, false],
]);
