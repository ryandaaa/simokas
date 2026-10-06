<?php

declare(strict_types=1);

namespace App\Domains\Rating\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Rating\Events\RatingSubmitted;
use App\Domains\Rating\Exceptions\InvalidRatingCommentException;
use App\Domains\Rating\Exceptions\InvalidRatingScoreException;
use App\Domains\Rating\Exceptions\RatingAlreadySubmittedException;
use App\Domains\Rating\Exceptions\TransactionHasNoInspectorException;
use App\Domains\Rating\Exceptions\TransactionNotCompletedException;
use App\Domains\Rating\Exceptions\UnauthorizedBuyerException;
use App\Domains\Rating\Models\Rating;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Support\Facades\DB;

/**
 * Submit rating for an inspector after transaction completion (RTG-R1 to RTG-R5, RTG-E1 to RTG-E5, RTG-E7, RTG-E9).
 */
final class SubmitRating
{
    public function __construct(
        private readonly RecalculateInspectorRating $recalculateRating,
    ) {}

    public function handle(
        User $buyer,
        Transaction $transaction,
        int $score,
        ?string $comment = null,
    ): Rating {
        if ($transaction->status !== TransactionStatus::Completed) {
            throw TransactionNotCompletedException::create();
        }

        if ((string) $transaction->buyer_id !== (string) $buyer->getKey()) {
            throw UnauthorizedBuyerException::create();
        }

        if ($transaction->inspector_id === null) {
            throw TransactionHasNoInspectorException::create();
        }

        if ($score < 1 || $score > 5) {
            throw InvalidRatingScoreException::create();
        }

        $sanitizedComment = null;
        if ($comment !== null) {
            $trimmed = trim($comment);
            if (mb_strlen($trimmed) > 500) {
                throw InvalidRatingCommentException::create();
            }
            // Disimpan apa adanya; peng-escape-an dilakukan saat ditampilkan (Blade {{ }}),
            // supaya tanda & atau kutip tidak ter-escape ganda di layar.
            $sanitizedComment = $trimmed;
        }

        if (Rating::query()->where('transaction_id', $transaction->id)->exists()) {
            throw RatingAlreadySubmittedException::create();
        }

        return DB::transaction(function () use ($transaction, $buyer, $score, $sanitizedComment): Rating {
            /** @var Transaction $lockedTx */
            $lockedTx = Transaction::query()
                ->where('id', $transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (Rating::query()->where('transaction_id', $lockedTx->id)->exists()) {
                throw RatingAlreadySubmittedException::create();
            }

            /** @var Rating $rating */
            $rating = Rating::create([
                'transaction_id' => $lockedTx->id,
                'inspector_id' => $lockedTx->inspector_id,
                'buyer_id' => (string) $buyer->getKey(),
                'score' => $score,
                'comment' => $sanitizedComment,
                'is_hidden' => false,
            ]);

            $this->recalculateRating->handle((string) $lockedTx->inspector_id);

            DB::afterCommit(static function () use ($rating): void {
                RatingSubmitted::dispatch($rating->id);
            });

            return $rating;
        });
    }
}
