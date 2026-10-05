<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Feature;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Actions\RequestInspection;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Livewire\RequestInspectionPage;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Listing\Models\Listing;
use App\Domains\Payment\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(TestCase::class, RefreshDatabase::class);

it('redirects guest to login when accessing request inspection page', function () {
    $listing = Listing::factory()->active()->create();

    get(route('jobs.request', ['listing' => $listing->id]))
        ->assertRedirect(route('login'));
});

it('forbids inspectors and admins from accessing jobs.request', function (string $role) {
    $user = match ($role) {
        'inspector' => User::factory()->inspector()->create(),
        'admin' => User::factory()->admin()->create(),
    };
    $listing = Listing::factory()->active()->create();

    actingAs($user);
    get(route('jobs.request', ['listing' => $listing->id]))
        ->assertForbidden();
})->with(['inspector', 'admin']);

it('aborts with 422 when listing is not active', function () {
    $user = User::factory()->user()->create();
    $listing = Listing::factory()->create(['status' => ListingStatus::PendingReview]);

    actingAs($user);
    get(route('jobs.request', ['listing' => $listing->id]))
        ->assertStatus(422);
});

it('renders request inspection page with fee, warning, and modal', function () {
    $user = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create([
        'title' => 'Toyota Avanza 2020',
        'vehicle_type' => VehicleType::Car,
    ]);

    actingAs($user);

    Livewire::test(RequestInspectionPage::class, ['listing' => $listing])
        ->assertOk()
        ->assertSee('Toyota Avanza 2020')
        ->assertSee('Rp50.000')
        ->assertSee('30 menit')
        ->assertSee(__('inspection.request.warning_title'))
        ->assertSee(__('inspection.request.submit'));
});

it('submits request and redirects to payment show page', function () {
    $user = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create([
        'vehicle_type' => VehicleType::Car,
    ]);

    actingAs($user);

    $component = Livewire::test(RequestInspectionPage::class, ['listing' => $listing])
        ->call('submit');

    $job = InspectionJob::where('listing_id', $listing->id)->first();
    expect($job)->not->toBeNull()
        ->and($job->status)->toBe(JobStatus::Requested);

    $payment = Payment::where('ref_id', $job->id)->first();
    expect($payment)->not->toBeNull();

    $component->assertRedirect(route('payments.show', ['payment' => $payment->id]));
});

it('shows continue to payment button when requester already has active job with bill', function () {
    $user = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['vehicle_type' => VehicleType::Car]);

    // Create an active job + bill first
    actingAs($user);
    app(RequestInspection::class)->handle($user, $listing);

    Livewire::test(RequestInspectionPage::class, ['listing' => $listing])
        ->assertOk()
        ->assertSee(__('inspection.request.existing_title'))
        ->assertSee(__('inspection.request.continue_to_payment'));
});
