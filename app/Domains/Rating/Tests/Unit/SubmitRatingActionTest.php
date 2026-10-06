<?php

declare(strict_types=1);

namespace App\Domains\Rating\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Listing\Models\Listing;
use App\Domains\Rating\Actions\SubmitRating;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

use function Pest\Laravel\assertDatabaseHas;

uses(TestCase::class, RefreshDatabase::class);

function createCompletedTransactionWithInspector(): array
{
    $inspectorUser = User::factory()->inspector()->create(['name' => 'Budi Santoso']);
    InspectorProfile::factory()->create([
        'user_id' => $inspectorUser->id,
        'rating_avg' => 0.00,
        'rating_count' => 0,
    ]);

    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();

    $listing = Listing::factory()->create([
        'seller_id' => $seller->id,
    ]);

    $transaction = Transaction::factory()->create([
        'listing_id' => $listing->id,
        'buyer_id' => $buyer->id,
        'inspector_id' => $inspectorUser->id,
        'status' => TransactionStatus::Completed,
        'price_amount' => 150000000,
        'completed_at' => now(),
    ]);

    return [$buyer, $transaction, $inspectorUser];
}

it('RTG-T01 buyer of completed transaction submits rating and it is saved', function () {
    Event::fake([RatingSubmitted::class]);

    [$buyer, $transaction, $inspector] = createCompletedTransactionWithInspector();

    $action = app(SubmitRating::class);
    $rating = $action->handle(
        buyer: $buyer,
        transaction: $transaction,
        score: 5,
        comment: 'Inspeksi sangat detail dan memuaskan.',
    );

    expect($rating)->toBeInstanceOf(Rating::class)
        ->and($rating->score)->toBe(5)
        ->and($rating->comment)->toBe('Inspeksi sangat detail dan memuaskan.')
        ->and($rating->buyer_id)->toBe($buyer->id)
        ->and($rating->inspector_id)->toBe($inspector->id)
        ->and($rating->transaction_id)->toBe($transaction->id)
        ->and($rating->is_hidden)->toBeFalse();

    assertDatabaseHas('ratings', [
        'id' => $rating->id,
        'transaction_id' => $transaction->id,
        'inspector_id' => $inspector->id,
        'buyer_id' => $buyer->id,
        'score' => 5,
        'is_hidden' => false,
    ]);

    Event::assertDispatched(RatingSubmitted::class, function (RatingSubmitted $event) use ($rating) {
        return $event->ratingId === $rating->id;
    });

    // Profile stats should be updated
    $profile = InspectorProfile::where('user_id', $inspector->id)->first();
    expect((float) $profile->rating_avg)->toBe(5.00)
        ->and($profile->rating_count)->toBe(1);
});

it('RTG-T02 rejected when transaction is not completed (RTG-E2)', function () {
    [$buyer, $transaction] = createCompletedTransactionWithInspector();
    $transaction->update(['status' => TransactionStatus::PaidHeld]);

    $action = app(SubmitRating::class);
    $action->handle($buyer, $transaction->fresh(), 5);
})->throws(TransactionNotCompletedException::class);

it('RTG-T02 rejected when user is not the buyer of transaction (RTG-E3)', function () {
    [, $transaction] = createCompletedTransactionWithInspector();
    $otherUser = User::factory()->user()->create();

    $action = app(SubmitRating::class);
    $action->handle($otherUser, $transaction, 5);
})->throws(UnauthorizedBuyerException::class);

it('RTG-T02 rejected when transaction has no inspector (RTG-E7)', function () {
    [$buyer, $transaction] = createCompletedTransactionWithInspector();
    $transaction->update(['inspector_id' => null]);

    $action = app(SubmitRating::class);
    $action->handle($buyer, $transaction->fresh(), 5);
})->throws(TransactionHasNoInspectorException::class);

it('RTG-T02 rejected on duplicate rating with stale instances race condition (RTG-E1)', function () {
    [$buyer, $transaction] = createCompletedTransactionWithInspector();

    $action = app(SubmitRating::class);
    $action->handle($buyer, $transaction, 4, 'Rating pertama');

    // Attempt second rating on stale instance
    $action->handle($buyer, $transaction, 5, 'Rating kedua');
})->throws(RatingAlreadySubmittedException::class);

it('RTG-T03 validates rating score range 1-5 (RTG-E4)', function (int $invalidScore) {
    [$buyer, $transaction] = createCompletedTransactionWithInspector();

    $action = app(SubmitRating::class);
    $action->handle($buyer, $transaction, $invalidScore);
})->with([0, 6, -1, 10])->throws(InvalidRatingScoreException::class);

it('RTG-T03 validates comment length and escapes HTML (RTG-E5)', function () {
    [$buyer, $transaction] = createCompletedTransactionWithInspector();

    $action = app(SubmitRating::class);

    // Comment > 500 characters
    $longComment = str_repeat('a', 501);
    expect(fn () => $action->handle($buyer, $transaction, 5, $longComment))
        ->toThrow(InvalidRatingCommentException::class);

    // The comment is stored as typed; escaping happens once, at render time (see RatingLivewireTest).
    $htmlComment = 'Layanan <script>alert("xss")</script> <b>bagus</b> & terpercaya';
    $rating = $action->handle($buyer, $transaction, 5, $htmlComment);

    expect($rating->comment)->toBe($htmlComment);
});

it('RTG-T04 rating average and count are recalculated correctly with 2 decimal rounding (RTG-E8)', function () {
    $inspectorUser = User::factory()->inspector()->create();
    InspectorProfile::factory()->create([
        'user_id' => $inspectorUser->id,
        'rating_avg' => 0.00,
        'rating_count' => 0,
    ]);

    $scores = [5, 4, 4]; // average: (5+4+4)/3 = 4.333333 -> 4.33

    foreach ($scores as $score) {
        $buyer = User::factory()->user()->create();
        $listing = Listing::factory()->create();
        $transaction = Transaction::factory()->create([
            'listing_id' => $listing->id,
            'buyer_id' => $buyer->id,
            'inspector_id' => $inspectorUser->id,
            'status' => TransactionStatus::Completed,
            'completed_at' => now(),
        ]);

        app(SubmitRating::class)->handle($buyer, $transaction, $score);
    }

    $profile = InspectorProfile::where('user_id', $inspectorUser->id)->first();
    expect((float) $profile->rating_avg)->toBe(4.33)
        ->and($profile->rating_count)->toBe(3);
});

it('RTG-T08 recalculation is consistent and safe under concurrent ratings (RTG-E9)', function () {
    $inspectorUser = User::factory()->inspector()->create();
    InspectorProfile::factory()->create([
        'user_id' => $inspectorUser->id,
        'rating_avg' => 0.00,
        'rating_count' => 0,
    ]);

    $ratingsData = [5, 3, 4, 5, 2]; // Total: 19 / 5 = 3.80

    foreach ($ratingsData as $score) {
        $buyer = User::factory()->user()->create();
        $listing = Listing::factory()->create();
        $transaction = Transaction::factory()->create([
            'listing_id' => $listing->id,
            'buyer_id' => $buyer->id,
            'inspector_id' => $inspectorUser->id,
            'status' => TransactionStatus::Completed,
            'completed_at' => now(),
        ]);

        app(SubmitRating::class)->handle($buyer, $transaction, $score);
    }

    $profile = InspectorProfile::where('user_id', $inspectorUser->id)->first();
    expect((float) $profile->rating_avg)->toBe(3.80)
        ->and($profile->rating_count)->toBe(5);
});
