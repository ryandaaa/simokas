<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Models\Listing;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use App\Domains\Rating\Models\Rating;
use App\Domains\Result\Enums\InspectionRecommendation;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Models\Transaction;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Helpers\ListingPrivacyHelper;

use function Pest\Laravel\get;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // CI tidak punya .env berisi ADMIN_PASSWORD; berikan nilai uji agar seeder tidak bergantung pada lingkungan.
    config([
        'auth.admin.email' => 'seederadmin@simokas.test',
        'auth.admin.password' => 'kata-sandi-uji-'.bin2hex(random_bytes(8)),
    ]);

    seed(ReferenceSeeder::class);
});

it('seeds demo data successfully with expected record counts and statuses', function (): void {
    seed(DemoSeeder::class);

    // 1. Users verification (3 regular users, 3 inspectors, 1 admin)
    $demoUsers = User::query()->where('email', 'like', '%@demo.id')->get();
    expect($demoUsers)->toHaveCount(7);

    // Regular users
    $regularUsers = $demoUsers->where('role', UserRole::User);
    expect($regularUsers)->toHaveCount(3);
    expect($regularUsers->pluck('email')->sort()->values()->all())->toBe([
        'pembeli@demo.id',
        'penjual@demo.id',
        'user3@demo.id',
    ]);

    // Inspectors
    $inspectors = $demoUsers->where('role', UserRole::Inspector);
    expect($inspectors)->toHaveCount(3);

    $inspector1 = User::query()->where('email', 'inspektor@demo.id')->firstOrFail();
    $inspector2 = User::query()->where('email', 'inspektor2@demo.id')->firstOrFail();
    $inspectorPending = User::query()->where('email', 'inspektor_pending@demo.id')->firstOrFail();

    expect($inspector1->status)->toBe(UserStatus::Active)
        ->and($inspector1->accepted_terms_at)->not->toBeNull();
    expect($inspector2->status)->toBe(UserStatus::Active)
        ->and($inspector2->accepted_terms_at)->not->toBeNull();
    expect($inspectorPending->status)->toBe(UserStatus::Pending)
        ->and($inspectorPending->accepted_terms_at)->toBeNull();

    $profile1 = InspectorProfile::query()->where('user_id', $inspector1->id)->firstOrFail();
    $profile2 = InspectorProfile::query()->where('user_id', $inspector2->id)->firstOrFail();
    $profilePending = InspectorProfile::query()->where('user_id', $inspectorPending->id)->firstOrFail();

    expect($profile1->verification_status)->toBe(VerificationStatus::Approved)
        ->and($profile2->verification_status)->toBe(VerificationStatus::Approved)
        ->and($profilePending->verification_status)->toBe(VerificationStatus::Pending);

    // Admin
    $admin = User::query()->where('email', 'admin@demo.id')->firstOrFail();
    expect($admin->role)->toBe(UserRole::Admin)
        ->and($admin->status)->toBe(UserStatus::Active);

    // 2. Listings (15 - 20 realistic listings in Kota Pekanbaru across statuses)
    $listings = Listing::all();
    expect($listings->count())->toBeGreaterThanOrEqual(15)
        ->and($listings->count())->toBeLessThanOrEqual(20);

    foreach ($listings as $listing) {
        expect($listing->area_name)->toContain('Pekanbaru')
            ->and($listing->chassis_number)->not->toBeEmpty();
    }

    $statusesPresent = $listings->pluck('status.value')->unique()->all();
    expect($statusesPresent)->toContain(
        ListingStatus::Active->value,
        ListingStatus::PendingReview->value,
        ListingStatus::InInspection->value,
        ListingStatus::InTransaction->value,
        ListingStatus::Sold->value,
        ListingStatus::Rejected->value,
        ListingStatus::Inactive->value,
    );

    // 3. Inspection results (4 - 5 complete and consistent results)
    $results = InspectionResult::all();
    expect($results->count())->toBeGreaterThanOrEqual(4)
        ->and($results->count())->toBeLessThanOrEqual(5);

    $recommendations = $results->pluck('recommendation')->all();
    expect($recommendations)->toContain(InspectionRecommendation::Recommended)
        ->and($recommendations)->toContain(InspectionRecommendation::Caution)
        ->and($recommendations)->toContain(InspectionRecommendation::NotRecommended);

    foreach ($results as $result) {
        expect($result->total_score)->not->toBeNull()
            ->and($result->total_score)->toBeGreaterThan(0)
            ->and($result->items()->count())->toBeGreaterThan(0)
            ->and($result->photos()->count())->toBeGreaterThan(0);

        // Listing inspected flag and date must match
        $listing = $result->listing;
        expect($listing->is_inspected)->toBeTrue()
            ->and($listing->last_inspected_at)->not->toBeNull();
    }

    // 4. Jobs: 2 open (status paid), 1 running (in_progress)
    $paidJobs = InspectionJob::query()->where('status', JobStatus::Paid)->get();
    $inProgressJobs = InspectionJob::query()->where('status', JobStatus::InProgress)->get();
    expect($paidJobs)->toHaveCount(2)
        ->and($inProgressJobs)->toHaveCount(1);

    // 5. Transactions: 1 offered, 1 paid_held, 1 completed with rating (RTG-1)
    $offeredTx = Transaction::query()->where('status', TransactionStatus::Offered)->get();
    $paidHeldTx = Transaction::query()->where('status', TransactionStatus::PaidHeld)->get();
    $completedTx = Transaction::query()->where('status', TransactionStatus::Completed)->get();

    expect($offeredTx)->toHaveCount(1)
        ->and($paidHeldTx)->toHaveCount(1)
        ->and($completedTx)->toHaveCount(1);

    $completed = $completedTx->first();
    $rating = Rating::query()->where('transaction_id', $completed->id)->first();
    expect($rating)->not->toBeNull()
        ->and($rating->score)->toBe(5)
        ->and($rating->inspector_id)->toBe($completed->inspector_id)
        ->and($rating->buyer_id)->toBe($completed->buyer_id);
});

it('does not run DemoSeeder in production environment', function (): void {
    app()->detectEnvironment(fn () => 'production');

    $this->artisan('db:seed', [
        '--class' => DatabaseSeeder::class,
        '--force' => true,
    ]);

    expect(User::query()->where('email', 'like', '%@demo.id')->count())->toBe(0)
        ->and(Listing::count())->toBe(0);
});

it('does not run DemoSeeder when SEED_DEMO is false', function (): void {
    // Timpa ketiga sumber env (putenv, $_ENV, $_SERVER) karena .env dapat
    // memuat SEED_DEMO=true ke $_SERVER yang menutupi dua sumber lainnya.
    $originalServer = $_SERVER['SEED_DEMO'] ?? null;

    putenv('SEED_DEMO=false');
    $_ENV['SEED_DEMO'] = 'false';
    $_SERVER['SEED_DEMO'] = 'false';

    try {
        seed(DatabaseSeeder::class);

        expect(User::query()->where('email', 'like', '%@demo.id')->count())->toBe(0)
            ->and(Listing::count())->toBe(0);
    } finally {
        putenv('SEED_DEMO');
        unset($_ENV['SEED_DEMO']);

        if ($originalServer === null) {
            unset($_SERVER['SEED_DEMO']);
        } else {
            $_SERVER['SEED_DEMO'] = $originalServer;
        }
    }
});

it('maintains critical domain invariants in demo data', function (): void {
    seed(DemoSeeder::class);

    // Invariant 1: For settled inspection payments, sum of ledger entries equals payment amount
    $inspectionPayments = Payment::query()
        ->where('purpose', PaymentPurpose::Inspection)
        ->get();

    foreach ($inspectionPayments as $payment) {
        $ledgerEntries = LedgerEntry::query()->where('payment_id', $payment->id)->get();
        if ($ledgerEntries->isNotEmpty()) {
            expect($ledgerEntries->sum('amount'))->toBe($payment->amount);
        }
    }

    // Invariant 2: Every ledger entry points to a valid payment
    $ledgerEntries = LedgerEntry::all();
    expect($ledgerEntries->isNotEmpty())->toBeTrue();
    foreach ($ledgerEntries as $entry) {
        expect($entry->payment)->not->toBeNull();
    }

    // Invariant 3: listing.is_inspected is true ONLY when there is a submitted inspection result
    $allListings = Listing::all();
    foreach ($allListings as $listing) {
        $hasResult = InspectionResult::query()->where('listing_id', $listing->id)->exists();
        if ($listing->is_inspected) {
            expect($hasResult)->toBeTrue();
        } else {
            expect($hasResult)->toBeFalse();
        }
    }

    // Invariant 4: No duplicate active jobs per listing
    $activeJobListingIds = InspectionJob::query()
        ->whereNotIn('status', [JobStatus::Completed, JobStatus::Cancelled, JobStatus::Expired])
        ->pluck('listing_id')
        ->all();

    expect(count($activeJobListingIds))->toBe(count(array_unique($activeJobListingIds)));

    // Invariant 5: All user passwords in demo data are securely hashed
    $users = User::all();
    foreach ($users as $user) {
        expect(Hash::info($user->password)['algoName'])->not->toBe('unknown')
            ->and($user->password)->not->toBe('password')
            ->and($user->password)->toStartWith('$2y$');
    }
});

it('does not leak private data in public catalog for demo listings', function (): void {
    seed(DemoSeeder::class);

    // Public catalog index
    $catalogResponse = get(route('catalog.index'))->assertOk();
    ListingPrivacyHelper::assertNoPrivateListingData($catalogResponse);

    // Public detail page for an active listing
    $activeListing = Listing::query()->where('status', ListingStatus::Active)->firstOrFail();
    $detailResponse = get(route('catalog.show', ['listing' => $activeListing->id]))->assertOk();

    ListingPrivacyHelper::assertNoPrivateListingData($detailResponse, [
        $activeListing->chassis_number,
        $activeListing->address_full,
    ]);
});
