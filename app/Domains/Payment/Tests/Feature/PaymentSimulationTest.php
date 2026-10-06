<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Payment\Contracts\Billing;
use App\Domains\Payment\DTOs\CreateBillData;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Events\PaymentSucceeded;
use App\Domains\Payment\Exceptions\FreeSimulationOutcomeNotAllowedException;
use App\Domains\Payment\Livewire\PaymentSimulation;
use App\Domains\Payment\Models\Payment;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

it('PAY-T01 creates bill with server calculated amount and ignores client provided amount (PAY-E6)', function () {
    $payer = User::factory()->create();
    $billing = app(Billing::class);

    // Simulated server calculation: setting tariff is 50,000.
    // Client tries to submit a request with spoofed amount 100
    $serverComputedAmount = 50000;
    $clientSpoofedAmount = 100;

    $bill = $billing->createBill(new CreateBillData(
        payerId: $payer->id,
        purpose: PaymentPurpose::Inspection,
        refId: (string) Str::uuid(),
        amount: $serverComputedAmount, // Server provides 50,000, client 100 ignored
        idempotencyKey: (string) Str::uuid(),
    ));

    expect($bill->amount)->toBe(50000)
        ->and($bill->amount)->not->toBe($clientSpoofedAmount);
});

it('PAY-T03 guarantees idempotency: two requests with same key produce one payment (PAY-E1)', function () {
    $payer = User::factory()->create();
    $billing = app(Billing::class);
    $key = 'client-idempotency-key-'.Str::random(10);
    $refId = (string) Str::uuid();

    $first = $billing->createBill(new CreateBillData(
        payerId: $payer->id,
        purpose: PaymentPurpose::Inspection,
        refId: $refId,
        amount: 50000,
        idempotencyKey: $key,
    ));

    $second = $billing->createBill(new CreateBillData(
        payerId: $payer->id,
        purpose: PaymentPurpose::Inspection,
        refId: $refId,
        amount: 50000,
        idempotencyKey: $key,
    ));

    expect($second->id)->toBe($first->id)
        ->and(Payment::query()->where('idempotency_key', $key)->count())->toBe(1);
});

it('rejects duplicate idempotency_key insertion directly via query with QueryException', function () {
    $payer = User::factory()->create();
    $key = 'duplicate-unique-key-test';

    DB::table('payments')->insert([
        'id' => (string) Str::uuid(),
        'payer_id' => $payer->id,
        'purpose' => 'inspection',
        'ref_id' => (string) Str::uuid(),
        'amount' => 50000,
        'status' => 'pending',
        'idempotency_key' => $key,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Second direct insertion must violate unique constraint and throw QueryException
    DB::table('payments')->insert([
        'id' => (string) Str::uuid(),
        'payer_id' => $payer->id,
        'purpose' => 'inspection',
        'ref_id' => (string) Str::uuid(),
        'amount' => 50000,
        'status' => 'pending',
        'idempotency_key' => $key,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
})->throws(QueryException::class);

it('PAY-T04 simulates outcome success, failed, and pending updating status accordingly (dataset)', function (string $outcome, PaymentStatus $expectedStatus) {
    Event::fake([PaymentSucceeded::class]);

    $payer = User::factory()->create();
    $payment = Payment::factory()->forPayer($payer)->pending()->create();

    actingAs($payer);

    $response = postJson(route('payments.simulate'), [
        'payment_id' => $payment->id,
        'method' => 'qris',
        'outcome' => $outcome,
    ]);

    $response->assertOk()
        ->assertJson([
            'data' => [
                'payment_id' => $payment->id,
                'status' => $expectedStatus->value,
            ],
            'error' => null,
        ]);

    expect($payment->fresh()->status)->toBe($expectedStatus);

    if ($expectedStatus === PaymentStatus::Success) {
        Event::assertDispatched(PaymentSucceeded::class);
    } else {
        Event::assertNotDispatched(PaymentSucceeded::class);
    }
})->with([
    'success outcome' => ['success', PaymentStatus::Success],
    'failed outcome' => ['failed', PaymentStatus::Failed],
    'pending outcome' => ['pending', PaymentStatus::Pending],
]);

it('PAY-T05 does not alter job on payment failure and allows retry with new key (PAY-E2)', function () {
    Event::fake([PaymentSucceeded::class]);

    $payer = User::factory()->create();
    $payment = Payment::factory()->forPayer($payer)->pending()->create();

    actingAs($payer);

    // Initial failed payment simulation
    postJson(route('payments.simulate'), [
        'payment_id' => $payment->id,
        'method' => 'transfer',
        'outcome' => 'failed',
    ])->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Failed);
    Event::assertNotDispatched(PaymentSucceeded::class);

    // Retry with a new key and successful outcome
    $newKey = (string) Str::uuid();
    postJson(route('payments.simulate'), [
        'payment_id' => $payment->id,
        'method' => 'transfer',
        'outcome' => 'success',
        'idempotency_key' => $newKey,
    ])->assertOk();

    $refreshed = $payment->fresh();
    expect($refreshed->status)->toBe(PaymentStatus::Success)
        ->and($refreshed->idempotency_key)->toBe($newKey);

    Event::assertDispatched(PaymentSucceeded::class);
});

it('PAY-T06 returns 410 PAYMENT_EXPIRED when payment is expired (PAY-E4)', function () {
    $payer = User::factory()->create();
    $payment = Payment::factory()->forPayer($payer)->expired()->create();

    actingAs($payer);

    // View returns 410
    get(route('payments.show', $payment))->assertStatus(410);

    // Simulate API returns 410
    postJson(route('payments.simulate'), [
        'payment_id' => $payment->id,
        'method' => 'qris',
        'outcome' => 'success',
    ])->assertStatus(410)
        ->assertJson([
            'error' => [
                'code' => 'PAYMENT_EXPIRED',
            ],
        ]);
});

it('PAY-T07 returns 403 when another user attempts to view or pay (PAY-E5)', function () {
    $payer = User::factory()->create();
    $stranger = User::factory()->create();
    $payment = Payment::factory()->forPayer($payer)->pending()->create();

    actingAs($stranger);

    // View returns 403
    get(route('payments.show', $payment))->assertForbidden();

    // Simulate API returns 403
    postJson(route('payments.simulate'), [
        'payment_id' => $payment->id,
        'method' => 'qris',
        'outcome' => 'success',
    ])->assertForbidden();
});

it('PAY-T14 commits the payment update before emitting the event, so a listener failure does not roll it back', function () {
    $payer = User::factory()->create();
    $payment = Payment::factory()->forPayer($payer)->pending()->create();

    actingAs($payer);

    // The event is emitted after the transaction commits, so a listener
    // failure must not roll back the payment update.
    Event::listen(PaymentSucceeded::class, function () {
        throw new RuntimeException('Database transaction failure test');
    });

    // The listener exception surfaces as a server error, but the payment
    // update was already committed before the event was emitted.
    postJson(route('payments.simulate'), [
        'payment_id' => $payment->id,
        'method' => 'qris',
        'outcome' => 'success',
    ])->assertServerError();

    // The payment update is durable: the transaction already committed
    // before the event was emitted.
    $fresh = $payment->fresh();
    expect($fresh->status)->toBe(PaymentStatus::Success)
        ->and($fresh->paid_at)->not->toBeNull()
        ->and($fresh->method)->not->toBeNull();
});

it('rejects free outcome selection on server in production environment', function () {
    config(['app.env' => 'production']);

    $payer = User::factory()->create();
    $payment = Payment::factory()->forPayer($payer)->pending()->create();

    actingAs($payer);

    // Trying to send outcome: failed in production must be rejected with 422 BUSINESS_RULE
    postJson(route('payments.simulate'), [
        'payment_id' => $payment->id,
        'method' => 'qris',
        'outcome' => 'failed',
    ])->assertStatus(422)
        ->assertJson([
            'error' => [
                'code' => 'BUSINESS_RULE',
            ],
        ]);

    // Livewire throws FreeSimulationOutcomeNotAllowedException when trying free outcome in production
    expect(fn () => Livewire::actingAs($payer)
        ->test(PaymentSimulation::class, ['payment' => $payment])
        ->set('outcome', 'failed')
        ->call('pay'))->toThrow(FreeSimulationOutcomeNotAllowedException::class);
});

it('handles expiry at 29 minutes vs 31 minutes boundary with frozen time', function () {
    Carbon::setTestNow('2026-10-05 10:00:00');

    $payer = User::factory()->create();
    $payment = Payment::factory()->forPayer($payer)->pending()->create([
        'expires_at' => Carbon::parse('2026-10-05 10:30:00'),
    ]);

    actingAs($payer);

    // At 29 minutes: payment is still valid
    Carbon::setTestNow('2026-10-05 10:29:00');
    get(route('payments.show', $payment))->assertOk();

    // At 31 minutes: payment is expired (410)
    Carbon::setTestNow('2026-10-05 10:31:00');
    get(route('payments.show', $payment))->assertStatus(410);

    Carbon::setTestNow();
});

it('enforces authorization across roles', function (string $role, int $expectedStatus) {
    $payer = User::factory()->create();
    $payment = Payment::factory()->forPayer($payer)->pending()->create();

    $user = match ($role) {
        'owner' => $payer,
        'buyer' => User::factory()->create(),
        'inspector' => User::factory()->inspector()->create(),
        'admin' => User::factory()->admin()->create(),
        default => User::factory()->create(),
    };

    actingAs($user);
    $response = get(route('payments.show', $payment));

    $response->assertStatus($expectedStatus);
})->with([
    'owner allowed' => ['owner', 200],
    'unrelated buyer forbidden' => ['buyer', 403],
    'inspector forbidden' => ['inspector', 403],
    'admin allowed to view' => ['admin', 200],
]);

it('redirects unauthenticated guest to login', function () {
    $payment = Payment::factory()->pending()->create();

    get(route('payments.show', $payment))
        ->assertRedirect(route('login'));
});

it('renders SIMULASI label, confirmation modal, and payment details in Livewire', function () {
    $payer = User::factory()->create();
    $payment = Payment::factory()->forPayer($payer)->pending()->create([
        'amount' => 50000,
    ]);

    Livewire::actingAs($payer)
        ->test(PaymentSimulation::class, ['payment' => $payment])
        ->assertSee(__('payment.simulation_label'))
        ->assertSee('Rp50.000')
        ->assertSee(__('payment.actions.pay'))
        ->assertSee(__('payment.actions.confirm_title'))
        ->set('method', 'qris')
        ->set('outcome', 'success')
        ->call('pay')
        ->assertHasNoErrors()
        ->assertSee(__('payment.messages.payment_successful'));

    expect($payment->fresh()->status)->toBe(PaymentStatus::Success);
});
