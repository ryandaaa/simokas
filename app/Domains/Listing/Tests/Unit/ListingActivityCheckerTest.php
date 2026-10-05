<?php

declare(strict_types=1);

use App\Domains\Listing\Contracts\ListingActivityChecker;
use App\Domains\Listing\Services\DefaultListingActivityChecker;
use App\Domains\Listing\Tests\Fakes\FakeListingActivityChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('binds the activity checker contract to an implementation that reports no activity when clean', function () {
    $checker = app(ListingActivityChecker::class);

    expect($checker)->toBeInstanceOf(ListingActivityChecker::class)
        ->and($checker->hasActiveJob('any-listing-id'))->toBeFalse()
        ->and($checker->hasActiveTransaction('any-listing-id'))->toBeFalse();

    $default = new DefaultListingActivityChecker;
    expect($default->hasActiveJob('any-listing-id'))->toBeFalse()
        ->and($default->hasActiveTransaction('any-listing-id'))->toBeFalse();
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
