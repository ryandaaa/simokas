<?php

declare(strict_types=1);

use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Exceptions\InvalidListingStatusTransitionException;
use App\Domains\Listing\Exceptions\ListingValidationException;
use App\Domains\Listing\Exceptions\PhotoUploadException;
use Tests\TestCase;

uses(TestCase::class);

it('formats InvalidListingStatusTransitionException properly', function () {
    $exception = InvalidListingStatusTransitionException::fromStatus(ListingStatus::PendingReview, ListingStatus::Sold);

    expect($exception->getMessage())->toContain('pending_review')
        ->and($exception->getMessage())->toContain('sold')
        ->and($exception->statusCode())->toBe(422);
});

it('formats ListingValidationException properly', function () {
    $slotRequired = ListingValidationException::slotsRequired();
    $slotPast = ListingValidationException::slotInPast();
    $invalidSeller = ListingValidationException::invalidSeller();
    $inspectorForbidden = ListingValidationException::inspectorForbidden();
    $adminForbidden = ListingValidationException::adminForbidden();

    expect($slotRequired->statusCode())->toBe(422)
        ->and($slotPast->statusCode())->toBe(422)
        ->and($invalidSeller->statusCode())->toBe(403)
        ->and($inspectorForbidden->statusCode())->toBe(403)
        ->and($adminForbidden->statusCode())->toBe(403);
});

it('formats PhotoUploadException properly', function () {
    $count = PhotoUploadException::countInvalid(2);
    $large = PhotoUploadException::fileTooLarge(2048);
    $unsupported = PhotoUploadException::unsupportedType('image/gif');
    $oneCover = PhotoUploadException::oneCoverRequired();

    expect($count->statusCode())->toBe(422)
        ->and($large->statusCode())->toBe(413)
        ->and($unsupported->statusCode())->toBe(415)
        ->and($oneCover->statusCode())->toBe(422);
});
