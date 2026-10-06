<?php

declare(strict_types=1);

namespace App\Domains\Result\Tests\Unit;

use App\Domains\Result\Enums\InspectionRecommendation;
use Tests\TestCase;

final class InspectionRecommendationTest extends TestCase
{
    public function test_it_has_all_expected_cases(): void
    {
        $this->assertSame('recommended', InspectionRecommendation::Recommended->value);
        $this->assertSame('caution', InspectionRecommendation::Caution->value);
        $this->assertSame('not_recommended', InspectionRecommendation::NotRecommended->value);
    }

    public function test_it_returns_correct_labels(): void
    {
        $this->assertSame('Layak', InspectionRecommendation::Recommended->label());
        $this->assertSame('Perlu Perhatian', InspectionRecommendation::Caution->label());
        $this->assertSame('Tidak Direkomendasikan', InspectionRecommendation::NotRecommended->label());
    }

    public function test_it_returns_correct_colors(): void
    {
        $this->assertSame('success', InspectionRecommendation::Recommended->color());
        $this->assertSame('warning', InspectionRecommendation::Caution->color());
        $this->assertSame('danger', InspectionRecommendation::NotRecommended->color());
    }

    public function test_it_returns_correct_icons(): void
    {
        $this->assertSame('centang', InspectionRecommendation::Recommended->icon());
        $this->assertSame('segitiga-peringatan', InspectionRecommendation::Caution->icon());
        $this->assertSame('silang', InspectionRecommendation::NotRecommended->icon());
    }
}
