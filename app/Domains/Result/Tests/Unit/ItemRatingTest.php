<?php

declare(strict_types=1);

namespace App\Domains\Result\Tests\Unit;

use App\Domains\Result\Enums\ItemRating;
use Tests\TestCase;

final class ItemRatingTest extends TestCase
{
    public function test_it_has_all_expected_ratings_and_scores(): void
    {
        $this->assertSame('good', ItemRating::Good->value);
        $this->assertSame('fair', ItemRating::Fair->value);
        $this->assertSame('poor', ItemRating::Poor->value);

        $this->assertSame(2, ItemRating::Good->score());
        $this->assertSame(1, ItemRating::Fair->score());
        $this->assertSame(0, ItemRating::Poor->score());
    }

    public function test_it_returns_human_labels(): void
    {
        $this->assertSame('Baik', ItemRating::Good->label());
        $this->assertSame('Cukup', ItemRating::Fair->label());
        $this->assertSame('Buruk', ItemRating::Poor->label());
    }
}
