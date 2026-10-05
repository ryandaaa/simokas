<?php

declare(strict_types=1);

namespace App\Domains\Admin\Tests\Feature;

use App\Domains\Admin\Livewire\ListingValidationQueue;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Models\User;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('ADM-T01 admin listing validation route is protected and only accessible by admin (dataset role)', function (UserRole $role, int $expectedStatus) {
    $user = User::factory()->create(['role' => $role]);
    actingAs($user);

    $response = get('/admin/listings');

    $response->assertStatus($expectedStatus);
})->with([
    'admin has access' => [UserRole::Admin, 200],
    'user is forbidden' => [UserRole::User, 403],
    'inspector is forbidden' => [UserRole::Inspector, 403],
]);

it('unauthenticated user is redirected to login from admin listing queue', function () {
    $response = get('/admin/listings');

    $response->assertRedirect(route('login'));
});

it('ADM-T02 lists pending listings oldest first with empty and pagination states', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(ListingValidationQueue::class)
        ->assertSee(__('admin.listing_queue.empty_title'));

    $older = Listing::factory()->pendingReview()->create([
        'title' => 'Toyota Avanza Lama Menunggu',
        'created_at' => now()->subDays(5),
    ]);
    $newer = Listing::factory()->pendingReview()->create([
        'title' => 'Honda Brio Baru Menunggu',
        'created_at' => now()->subDay(),
    ]);
    Listing::factory()->active()->create(['title' => 'Listing Aktif Bukan Antrean']);

    $html = Livewire::actingAs($admin)
        ->test(ListingValidationQueue::class)
        ->assertSee('Toyota Avanza Lama Menunggu')
        ->assertSee('Honda Brio Baru Menunggu')
        ->assertDontSee('Listing Aktif Bukan Antrean')
        ->html();

    expect(strpos($html, 'Toyota Avanza Lama Menunggu') < strpos($html, 'Honda Brio Baru Menunggu'))->toBeTrue();
    expect($older->fresh()->status)->toBe(ListingStatus::PendingReview);
    expect($newer->fresh()->status)->toBe(ListingStatus::PendingReview);
});

it('ADM-T02 admin approves a listing from the validation queue', function () {
    $admin = User::factory()->admin()->create();
    $listing = Listing::factory()->pendingReview()->create(['title' => 'Daihatsu Xenia Menunggu Validasi']);

    Livewire::actingAs($admin)
        ->test(ListingValidationQueue::class)
        ->call('confirmApprove', $listing->id)
        ->assertSet('confirmingApproveId', $listing->id)
        ->call('approve')
        ->assertSet('confirmingApproveId', null)
        ->assertSee(__('admin.listing_queue.approved_success'));

    $fresh = $listing->fresh();
    expect($fresh->status)->toBe(ListingStatus::Active)
        ->and($fresh->validated_by)->toBe($admin->id);
});

it('ADM-T02 admin rejects a listing with mandatory reason from the validation queue', function () {
    $admin = User::factory()->admin()->create();
    $listing = Listing::factory()->pendingReview()->create(['title' => 'Suzuki Ertiga Menunggu Validasi']);

    Livewire::actingAs($admin)
        ->test(ListingValidationQueue::class)
        ->call('confirmReject', $listing->id)
        ->set('rejectionReason', '')
        ->call('reject')
        ->assertHasErrors(['rejectionReason' => 'required']);

    $reason = 'Foto STNK tidak terbaca, mohon unggah ulang.';
    Livewire::actingAs($admin)
        ->test(ListingValidationQueue::class)
        ->call('confirmReject', $listing->id)
        ->set('rejectionReason', $reason)
        ->call('reject')
        ->assertHasNoErrors()
        ->assertSet('confirmingRejectId', null)
        ->assertSee(__('admin.listing_queue.rejected_success'));

    $fresh = $listing->fresh();
    expect($fresh->status)->toBe(ListingStatus::Rejected)
        ->and($fresh->rejection_reason)->toBe($reason);
});

it('ADM-E3 shows a friendly error when another admin already processed the listing', function () {
    $firstAdmin = User::factory()->admin()->create();
    $secondAdmin = User::factory()->admin()->create();
    $listing = Listing::factory()->pendingReview()->create(['title' => 'Wuling Confero Rebutan Admin']);

    Livewire::actingAs($firstAdmin)
        ->test(ListingValidationQueue::class)
        ->call('confirmApprove', $listing->id)
        ->call('approve')
        ->assertSee(__('admin.listing_queue.approved_success'));

    Livewire::actingAs($secondAdmin)
        ->test(ListingValidationQueue::class)
        ->call('confirmReject', $listing->id)
        ->set('rejectionReason', 'Alasan admin kedua.')
        ->call('reject')
        ->assertSee(__('admin.listing_queue.already_processed'));

    $fresh = $listing->fresh();
    expect($fresh->status)->toBe(ListingStatus::Active)
        ->and($fresh->validated_by)->toBe($firstAdmin->id);
});
