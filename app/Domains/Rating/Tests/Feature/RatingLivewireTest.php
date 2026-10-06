<?php

declare(strict_types=1);

namespace App\Domains\Rating\Tests\Feature;

use App\Domains\Dashboard\Livewire\InspectorDashboard;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Listing\Models\Listing;
use App\Domains\Rating\Livewire\AdminRatingList;
use App\Domains\Rating\Livewire\RatingForm;
use App\Domains\Rating\Models\Rating;
use App\Domains\Result\Enums\InspectionRecommendation;
use App\Domains\Result\Enums\ResultStatus;
use App\Domains\Result\Livewire\ViewResultPage;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\InspectionTemplate;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

it('RTG-T01 Livewire RatingForm submits valid rating and displays success', function () {
    $inspectorUser = User::factory()->inspector()->create(['name' => 'Budi Inspektor']);
    InspectorProfile::factory()->create([
        'user_id' => $inspectorUser->id,
        'rating_avg' => 0.00,
        'rating_count' => 0,
    ]);

    $buyer = User::factory()->user()->create();
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->create(['seller_id' => $seller->id]);

    $transaction = Transaction::factory()->create([
        'listing_id' => $listing->id,
        'buyer_id' => $buyer->id,
        'inspector_id' => $inspectorUser->id,
        'status' => TransactionStatus::Completed,
        'completed_at' => now(),
    ]);

    Livewire::actingAs($buyer)
        ->test(RatingForm::class, ['transactionId' => $transaction->id])
        ->assertSee('Budi Inspektor')
        ->set('score', 5)
        ->set('comment', 'Sangat memuaskan dan profesional!')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSee(__('rating.success'))
        ->assertSee('5 / 5');

    assertDatabaseHas('ratings', [
        'transaction_id' => $transaction->id,
        'inspector_id' => $inspectorUser->id,
        'buyer_id' => $buyer->id,
        'score' => 5,
        'comment' => 'Sangat memuaskan dan profesional!',
        'is_hidden' => false,
    ]);
});

it('RTG-T06 Livewire RatingForm shows submitted rating and disables resubmission', function () {
    $inspectorUser = User::factory()->inspector()->create();
    $buyer = User::factory()->user()->create();
    $transaction = Transaction::factory()->create([
        'buyer_id' => $buyer->id,
        'inspector_id' => $inspectorUser->id,
        'status' => TransactionStatus::Completed,
    ]);

    Rating::factory()->create([
        'transaction_id' => $transaction->id,
        'buyer_id' => $buyer->id,
        'inspector_id' => $inspectorUser->id,
        'score' => 4,
        'comment' => 'Ulasan yang sudah ada',
    ]);

    Livewire::actingAs($buyer)
        ->test(RatingForm::class, ['transactionId' => $transaction->id])
        ->assertSee('4 / 5')
        ->assertSee('Ulasan yang sudah ada')
        ->assertSee(__('rating.cannot_modify'))
        ->assertDontSee(__('rating.submit'));
});

it('RTG-T07 rating appears on inspection result and inspector dashboard', function () {
    // 1. Setup inspector with rating
    $inspectorUser = User::factory()->inspector()->create(['name' => 'Doni Saputra']);
    InspectorProfile::factory()->create([
        'user_id' => $inspectorUser->id,
        'rating_avg' => 4.80,
        'rating_count' => 5,
    ]);

    // Check InspectorDashboard
    Livewire::actingAs($inspectorUser)
        ->test(InspectorDashboard::class, ['user' => $inspectorUser])
        ->assertSee('4.80')
        ->assertSee('5 ulasan');

    // 2. Setup inspection result
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->create([
        'seller_id' => $seller->id,
        'is_inspected' => true,
    ]);

    $template = InspectionTemplate::factory()->create();
    $job = InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'requester_id' => $seller->id,
        'inspector_id' => $inspectorUser->id,
        'status' => JobStatus::Completed,
    ]);

    InspectionResult::factory()->create([
        'job_id' => $job->id,
        'inspector_id' => $inspectorUser->id,
        'template_id' => $template->id,
        'status' => ResultStatus::Submitted,
        'recommendation' => InspectionRecommendation::Recommended,
        'total_score' => 90,
        'submitted_at' => now(),
    ]);

    // View Result page for seller (has access)
    Livewire::actingAs($seller)
        ->test(ViewResultPage::class, ['listing' => $listing])
        ->assertSee('Doni Saputra')
        ->assertSee('4.80')
        ->assertSee('(5)');
});

it('admin can list, filter, hide, and unhide ratings in AdminRatingList', function () {
    $admin = User::factory()->admin()->create();
    $inspectorUser = User::factory()->inspector()->create(['name' => 'Ahmad Inspektor']);
    InspectorProfile::factory()->create([
        'user_id' => $inspectorUser->id,
        'rating_avg' => 5.00,
        'rating_count' => 1,
    ]);

    $rating = Rating::factory()->create([
        'inspector_id' => $inspectorUser->id,
        'score' => 5,
        'comment' => 'Komentar mengandung promosi spam',
        'is_hidden' => false,
    ]);

    Livewire::actingAs($admin)
        ->test(AdminRatingList::class)
        ->assertSee('Ahmad Inspektor')
        ->assertSee('Komentar mengandung promosi spam')
        ->call('hide', $rating->id)
        ->assertSee(__('rating.admin.hidden_success'));

    expect($rating->fresh()->is_hidden)->toBeTrue();

    // Now unhide
    Livewire::actingAs($admin)
        ->test(AdminRatingList::class)
        ->call('unhide', $rating->id)
        ->assertSee(__('rating.admin.unhidden_success'));

    expect($rating->fresh()->is_hidden)->toBeFalse();
});

it('RTG-E5 rating comment is escaped exactly once when rendered (no raw HTML, no double escaping)', function () {
    $inspectorUser = User::factory()->inspector()->create();
    InspectorProfile::factory()->create([
        'user_id' => $inspectorUser->id,
        'rating_avg' => 0.00,
        'rating_count' => 0,
    ]);
    $buyer = User::factory()->user()->create();
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->create(['seller_id' => $seller->id]);
    $transaction = Transaction::factory()->create([
        'listing_id' => $listing->id,
        'buyer_id' => $buyer->id,
        'inspector_id' => $inspectorUser->id,
        'status' => TransactionStatus::Completed,
        'completed_at' => now(),
    ]);
    $admin = User::factory()->admin()->create();

    $comment = 'Bagus & cepat <b>sekali</b>';

    Rating::query()->create([
        'transaction_id' => $transaction->id,
        'inspector_id' => $inspectorUser->id,
        'buyer_id' => $buyer->id,
        'score' => 5,
        'comment' => $comment,
        'is_hidden' => false,
    ]);

    Livewire::actingAs($admin)
        ->test(AdminRatingList::class)
        ->assertSee(e($comment), false)
        ->assertDontSee('<b>sekali</b>', false)
        ->assertDontSee('&amp;amp;', false);
});
