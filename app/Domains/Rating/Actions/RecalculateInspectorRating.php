<?php

declare(strict_types=1);

namespace App\Domains\Rating\Actions;

use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Rating\Models\Rating;

/**
 * Recalculate inspector's rating_avg and rating_count from source (RTG-R6, RTG-E8, RTG-E9).
 *
 * Executes an aggregate query over all non-hidden ratings for the inspector
 * and updates the inspector's profile row with a row lock to ensure
 * concurrency safety.
 */
final class RecalculateInspectorRating
{
    public function handle(string $inspectorUserId): void
    {
        /** @var InspectorProfile|null $profile */
        $profile = InspectorProfile::query()
            ->where('user_id', $inspectorUserId)
            ->lockForUpdate()
            ->first();

        if ($profile === null) {
            return;
        }

        $stats = Rating::query()
            ->where('inspector_id', $inspectorUserId)
            ->where('is_hidden', false)
            ->selectRaw('COUNT(*) as total_count, AVG(score) as avg_score')
            ->first();

        $count = (int) ($stats->total_count ?? 0);
        $avg = $count > 0 ? round((float) $stats->avg_score, 2) : 0.00;

        $profile->update([
            'rating_avg' => $avg,
            'rating_count' => $count,
        ]);
    }
}
