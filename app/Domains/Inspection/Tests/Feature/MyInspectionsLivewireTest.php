<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Feature;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Livewire\MyInspections;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Models\Listing;
use App\Domains\Payment\Enums\PaymentMethod;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        default => throw new \InvalidArgumentException("Unknown role: {$role}"),
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
        $listing = Listing::factory()->active()->create(['title' => sprintf('Kendaraan Alpha-%02d', $i)]);
        InspectionJob::factory()->create([
            'requester_id' => $user->id,
            'listing_id' => $listing->id,
            'created_at' => now()->subDays(15 - $i),
        ]);
    }

    actingAs($user);

    Livewire::test(MyInspections::class)
        ->assertOk()
        ->assertSee('Kendaraan Alpha-15')
        ->assertDontSee('Kendaraan Alpha-01'); // Page 1 shows newest 12
});

it('renders Batalkan button for cancellable jobs and in_progress notice for running jobs', function () {
    $user = User::factory()->user()->create();
    $inspector = User::factory()->inspector()->create();

    $listingPaid = Listing::factory()->active()->create(['title' => 'Mobil Bayar']);
    $listingInProgress = Listing::factory()->active()->create(['title' => 'Mobil Jalan']);

    $jobPaid = InspectionJob::factory()->paid()->create([
        'requester_id' => $user->id,
        'listing_id' => $listingPaid->id,
    ]);

    $jobInProgress = InspectionJob::factory()->inProgress($inspector)->create([
        'requester_id' => $user->id,
        'listing_id' => $listingInProgress->id,
    ]);

    actingAs($user);

    Livewire::test(MyInspections::class)
        ->assertOk()
        ->assertSee(__('inspection.mine.cancel_button'))
        ->assertSee(__('inspection.mine.in_progress_notice'));
});

it('allows requester to cancel a paid job with refund through Livewire modal', function () {
    $user = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['title' => 'Mobil Batal Refund']);

    $job = InspectionJob::factory()->paid()->create([
        'requester_id' => $user->id,
        'listing_id' => $listing->id,
    ]);

    $payment = Payment::factory()->create([
        'payer_id' => $user->id,
        'purpose' => PaymentPurpose::Inspection,
        'ref_id' => $job->id,
        'amount' => 50000,
        'status' => PaymentStatus::Success,
        'method' => PaymentMethod::Qris,
    ]);

    actingAs($user);

    Livewire::test(MyInspections::class)
        ->call('confirmCancel', $job->id)
        ->assertDispatched('open-modal', 'requester-cancel-modal')
        ->set('cancel_reason', 'Rencana berubah')
        ->call('cancelJob')
        ->assertDispatched('close-modal', 'requester-cancel-modal')
        ->assertSee(__('inspection.mine.cancel_success'));

    expect($job->fresh()->status)->toBe(JobStatus::Cancelled)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Refunded);
});

it('displays Bayar button with correct payment link for requested job with active bill', function () {
    $user = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['title' => 'Toyota Avanza 2020']);

    $job = InspectionJob::factory()->create([
        'requester_id' => $user->id,
        'listing_id' => $listing->id,
        'status' => JobStatus::Requested,
        'fee_amount' => 50000,
    ]);

    $payment = Payment::factory()->create([
        'payer_id' => $user->id,
        'purpose' => PaymentPurpose::Inspection,
        'ref_id' => $job->id,
        'status' => PaymentStatus::Pending,
        'expires_at' => now()->addMinutes(25),
    ]);

    actingAs($user);

    Livewire::test(MyInspections::class)
        ->assertOk()
        ->assertSee(__('inspection.mine.pay'))
        ->assertSee(route('payments.show', ['payment' => $payment->id]), false)
        ->assertDontSee(__('inspection.mine.bill_expired'));
});

it('displays bill expired notice and reapply link instead of Bayar button when bill is expired', function () {
    $user = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['title' => 'Honda Brio 2019']);

    $job = InspectionJob::factory()->create([
        'requester_id' => $user->id,
        'listing_id' => $listing->id,
        'status' => JobStatus::Requested,
        'fee_amount' => 50000,
    ]);

    Payment::factory()->create([
        'payer_id' => $user->id,
        'purpose' => PaymentPurpose::Inspection,
        'ref_id' => $job->id,
        'status' => PaymentStatus::Pending,
        'expires_at' => now()->subMinutes(5),
    ]);

    actingAs($user);

    Livewire::test(MyInspections::class)
        ->assertOk()
        ->assertDontSee(__('inspection.mine.pay'))
        ->assertSee(__('inspection.mine.bill_expired'))
        ->assertSee(__('inspection.mine.reapply'))
        ->assertSee(route('jobs.request', ['listing' => $listing->id]), false);
});

it('displays bill expired notice when requested job has no payment bill', function () {
    $user = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['title' => 'Daihatsu Xenia 2021']);

    InspectionJob::factory()->create([
        'requester_id' => $user->id,
        'listing_id' => $listing->id,
        'status' => JobStatus::Requested,
        'fee_amount' => 50000,
    ]);

    actingAs($user);

    Livewire::test(MyInspections::class)
        ->assertOk()
        ->assertDontSee(__('inspection.mine.pay'))
        ->assertSee(__('inspection.mine.bill_expired'));
});

it('does not display Bayar button for non-requested job statuses', function (JobStatus $status) {
    $user = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['title' => 'Mobil Status Lain']);

    $job = InspectionJob::factory()->create([
        'requester_id' => $user->id,
        'listing_id' => $listing->id,
        'status' => $status,
        'fee_amount' => 50000,
    ]);

    Payment::factory()->create([
        'payer_id' => $user->id,
        'purpose' => PaymentPurpose::Inspection,
        'ref_id' => $job->id,
        'status' => PaymentStatus::Pending,
        'expires_at' => now()->addMinutes(25),
    ]);

    actingAs($user);

    Livewire::test(MyInspections::class)
        ->assertOk()
        ->assertDontSee(__('inspection.mine.pay'))
        ->assertDontSee(__('inspection.mine.bill_expired'));
})->with([
    'paid' => JobStatus::Paid,
    'taken' => JobStatus::Taken,
    'scheduled' => JobStatus::Scheduled,
    'in_progress' => JobStatus::InProgress,
    'completed' => JobStatus::Completed,
    'cancelled' => JobStatus::Cancelled,
]);

it('does not trigger N+1 queries when multiple requested jobs exist', function () {
    $user = User::factory()->user()->create();

    $listing1 = Listing::factory()->active()->create(['title' => 'Mobil 1']);
    $job1 = InspectionJob::factory()->create([
        'requester_id' => $user->id,
        'listing_id' => $listing1->id,
        'status' => JobStatus::Requested,
    ]);
    Payment::factory()->create([
        'payer_id' => $user->id,
        'purpose' => PaymentPurpose::Inspection,
        'ref_id' => $job1->id,
        'status' => PaymentStatus::Pending,
        'expires_at' => now()->addMinutes(20),
    ]);

    actingAs($user);

    // Warm up
    Livewire::test(MyInspections::class);

    DB::flushQueryLog();
    DB::enableQueryLog();

    Livewire::test(MyInspections::class);
    $queryCountSingle = count(DB::getQueryLog());

    // Tambah 5 job lagi dengan tagihan masing-masing
    for ($i = 2; $i <= 6; $i++) {
        $listing = Listing::factory()->active()->create(['title' => "Mobil {$i}"]);
        $job = InspectionJob::factory()->create([
            'requester_id' => $user->id,
            'listing_id' => $listing->id,
            'status' => JobStatus::Requested,
        ]);
        Payment::factory()->create([
            'payer_id' => $user->id,
            'purpose' => PaymentPurpose::Inspection,
            'ref_id' => $job->id,
            'status' => PaymentStatus::Pending,
            'expires_at' => now()->addMinutes(20),
        ]);
    }

    DB::flushQueryLog();

    Livewire::test(MyInspections::class);
    $queryCountMultiple = count(DB::getQueryLog());

    expect($queryCountMultiple)->toBeLessThanOrEqual($queryCountSingle);
});
