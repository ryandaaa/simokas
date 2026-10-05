<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Feature;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Livewire\MyInspections;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('redirects guest to login when accessing my inspections page', function () {
    get(route('jobs.mine'))->assertRedirect(route('login'));
});

it('forbids inspector and admin from accessing jobs.mine', function (string $role) {
    $user = match ($role) {
        'inspector' => User::factory()->inspector()->create(),
        'admin' => User::factory()->admin()->create(),
    };

    actingAs($user);
    get(route('jobs.mine'))->assertForbidden();
})->with(['inspector', 'admin']);

it('renders empty state when user has no inspection jobs', function () {
    $user = User::factory()->user()->create();

    actingAs($user);

    Livewire::test(MyInspections::class)
        ->assertOk()
        ->assertSee(__('inspection.mine.empty_title'))
        ->assertSee(__('inspection.mine.browse'));
});

it('displays list of requester jobs with status chips and only own jobs', function () {
    $user = User::factory()->user()->create();
    $otherUser = User::factory()->user()->create();

    $listing1 = Listing::factory()->active()->create(['title' => 'Honda Jazz 2018']);
    $listing2 = Listing::factory()->active()->create(['title' => 'Yamaha NMAX 2021']);
    $listingOther = Listing::factory()->active()->create(['title' => 'Suzuki Ertiga 2019']);

    InspectionJob::factory()->create([
        'requester_id' => $user->id,
        'listing_id' => $listing1->id,
        'status' => JobStatus::Requested,
        'fee_amount' => 50000,
    ]);

    InspectionJob::factory()->create([
        'requester_id' => $user->id,
        'listing_id' => $listing2->id,
        'status' => JobStatus::Paid,
        'fee_amount' => 15000,
    ]);

    InspectionJob::factory()->create([
        'requester_id' => $otherUser->id,
        'listing_id' => $listingOther->id,
        'status' => JobStatus::Requested,
        'fee_amount' => 50000,
    ]);

    actingAs($user);

    Livewire::test(MyInspections::class)
        ->assertOk()
        ->assertSee('Honda Jazz 2018')
        ->assertSee('Yamaha NMAX 2021')
        ->assertDontSee('Suzuki Ertiga 2019')
        ->assertSee('Rp50.000')
        ->assertSee('Rp15.000')
        ->assertSee(JobStatus::Requested->label())
        ->assertSee(JobStatus::Paid->label());
});

it('paginates results when user has more than 12 jobs', function () {
    $user = User::factory()->user()->create();

    for ($i = 1; $i <= 15; $i++) {
        $listing = Listing::factory()->active()->create(['title' => "Car Number {$i}"]);
        InspectionJob::factory()->create([
            'requester_id' => $user->id,
            'listing_id' => $listing->id,
            'created_at' => now()->subDays(15 - $i),
        ]);
    }

    actingAs($user);

    Livewire::test(MyInspections::class)
        ->assertOk()
        ->assertSee('Car Number 15')
        ->assertDontSee('Car Number 1'); // Page 1 shows newest 12
});
