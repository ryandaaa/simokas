<?php

declare(strict_types=1);

namespace App\Domains\Rating\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Rating\Actions\HideRating;
use App\Domains\Rating\Actions\UnhideRating;
use App\Domains\Rating\Events\RatingHidden;
use App\Domains\Rating\Events\RatingUnhidden;
use App\Domains\Rating\Models\Rating;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('RTG-T05 admin hides and unhides rating, recalculating inspector average and logging audit (RTG-E6)', function () {
    $admin = User::factory()->admin()->create();
    $inspectorUser = User::factory()->inspector()->create();
    $profile = InspectorProfile::factory()->create([
        'user_id' => $inspectorUser->id,
        'rating_avg' => 4.50,
        'rating_count' => 2,
    ]);

    // Create 2 ratings: 5 and 4 (avg = 4.50)
    $rating1 = Rating::factory()->create([
        'inspector_id' => $inspectorUser->id,
        'score' => 5,
        'is_hidden' => false,
    ]);
    $rating2 = Rating::factory()->create([
        'inspector_id' => $inspectorUser->id,
        'score' => 4,
        'is_hidden' => false,
    ]);

    $this->actingAs($admin);

    // 1. Admin hides rating 2 (score 4)
    $hideAction = app(HideRating::class);
    $hidden = $hideAction->handle($admin, $rating2);

    expect($hidden->is_hidden)->toBeTrue();
    $this->assertDatabaseHas('ratings', [
        'id' => $rating2->id,
        'is_hidden' => true,
    ]);

    // Profile stats should now be based only on rating 1 (score 5, count 1)
    $profile->refresh();
    expect((float) $profile->rating_avg)->toBe(5.00)
        ->and($profile->rating_count)->toBe(1);

    // Audit log should be recorded
    $this->assertDatabaseHas('audit_logs', [
        'actor_id' => $admin->id,
        'action' => 'hide_rating',
        'entity' => 'rating',
        'entity_id' => $rating2->id,
    ]);

    // 2. Admin unhides rating 2
    $unhideAction = app(UnhideRating::class);
    $unhidden = $unhideAction->handle($admin, $rating2->fresh());

    expect($unhidden->is_hidden)->toBeFalse();
    $this->assertDatabaseHas('ratings', [
        'id' => $rating2->id,
        'is_hidden' => false,
    ]);

    // Profile stats should now be back to (5 + 4) / 2 = 4.50, count 2
    $profile->refresh();
    expect((float) $profile->rating_avg)->toBe(4.50)
        ->and($profile->rating_count)->toBe(2);

    // Audit log should record unhide
    $this->assertDatabaseHas('audit_logs', [
        'actor_id' => $admin->id,
        'action' => 'unhide_rating',
        'entity' => 'rating',
        'entity_id' => $rating2->id,
    ]);
});

it('hiding already hidden rating and unhiding already visible rating is idempotent', function () {
    $admin = User::factory()->admin()->create();
    $inspectorUser = User::factory()->inspector()->create();
    InspectorProfile::factory()->create([
        'user_id' => $inspectorUser->id,
        'rating_avg' => 5.00,
        'rating_count' => 1,
    ]);

    $rating = Rating::factory()->create([
        'inspector_id' => $inspectorUser->id,
        'score' => 5,
        'is_hidden' => true,
    ]);

    Event::fake([RatingHidden::class, RatingUnhidden::class]);

    // Hide an already hidden rating -> idempotent
    $hideAction = app(HideRating::class);
    $res = $hideAction->handle($admin, $rating);
    expect($res->is_hidden)->toBeTrue();
    Event::assertNotDispatched(RatingHidden::class);

    // Unhide a visible rating -> idempotent
    $rating->update(['is_hidden' => false]);
    $unhideAction = app(UnhideRating::class);
    $res2 = $unhideAction->handle($admin, $rating->fresh());
    expect($res2->is_hidden)->toBeFalse();
    Event::assertNotDispatched(RatingUnhidden::class);
});
