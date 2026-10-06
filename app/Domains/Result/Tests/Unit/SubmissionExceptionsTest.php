<?php

declare(strict_types=1);

namespace App\Domains\Result\Tests\Unit;

use App\Domains\Result\Exceptions\MinPhotosNotMetException;
use App\Domains\Result\Exceptions\ReopenReasonRequiredException;
use App\Domains\Result\Exceptions\RequiredItemsMissingException;
use Tests\TestCase;

final class SubmissionExceptionsTest extends TestCase
{
    public function test_it_creates_required_items_missing_exception(): void
    {
        $missing = [
            ['id' => 'item-uuid-1', 'label' => 'STNK'],
            ['id' => 'item-uuid-2', 'label' => 'BPKB'],
        ];

        $exception = RequiredItemsMissingException::withItems($missing);

        $this->assertSame(422, $exception->getStatusCode());
        $this->assertSame('REQUIRED_ITEMS_MISSING', $exception->getErrorCode());
        $this->assertSame($missing, $exception->getMissingItems());
    }

    public function test_it_creates_min_photos_not_met_exception(): void
    {
        $missingPhotos = [
            ['id' => 'item-uuid-3', 'label' => 'Bodi'],
        ];

        $exception = MinPhotosNotMetException::withItems($missingPhotos);

        $this->assertSame(422, $exception->getStatusCode());
        $this->assertSame('MIN_PHOTOS_NOT_MET', $exception->getErrorCode());
        $this->assertSame($missingPhotos, $exception->getMissingPhotoItems());
    }

    public function test_it_creates_reopen_reason_required_exception(): void
    {
        $exception = ReopenReasonRequiredException::create();

        $this->assertSame(422, $exception->getStatusCode());
        $this->assertSame('REOPEN_REASON_REQUIRED', $exception->getErrorCode());
    }
}
