<?php

declare(strict_types=1);

namespace App\Domains\Rating\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Rating\Events\RatingHidden;
use App\Domains\Rating\Models\Rating;
use Illuminate\Support\Facades\DB;

/**
 * Hide a rating (RTG-R7, RTG-E6).
 *
 * Idempotent: if already hidden, no-op and returns model.
 * Emits RatingHidden (id only) after commit.
 */
final class HideRating
{
    public function __construct(
        private readonly RecalculateInspectorRating $recalculateRating,
    ) {}

    public function handle(User $admin, Rating $rating): Rating
    {
        return DB::transaction(function () use ($rating): Rating {
            /** @var Rating $locked */
            $locked = Rating::query()
                ->where('id', $rating->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->is_hidden) {
                return $locked;
            }

            $locked->update(['is_hidden' => true]);

            $this->recalculateRating->handle((string) $locked->inspector_id);

            DB::afterCommit(static function () use ($locked): void {
                RatingHidden::dispatch($locked->id);
            });

            return $locked;
        });
    }
}
