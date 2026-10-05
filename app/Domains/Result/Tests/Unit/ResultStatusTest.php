<?php

declare(strict_types=1);

namespace App\Domains\Result\Tests\Unit;

use App\Domains\Result\Enums\ResultStatus;
use Tests\TestCase;

final class ResultStatusTest extends TestCase
{
    public function test_it_has_expected_cases(): void
    {
        $this->assertSame('draft', ResultStatus::Draft->value);
        $this->assertSame('submitted', ResultStatus::Submitted->value);
    }

    public function test_it_provides_labels(): void
    {
        $this->assertSame('Draf', ResultStatus::Draft->label());
        $this->assertSame('Dikirim', ResultStatus::Submitted->label());
    }

    public function test_it_checks_status_helpers(): void
    {
        $this->assertTrue(ResultStatus::Draft->isDraft());
        $this->assertFalse(ResultStatus::Draft->isSubmitted());

        $this->assertTrue(ResultStatus::Submitted->isSubmitted());
        $this->assertFalse(ResultStatus::Submitted->isDraft());
    }

    public function test_it_validates_transitions(): void
    {
        $this->assertTrue(ResultStatus::Draft->canTransitionTo(ResultStatus::Submitted));
        $this->assertFalse(ResultStatus::Draft->canTransitionTo(ResultStatus::Draft));
        $this->assertFalse(ResultStatus::Submitted->canTransitionTo(ResultStatus::Draft));
    }
}
