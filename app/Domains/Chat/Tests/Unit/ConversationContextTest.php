<?php

declare(strict_types=1);

namespace App\Domains\Chat\Tests\Unit;

use App\Domains\Chat\Enums\ConversationContext;
use Tests\TestCase;

final class ConversationContextTest extends TestCase
{
    public function test_enum_values_and_labels(): void
    {
        $this->assertSame('listing', ConversationContext::Listing->value);
        $this->assertSame('job', ConversationContext::Job->value);

        $this->assertSame('Listing', ConversationContext::Listing->label());
        $this->assertSame('Inspeksi', ConversationContext::Job->label());
    }
}
