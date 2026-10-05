<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Policies\PaymentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('allows payer to view their own payment', function () {
    $policy = new PaymentPolicy;
    $payer = User::factory()->create();
    $payment = Payment::factory()->forPayer($payer)->create();

    expect($policy->view($payer, $payment))->toBeTrue();
});

it('allows payer to pay their own payment', function () {
    $policy = new PaymentPolicy;
    $payer = User::factory()->create();
    $payment = Payment::factory()->forPayer($payer)->create();

    expect($policy->pay($payer, $payment))->toBeTrue();
});

it('forbids other users from viewing or paying someone elses payment', function () {
    $policy = new PaymentPolicy;
    $payer = User::factory()->create();
    $stranger = User::factory()->create();
    $payment = Payment::factory()->forPayer($payer)->create();

    expect($policy->view($stranger, $payment))->toBeFalse()
        ->and($policy->pay($stranger, $payment))->toBeFalse();
});

it('allows admin to view payment but only payer can pay', function () {
    $policy = new PaymentPolicy;
    $payer = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $payment = Payment::factory()->forPayer($payer)->create();

    expect($policy->view($admin, $payment))->toBeTrue()
        ->and($policy->pay($admin, $payment))->toBeFalse();
});
