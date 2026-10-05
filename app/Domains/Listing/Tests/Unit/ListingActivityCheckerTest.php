<?php

declare(strict_types=1);

use App\Domains\Listing\Contracts\ListingActivityChecker;
use App\Domains\Listing\Services\DefaultListingActivityChecker;
use App\Domains\Listing\Tests\Fakes\FakeListingActivityChecker;
use Tests\TestCase;

uses(TestCase::class);

it('binds the activity checker contract to a default that reports no activity', function () {
    $checker = app(ListingActivityChecker::class);

    expect($checker)->toBeInstanceOf(DefaultListingActivityChecker::class)
        ->and($checker->hasActiveJob('any-listing-id'))->toBeFalse()
        ->and($checker->hasActiveTransaction('any-listing-id'))->toBeFalse();
});

it('locks listing edits when the fake reports an active job or transaction', function () {
    $checker = new FakeListingActivityChecker;
    app()->instance(ListingActivityChecker::class, $checker);

    expect(app(ListingActivityChecker::class)->hasActiveJob('listing-1'))->toBeFalse();

    $checker->withActiveJob('listing-1')->withActiveTransaction('listing-2');

    expect(app(ListingActivityChecker::class)->hasActiveJob('listing-1'))->toBeTrue()
        ->and(app(ListingActivityChecker::class)->hasActiveTransaction('listing-1'))->toBeFalse()
        ->and(app(ListingActivityChecker::class)->hasActiveTransaction('listing-2'))->toBeTrue();
});
