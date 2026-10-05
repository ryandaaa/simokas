<?php

declare(strict_types=1);

namespace App\Domains\Listing\Tests\Fakes;

use App\Domains\Listing\Contracts\ListingActivityChecker;

/**
 * Test double for ListingActivityChecker.
 *
 * JOB-1 and TRX-1 will replace the default binding with real
 * implementations; tests use this fake to simulate an active job
 * or an active transaction without depending on those domains.
 */
final class FakeListingActivityChecker implements ListingActivityChecker
{
    /** @var array<string, true> */
    private array $jobs = [];

    /** @var array<string, true> */
    private array $transactions = [];

    public function withActiveJob(string $listingId): self
    {
        $this->jobs[$listingId] = true;

        return $this;
    }

    public function withActiveTransaction(string $listingId): self
    {
        $this->transactions[$listingId] = true;

        return $this;
    }

    public function hasActiveJob(string $listingId): bool
    {
        return isset($this->jobs[$listingId]);
    }

    public function hasActiveTransaction(string $listingId): bool
    {
        return isset($this->transactions[$listingId]);
    }
}
