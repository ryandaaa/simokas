<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\Tests\Feature;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Models\Listing;
use App\Domains\Result\Database\seeders\InspectionTemplateSeeder;
use App\Domains\Result\Enums\InspectionRecommendation;
use App\Domains\Result\Enums\ResultStatus;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\InspectionTemplate;
use App\Domains\Result\Models\ResultAccess;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

function createApprovedInspector(string $name = 'Verified Inspector'): User
{
    $user = User::factory()->create([
        'name' => $name,
        'role' => UserRole::Inspector,
        'status' => UserStatus::Active,
    ]);

    InspectorProfile::factory()->create([
        'user_id' => $user->id,
        'verification_status' => VerificationStatus::Approved,
        'vehicle_types' => ['car', 'motorcycle'],
    ]);

    return $user;
}

function getTemplate(): InspectionTemplate
{
    $template = InspectionTemplate::query()->first();
    if ($template !== null) {
        return $template;
    }

    $seeder = new InspectionTemplateSeeder;
    $seeder->run();

    return InspectionTemplate::query()->firstOrFail();
}

it('HIST-T01 user only sees their own history and cannot access others via parameters', function () {
    $user1 = User::factory()->user()->create(['name' => 'Pengguna Satu']);
    $user2 = User::factory()->user()->create(['name' => 'Pengguna Dua']);
    $seller = User::factory()->user()->create(['name' => 'Penjual Mobil']);
    $inspector = createApprovedInspector('Inspektor Budi');
    $template = getTemplate();

    // Listings
    $listing1 = Listing::factory()->active()->create([
        'seller_id' => $seller->id,
        'title' => 'Toyota Avanza Milik User 1',
    ]);
    $listing2 = Listing::factory()->active()->create([
        'seller_id' => $seller->id,
        'title' => 'Honda Jazz Milik User 2',
    ]);
    $listingUser1Seller = Listing::factory()->active()->create([
        'seller_id' => $user1->id,
        'title' => 'Suzuki Ertiga Dijual User 1',
    ]);

    // Transaction for User 1 as buyer
    $tx1 = Transaction::factory()->create([
        'listing_id' => $listing1->id,
        'buyer_id' => $user1->id,
        'status' => TransactionStatus::Completed,
        'price_amount' => 150_000_000,
    ]);

    // Transaction for User 2 as buyer
    $tx2 = Transaction::factory()->create([
        'listing_id' => $listing2->id,
        'buyer_id' => $user2->id,
        'status' => TransactionStatus::Completed,
        'price_amount' => 180_000_000,
    ]);

    // Transaction for User 1 as seller
    $txSeller1 = Transaction::factory()->create([
        'listing_id' => $listingUser1Seller->id,
        'buyer_id' => $user2->id,
        'status' => TransactionStatus::Accepted,
        'price_amount' => 140_000_000,
    ]);

    // Inspection Results: Result 1 accessed by User 1, Result 2 for User 2
    $job1 = InspectionJob::factory()->create([
        'listing_id' => $listing1->id,
        'requester_id' => $user1->id,
        'inspector_id' => $inspector->id,
        'status' => JobStatus::Completed,
    ]);
    $res1 = InspectionResult::factory()->create([
        'job_id' => $job1->id,
        'listing_id' => $listing1->id,
        'inspector_id' => $inspector->id,
        'template_id' => $template->id,
        'status' => ResultStatus::Submitted,
        'recommendation' => InspectionRecommendation::Recommended,
        'total_score' => 88.5,
    ]);
    ResultAccess::factory()->create([
        'result_id' => $res1->id,
        'user_id' => $user1->id,
    ]);

    $job2 = InspectionJob::factory()->create([
        'listing_id' => $listing2->id,
        'requester_id' => $user2->id,
        'inspector_id' => $inspector->id,
        'status' => JobStatus::Completed,
    ]);
    $res2 = InspectionResult::factory()->create([
        'job_id' => $job2->id,
        'listing_id' => $listing2->id,
        'inspector_id' => $inspector->id,
        'template_id' => $template->id,
        'status' => ResultStatus::Submitted,
        'recommendation' => InspectionRecommendation::Caution,
        'total_score' => 65.0,
    ]);
    ResultAccess::factory()->create([
        'result_id' => $res2->id,
        'user_id' => $user2->id,
    ]);

    // User 1 visits history
    $response = actingAs($user1)->get(route('history'));
    $response->assertOk();
    $response->assertSee('Toyota Avanza Milik User 1');
    $response->assertSee('Suzuki Ertiga Dijual User 1');
    $response->assertDontSee('Honda Jazz Milik User 2');

    // User 1 tries to access User 2 data via query parameter
    $responseWithParam = actingAs($user1)->get(route('history', ['user_id' => $user2->id, 'user' => $user2->id]));
    $responseWithParam->assertOk();
    $responseWithParam->assertSee('Toyota Avanza Milik User 1');
    $responseWithParam->assertDontSee('Honda Jazz Milik User 2');

    // User 2 visits history
    $response2 = actingAs($user2)->get(route('history'));
    $response2->assertOk();
    $response2->assertSee('Honda Jazz Milik User 2');
    $response2->assertDontSee('Toyota Avanza Milik User 1');
});

it('HIST-T02 accessible inspection results remain visible after listing is sold', function () {
    $user = User::factory()->user()->create();
    $seller = User::factory()->user()->create();
    $inspector = createApprovedInspector();
    $template = getTemplate();

    $listing = Listing::factory()->create([
        'seller_id' => $seller->id,
        'title' => 'Daihatsu Xenia Terjual',
        'status' => ListingStatus::Sold,
    ]);

    $job = InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'requester_id' => $user->id,
        'inspector_id' => $inspector->id,
        'status' => JobStatus::Completed,
    ]);

    $result = InspectionResult::factory()->create([
        'job_id' => $job->id,
        'listing_id' => $listing->id,
        'inspector_id' => $inspector->id,
        'template_id' => $template->id,
        'status' => ResultStatus::Submitted,
        'recommendation' => InspectionRecommendation::Recommended,
        'total_score' => 92.0,
    ]);

    ResultAccess::factory()->create([
        'result_id' => $result->id,
        'user_id' => $user->id,
    ]);

    $response = actingAs($user)->get(route('history'));
    $response->assertOk();
    $response->assertSee('Daihatsu Xenia Terjual');
    $response->assertSee(route('results.show', $listing));
});

it('HIST-T03 history response does not leak sensitive private listing data', function () {
    $user = User::factory()->user()->create();
    $seller = User::factory()->user()->create();

    $privateAddress = 'Jalan Melati Eksklusif No 888 Komplek Rahasia';
    $privateLat = '0.5123999';
    $privateLng = '101.4456999';
    $privateChassis = 'MH1XYZ998877665544';
    $privatePlace = 'Garasi Pribadi Rumah Belakang';

    $listing = Listing::factory()->active()->create([
        'seller_id' => $seller->id,
        'title' => 'Mobil Dengan Data Privat',
        'address_full' => $privateAddress,
        'lat' => $privateLat,
        'lng' => $privateLng,
        'chassis_number' => $privateChassis,
        'inspection_place' => $privatePlace,
    ]);

    Transaction::factory()->create([
        'listing_id' => $listing->id,
        'buyer_id' => $user->id,
        'status' => TransactionStatus::Completed,
        'price_amount' => 120_000_000,
    ]);

    $response = actingAs($user)->get(route('history'));
    $response->assertOk();

    // Check that sensitive private attributes never appear in response HTML
    $response->assertDontSee($privateAddress);
    $response->assertDontSee($privateLat);
    $response->assertDontSee($privateLng);
    $response->assertDontSee($privateChassis);
    $response->assertDontSee($privatePlace);
});

it('HIST-T04 paginates items with 10 per page', function () {
    $user = User::factory()->user()->create();
    $seller = User::factory()->user()->create();

    // Create 15 transactions as buyer
    for ($i = 1; $i <= 15; $i++) {
        $listing = Listing::factory()->active()->create([
            'seller_id' => $seller->id,
            'title' => sprintf('Kendaraan Riwayat No %02d', $i),
            'created_at' => now()->subMinutes(20 - $i),
        ]);

        Transaction::factory()->create([
            'listing_id' => $listing->id,
            'buyer_id' => $user->id,
            'status' => TransactionStatus::Completed,
            'price_amount' => 100_000_000 + ($i * 1_000_000),
            'created_at' => now()->subMinutes(20 - $i),
        ]);
    }

    // Page 1 should show 10 items (latest 10)
    $responsePage1 = actingAs($user)->get(route('history'));
    $responsePage1->assertOk();
    $responsePage1->assertSee('Kendaraan Riwayat No 15');
    $responsePage1->assertSee('Kendaraan Riwayat No 06');
    $responsePage1->assertDontSee('Kendaraan Riwayat No 05');

    // Page 2 should show remaining 5 items
    $responsePage2 = actingAs($user)->get(route('history', ['buying_page' => 2]));
    $responsePage2->assertOk();
    $responsePage2->assertSee('Kendaraan Riwayat No 05');
    $responsePage2->assertSee('Kendaraan Riwayat No 01');
    $responsePage2->assertDontSee('Kendaraan Riwayat No 15');
});

it('HIST-T05 displays empty states for user without history', function () {
    $newUser = User::factory()->user()->create();

    $response = actingAs($newUser)->get(route('history'));
    $response->assertOk();

    $response->assertSee(__('dashboard.history.empty_buying_title'));
    $response->assertSee(__('dashboard.history.empty_selling_title'));
    $response->assertSee(__('dashboard.history.empty_results_title'));
});

it('HIST-T02b a draft result never appears in the requester history until it is submitted', function () {
    $requester = User::factory()->user()->create();
    $seller = User::factory()->user()->create();
    $inspector = createApprovedInspector();
    $template = getTemplate();

    $listing = Listing::factory()->create([
        'seller_id' => $seller->id,
        'title' => 'Suzuki Ertiga Draf Rahasia',
        'status' => ListingStatus::Active,
    ]);

    $job = InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'requester_id' => $requester->id,
        'inspector_id' => $inspector->id,
        'status' => JobStatus::InProgress,
    ]);

    $result = InspectionResult::factory()->create([
        'job_id' => $job->id,
        'listing_id' => $listing->id,
        'inspector_id' => $inspector->id,
        'template_id' => $template->id,
        'status' => ResultStatus::Draft,
    ]);

    actingAs($requester)->get(route('history'))
        ->assertOk()
        ->assertDontSee('Suzuki Ertiga Draf Rahasia');

    $result->update([
        'status' => ResultStatus::Submitted,
        'recommendation' => InspectionRecommendation::Recommended,
        'total_score' => 85.0,
        'submitted_at' => now(),
    ]);

    actingAs($requester)->get(route('history'))
        ->assertOk()
        ->assertSee('Suzuki Ertiga Draf Rahasia');
});

it('HIST-T06 enforces authorization for all roles and unapproved inspector restrictions', function () {
    // 1. Guest redirected to login
    $guestResponse = get(route('history'));
    $guestResponse->assertRedirect(route('login'));

    // 2. Blocked user rejected by not_blocked middleware
    $blockedUser = User::factory()->blocked()->create();
    $blockedResponse = actingAs($blockedUser)->get(route('history'));
    $blockedResponse->assertRedirect(route('identity.blocked'));

    // 3. Regular user allowed
    $regularUser = User::factory()->user()->create();
    $regResponse = actingAs($regularUser)->get(route('history'));
    $regResponse->assertOk();
    $regResponse->assertDontSee(__('dashboard.history.inspector_jobs'));

    // 4. Unapproved inspector allowed to view page, but does NOT see inspector jobs section
    $pendingInspector = User::factory()->create([
        'role' => UserRole::Inspector,
        'status' => UserStatus::Pending,
    ]);
    InspectorProfile::factory()->create([
        'user_id' => $pendingInspector->id,
        'verification_status' => VerificationStatus::Pending,
    ]);
    $pendingResponse = actingAs($pendingInspector)->get(route('history'));
    $pendingResponse->assertOk();
    $pendingResponse->assertDontSee(__('dashboard.history.inspector_jobs'));

    // 5. Approved inspector allowed and sees inspector jobs section
    $approvedInspector = createApprovedInspector();
    $appResponse = actingAs($approvedInspector)->get(route('history'));
    $appResponse->assertOk();
    $appResponse->assertSee(__('dashboard.history.inspector_jobs'));

    // 6. Admin allowed
    $admin = User::factory()->admin()->create();
    $adminResponse = actingAs($admin)->get(route('history'));
    $adminResponse->assertOk();
});

it('HIST-T07 query count is bounded and does not suffer from N+1 problems', function () {
    $user = User::factory()->user()->create();
    $seller = User::factory()->user()->create();
    $inspector = createApprovedInspector();
    $template = getTemplate();

    // Seed 10 transactions and 10 results
    for ($i = 1; $i <= 10; $i++) {
        $listing = Listing::factory()->active()->create([
            'seller_id' => $seller->id,
            'title' => "Kendaraan N1 Test {$i}",
        ]);

        Transaction::factory()->create([
            'listing_id' => $listing->id,
            'buyer_id' => $user->id,
            'status' => TransactionStatus::Completed,
            'price_amount' => 100_000_000,
        ]);

        $job = InspectionJob::factory()->create([
            'listing_id' => $listing->id,
            'requester_id' => $user->id,
            'inspector_id' => $inspector->id,
            'status' => JobStatus::Completed,
        ]);

        $result = InspectionResult::factory()->create([
            'job_id' => $job->id,
            'listing_id' => $listing->id,
            'inspector_id' => $inspector->id,
            'template_id' => $template->id,
            'status' => ResultStatus::Submitted,
        ]);

        ResultAccess::factory()->create([
            'result_id' => $result->id,
            'user_id' => $user->id,
        ]);
    }

    DB::flushQueryLog();
    DB::enableQueryLog();

    $response = actingAs($user)->get(route('history'));
    $response->assertOk();

    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    // With 10 transactions and 10 results, an N+1 implementation would run > 30 queries.
    // An eager-loaded query count should be well below 20 queries (around 8-12 queries).
    expect($queryCount)->toBeLessThan(20);
});
