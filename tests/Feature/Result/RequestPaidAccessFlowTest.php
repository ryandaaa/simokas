<?php

declare(strict_types=1);

namespace Tests\Feature\Result;

use App\Domains\Identity\Models\User;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Models\Payment;
use App\Domains\Result\Livewire\ViewResultPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Journeys\Concerns\HasCompletedInspectionSetup;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class, HasCompletedInspectionSetup::class);

it('RES-T50 the result page creates the access bill and redirects to payment (never a GET to the POST route)', function (): void {
    $state = $this->setupCompletedInspectionState();
    $buyer = User::factory()->user()->create();

    $component = Livewire::actingAs($buyer)
        ->test(ViewResultPage::class, ['listing' => $state['listing']])
        ->call('requestAccess');

    /** @var Payment $payment */
    $payment = Payment::query()
        ->where('purpose', PaymentPurpose::Access)
        ->where('payer_id', $buyer->id)
        ->where('ref_id', $state['result']->id)
        ->firstOrFail();

    expect($payment->amount)->toBe($state['job']->fee_amount);

    $component->assertRedirect(route('payments.show', ['payment' => $payment->id]));
});

it('RES-T51 requesting access does nothing for a viewer who already has access', function (): void {
    $state = $this->setupCompletedInspectionState();

    Livewire::actingAs($state['listing']->seller)
        ->test(ViewResultPage::class, ['listing' => $state['listing']])
        ->call('requestAccess')
        ->assertNoRedirect();

    expect(Payment::query()->where('purpose', PaymentPurpose::Access)->count())->toBe(0);
});

it('RES-T52 the access route accepts POST only, so no page may link to it with GET', function (): void {
    $state = $this->setupCompletedInspectionState();
    $buyer = User::factory()->user()->create();

    actingAs($buyer);

    get(route('results.access', ['listing' => $state['listing']->id]))->assertStatus(405);
});
