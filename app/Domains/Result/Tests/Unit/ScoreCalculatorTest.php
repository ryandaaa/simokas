<?php

declare(strict_types=1);

namespace App\Domains\Result\Tests\Unit;

use App\Domains\Result\Enums\InspectionRecommendation;
use App\Domains\Result\Enums\ItemRating;
use App\Domains\Result\Services\ScoreCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class ScoreCalculatorTest extends TestCase
{
    private ScoreCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new ScoreCalculator;
    }

    public function test_it_calculates_example_62_point_5_correctly(): void
    {
        // 4 items with weight 1 (max score 8): Baik (2), Baik (2), Cukup (1), Buruk (0)
        // Sum = 5, score = 62.5, recommendation caution
        $items = [
            ['weight' => 1, 'rating' => ItemRating::Good, 'is_critical' => false],
            ['weight' => 1, 'rating' => ItemRating::Good, 'is_critical' => false],
            ['weight' => 1, 'rating' => ItemRating::Fair, 'is_critical' => false],
            ['weight' => 1, 'rating' => ItemRating::Poor, 'is_critical' => false],
        ];

        $result = $this->calculator->calculate($items);

        $this->assertSame(62.5, $result->totalScore);
        $this->assertSame(InspectionRecommendation::Caution, $result->recommendation);
        $this->assertFalse($result->hasCriticalFailure);
        $this->assertSame(5, $result->actualWeightedSum);
        $this->assertSame(8, $result->maxWeightedSum);
    }

    #[DataProvider('boundaryScoreDataset')]
    public function test_res_t06_it_evaluates_score_boundaries_with_dataset(
        array $items,
        float $expectedScore,
        InspectionRecommendation $expectedRecommendation
    ): void {
        $result = $this->calculator->calculate($items);

        $this->assertSame($expectedScore, $result->totalScore);
        $this->assertSame($expectedRecommendation, $result->recommendation);
    }

    public static function boundaryScoreDataset(): array
    {
        return [
            // Exact 100 -> recommended
            '100% perfect' => [
                [
                    ['weight' => 1, 'rating' => ItemRating::Good, 'is_critical' => false],
                ],
                100.0,
                InspectionRecommendation::Recommended,
            ],

            // Exact 80.0 boundary -> recommended
            // 5 items weight 1 (max 10): 4 Baik (8), 1 Buruk (0) = 8/10 * 100 = 80.0
            '80.0 boundary (recommended)' => [
                [
                    ['weight' => 1, 'rating' => ItemRating::Good, 'is_critical' => false],
                    ['weight' => 1, 'rating' => ItemRating::Good, 'is_critical' => false],
                    ['weight' => 1, 'rating' => ItemRating::Good, 'is_critical' => false],
                    ['weight' => 1, 'rating' => ItemRating::Good, 'is_critical' => false],
                    ['weight' => 1, 'rating' => ItemRating::Poor, 'is_critical' => false],
                ],
                80.0,
                InspectionRecommendation::Recommended,
            ],

            // 79.0 boundary -> caution
            // 100 items weight 1 (max 200): 79 Baik (158), 21 Buruk (0) = 158/200 * 100 = 79.0
            '79.0 boundary (caution)' => [
                array_merge(
                    array_fill(0, 79, ['weight' => 1, 'rating' => ItemRating::Good, 'is_critical' => false]),
                    array_fill(0, 21, ['weight' => 1, 'rating' => ItemRating::Poor, 'is_critical' => false]),
                ),
                79.0,
                InspectionRecommendation::Caution,
            ],

            // Exact 60.0 boundary -> caution
            // 5 items weight 1 (max 10): 3 Baik (6), 2 Buruk (0) = 6/10 * 100 = 60.0
            '60.0 boundary (caution)' => [
                [
                    ['weight' => 1, 'rating' => ItemRating::Good, 'is_critical' => false],
                    ['weight' => 1, 'rating' => ItemRating::Good, 'is_critical' => false],
                    ['weight' => 1, 'rating' => ItemRating::Good, 'is_critical' => false],
                    ['weight' => 1, 'rating' => ItemRating::Poor, 'is_critical' => false],
                    ['weight' => 1, 'rating' => ItemRating::Poor, 'is_critical' => false],
                ],
                60.0,
                InspectionRecommendation::Caution,
            ],

            // 59.0 boundary -> not_recommended
            // 100 items weight 1 (max 200): 59 Baik (118), 41 Buruk (0) = 118/200 * 100 = 59.0
            '59.0 boundary (not_recommended)' => [
                array_merge(
                    array_fill(0, 59, ['weight' => 1, 'rating' => ItemRating::Good, 'is_critical' => false]),
                    array_fill(0, 41, ['weight' => 1, 'rating' => ItemRating::Poor, 'is_critical' => false]),
                ),
                59.0,
                InspectionRecommendation::NotRecommended,
            ],

            // Exact 0.0 -> not_recommended
            '0.0 all poor' => [
                [
                    ['weight' => 1, 'rating' => ItemRating::Poor, 'is_critical' => false],
                ],
                0.0,
                InspectionRecommendation::NotRecommended,
            ],
        ];
    }

    public function test_res_t07_critical_item_rated_poor_forces_not_recommended_regardless_of_high_score(): void
    {
        // 10 items weight 1: 9 Good (18), 1 Critical Poor (0).
        // Score = 18/20 * 100 = 90.0 (normally recommended), but critical poor forces not_recommended!
        $items = [
            ['weight' => 1, 'rating' => ItemRating::Good, 'is_critical' => false],
            ['weight' => 1, 'rating' => ItemRating::Good, 'is_critical' => false],
            ['weight' => 1, 'rating' => ItemRating::Good, 'is_critical' => false],
            ['weight' => 1, 'rating' => ItemRating::Good, 'is_critical' => false],
            ['weight' => 1, 'rating' => ItemRating::Good, 'is_critical' => false],
            ['weight' => 1, 'rating' => ItemRating::Good, 'is_critical' => false],
            ['weight' => 1, 'rating' => ItemRating::Good, 'is_critical' => false],
            ['weight' => 1, 'rating' => ItemRating::Good, 'is_critical' => false],
            ['weight' => 1, 'rating' => ItemRating::Good, 'is_critical' => false],
            ['weight' => 1, 'rating' => ItemRating::Poor, 'is_critical' => true],
        ];

        $result = $this->calculator->calculate($items);

        $this->assertSame(90.0, $result->totalScore);
        $this->assertSame(InspectionRecommendation::NotRecommended, $result->recommendation);
        $this->assertTrue($result->hasCriticalFailure);
    }

    public function test_critical_item_rated_fair_or_good_does_not_force_not_recommended(): void
    {
        // 5 items: 4 Good, 1 Critical Fair.
        // Sum = 8 + 1 = 9 / 10 = 90.0 -> recommended!
        $items = [
            ['weight' => 1, 'rating' => ItemRating::Good, 'is_critical' => false],
            ['weight' => 1, 'rating' => ItemRating::Good, 'is_critical' => false],
            ['weight' => 1, 'rating' => ItemRating::Good, 'is_critical' => false],
            ['weight' => 1, 'rating' => ItemRating::Good, 'is_critical' => false],
            ['weight' => 1, 'rating' => ItemRating::Fair, 'is_critical' => true],
        ];

        $result = $this->calculator->calculate($items);

        $this->assertSame(90.0, $result->totalScore);
        $this->assertSame(InspectionRecommendation::Recommended, $result->recommendation);
        $this->assertFalse($result->hasCriticalFailure);
    }

    public function test_it_handles_weights_and_string_ratings(): void
    {
        // Item 1: weight 3, rating 'good' (value 2 * 3 = 6, max 6)
        // Item 2: weight 2, rating 'fair' (value 1 * 2 = 2, max 4)
        // Item 3: weight 1, rating 'poor' (value 0 * 1 = 0, max 2)
        // Sum = 8, max = 12. Score = 8/12 * 100 = 66.67, caution
        $items = [
            ['weight' => 3, 'rating' => 'good', 'is_critical' => false],
            ['weight' => 2, 'rating' => 'fair', 'is_critical' => false],
            ['weight' => 1, 'rating' => 'poor', 'is_critical' => false],
        ];

        $result = $this->calculator->calculate($items);

        $this->assertSame(66.67, $result->totalScore);
        $this->assertSame(InspectionRecommendation::Caution, $result->recommendation);
    }

    public function test_it_handles_objects_and_defaults(): void
    {
        $item1 = (object) ['weight' => 2, 'rating' => 'good', 'is_critical' => false];
        $item2 = (object) ['weight' => null, 'rating' => null, 'is_critical' => null]; // defaults weight 1, value 0

        $result = $this->calculator->calculate([$item1, $item2]);

        // Max = (2*2) + (1*2) = 6. Actual = (2*2) + 0 = 4. Score = 4/6 * 100 = 66.67
        $this->assertSame(66.67, $result->totalScore);
        $this->assertSame(InspectionRecommendation::Caution, $result->recommendation);
    }

    public function test_it_handles_empty_items_collection(): void
    {
        $result = $this->calculator->calculate([]);

        $this->assertSame(0.0, $result->totalScore);
        $this->assertSame(InspectionRecommendation::NotRecommended, $result->recommendation);
        $this->assertFalse($result->hasCriticalFailure);
    }

    public function test_it_resolves_invalid_or_negative_weight_to_one(): void
    {
        $items = [
            ['weight' => -5, 'rating' => 'good'],
            ['weight' => 0, 'rating' => 'fair'],
            'raw_string_item',
        ];

        $result = $this->calculator->calculate($items);

        // All 3 items resolve weight to 1.
        // Item 1: Good (2/2)
        // Item 2: Fair (1/2)
        // Item 3: Unknown rating (0/2)
        // Actual = 2 + 1 + 0 = 3, Max = 6. Score = 50.0 -> not_recommended
        $this->assertSame(50.0, $result->totalScore);
        $this->assertSame(InspectionRecommendation::NotRecommended, $result->recommendation);
    }
}
