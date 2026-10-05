<?php

declare(strict_types=1);

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Payment\Contracts\Ledger;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Livewire\InspectorEarnings;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

function makeApprovedInspector(): User
{
    $user = User::factory()->create([
        'role' => UserRole::Inspector,
        'status' => UserStatus::Active,
    ]);

    InspectorProfile::factory()->approved()->create(['user_id' => $user->id]);

    return $user;
}

function seedEarnings(User $inspector): void
{
    $ledger = app(Ledger::class);

    $access = Payment::factory()->success()->create([
        'purpose' => PaymentPurpose::Access,
        'amount' => 15000,
    ]);
    $inspection = Payment::factory()->success()->create([
        'purpose' => PaymentPurpose::Inspection,
        'amount' => 50000,
    ]);

    $ledger->postAccessPayout($access->id, $inspector->id);
    $ledger->postInspectionPayout($inspection->id, $inspector->id, 10);
}

it('PAY-T12 shows inspector balance and per-source breakdown on the earnings page', function () {
    $inspector = makeApprovedInspector();
    seedEarnings($inspector);

    actingAs($inspector);

    get(route('inspector.earnings'))->assertOk();

    Livewire::actingAs($inspector)
        ->test(InspectorEarnings::class)
        ->assertSee(__('payment.earnings.title'))
        ->assertSee('Rp58.500')
        ->assertSee(__('payment.ledger.types.access_fee'))
        ->assertSee(__('payment.ledger.types.inspection_fee'))
        ->assertSee('Rp13.500')
        ->assertSee('Rp45.000');
});

it('shows only own entries and hides other inspectors earnings', function () {
    $inspector = makeApprovedInspector();
    $other = makeApprovedInspector();
    seedEarnings($inspector);

    $otherPayment = Payment::factory()->success()->create([
        'purpose' => PaymentPurpose::Inspection,
        'amount' => 150000,
    ]);
    app(Ledger::class)->postInspectionPayout($otherPayment->id, $other->id, 10);

    Livewire::actingAs($inspector)
        ->test(InspectorEarnings::class)
        ->assertSee('Rp58.500')
        ->assertDontSee('Rp135.000');
});

it('shows an empty state when the inspector has no earnings yet', function () {
    $inspector = makeApprovedInspector();

    Livewire::actingAs($inspector)
        ->test(InspectorEarnings::class)
        ->assertSee(__('payment.earnings.empty_title'))
        ->assertSee('Rp0');
});

it('renders loading and error states with a retry action', function () {
    $inspector = makeApprovedInspector();
    seedEarnings($inspector);

    Livewire::actingAs($inspector)
        ->test(InspectorEarnings::class)
        ->assertSee('wire:loading', false);

    $mock = Mockery::mock(Ledger::class);
    $mock->shouldReceive('balanceFor')->andThrow(new RuntimeException('ledger unavailable'));
    app()->instance(Ledger::class, $mock);

    $component = Livewire::actingAs($inspector)->test(InspectorEarnings::class);

    $component->assertSee(__('payment.earnings.load_failed'))
        ->assertSee(__('payment.earnings.retry'));

    app()->forgetInstance(Ledger::class);

    $component->call('reload')
        ->assertSee('Rp58.500')
        ->assertDontSee(__('payment.earnings.load_failed'));
});

it('paginates the earnings entries', function () {
    $inspector = makeApprovedInspector();

    for ($i = 1; $i <= 13; $i++) {
        LedgerEntry::factory()->forInspector($inspector)->create([
            'amount' => 13000 + $i,
            'created_at' => now()->addSeconds($i),
            'updated_at' => now()->addSeconds($i),
        ]);
    }

    Livewire::actingAs($inspector)
        ->test(InspectorEarnings::class)
        ->assertSee('Rp13.013')
        ->assertDontSee('Rp13.001')
        ->call('gotoPage', 2)
        ->assertSee('Rp13.001');
});

it('forbids earnings page for every role except approved inspectors', function (string $role, VerificationStatus|string|null $status, int $expected) {
    if ($role === 'guest') {
        get(route('inspector.earnings'))->assertRedirect(route('login'));

        return;
    }

    $user = User::factory()->create([
        'role' => $role === 'admin' ? UserRole::Admin : ($role === 'inspector' ? UserRole::Inspector : UserRole::User),
        'status' => UserStatus::Active,
    ]);

    if ($status instanceof VerificationStatus) {
        InspectorProfile::factory()->create([
            'user_id' => $user->id,
            'verification_status' => $status,
        ]);
    }

    actingAs($user);
    get(route('inspector.earnings'))->assertStatus($expected);

    Livewire::actingAs($user)
        ->test(InspectorEarnings::class)
        ->assertForbidden();
})->with([
    'guest redirects to login' => ['guest', null, 302],
    'regular user forbidden' => ['user', null, 403],
    'admin forbidden' => ['admin', null, 403],
    'pending inspector forbidden' => ['inspector', VerificationStatus::Pending, 403],
    'rejected inspector forbidden' => ['inspector', VerificationStatus::Rejected, 403],
    'inspector without profile forbidden' => ['inspector', null, 403],
]);

it('allows an approved inspector to open the earnings page', function () {
    $inspector = makeApprovedInspector();

    actingAs($inspector);
    get(route('inspector.earnings'))->assertOk();

    Livewire::actingAs($inspector)
        ->test(InspectorEarnings::class)
        ->assertSee(__('payment.earnings.title'));
});
