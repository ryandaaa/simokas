<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('shows Ajukan Inspeksi button and hides replacement notice when no active job exists', function () {
    $seller = User::factory()->user()->create([
        'name' => 'Penjual Satu',
        'email' => 'seller-nojob@example.com',
    ]);
    $buyer = User::factory()->user()->create([
        'name' => 'Pembeli Satu',
        'email' => 'buyer-nojob@example.com',
    ]);
    $listing = Listing::factory()->active()->create([
        'seller_id' => $seller->id,
        'title' => 'Mobil Tanpa Job Aktif',
    ]);

    // Authenticated eligible buyer
    actingAs($buyer)
        ->get(route('catalog.show', ['listing' => $listing->id]))
        ->assertOk()
        ->assertSee(__('catalog.detail.action_request_inspection'))
        ->assertDontSee(__('catalog.detail.active_inspection_notice'));

    // Guest
    auth()->logout();
    get(route('catalog.show', ['listing' => $listing->id]))
        ->assertOk()
        ->assertSee(__('catalog.detail.action_request_inspection'))
        ->assertDontSee(__('catalog.detail.active_inspection_notice'));
});

it('hides Ajukan Inspeksi button and shows replacement notice for active jobs', function (JobStatus $status) {
    $seller = User::factory()->user()->create([
        'name' => 'Penjual Dua',
        'email' => 'seller-activejob@example.com',
    ]);
    $buyer = User::factory()->user()->create([
        'name' => 'Pembeli Dua',
        'email' => 'buyer-activejob@example.com',
    ]);
    $inspector = User::factory()->inspector()->create([
        'name' => 'Inspektor Rahasia',
        'email' => 'inspector-secret@example.com',
    ]);
    $listing = Listing::factory()->active()->create([
        'seller_id' => $seller->id,
        'title' => 'Mobil Dengan Job Aktif',
    ]);

    InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'requester_id' => $buyer->id,
        'inspector_id' => in_array($status, [JobStatus::Taken, JobStatus::Scheduled, JobStatus::InProgress], true)
            ? $inspector->id
            : null,
        'status' => $status,
    ]);

    // Authenticated eligible buyer
    actingAs($buyer)
        ->get(route('catalog.show', ['listing' => $listing->id]))
        ->assertOk()
        ->assertDontSee(__('catalog.detail.action_request_inspection'))
        ->assertSee(__('catalog.detail.active_inspection_notice'))
        ->assertDontSee('Inspektor Rahasia');

    // Guest
    auth()->logout();
    get(route('catalog.show', ['listing' => $listing->id]))
        ->assertOk()
        ->assertDontSee(__('catalog.detail.action_request_inspection'))
        ->assertSee(__('catalog.detail.active_inspection_notice'))
        ->assertDontSee('Inspektor Rahasia');
})->with([
    'requested' => JobStatus::Requested,
    'paid' => JobStatus::Paid,
    'taken' => JobStatus::Taken,
    'scheduled' => JobStatus::Scheduled,
    'in_progress' => JobStatus::InProgress,
]);

it('restores Ajukan Inspeksi button and hides notice when job reaches final status', function (JobStatus $status) {
    $seller = User::factory()->user()->create([
        'name' => 'Penjual Tiga',
        'email' => 'seller-finaljob@example.com',
    ]);
    $buyer = User::factory()->user()->create([
        'name' => 'Pembeli Tiga',
        'email' => 'buyer-finaljob@example.com',
    ]);
    $listing = Listing::factory()->active()->create([
        'seller_id' => $seller->id,
        'title' => 'Mobil Dengan Job Selesai',
    ]);

    InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'requester_id' => $buyer->id,
        'status' => $status,
    ]);

    actingAs($buyer)
        ->get(route('catalog.show', ['listing' => $listing->id]))
        ->assertOk()
        ->assertSee(__('catalog.detail.action_request_inspection'))
        ->assertDontSee(__('catalog.detail.active_inspection_notice'));
})->with([
    'completed' => JobStatus::Completed,
    'cancelled' => JobStatus::Cancelled,
    'expired' => JobStatus::Expired,
]);
