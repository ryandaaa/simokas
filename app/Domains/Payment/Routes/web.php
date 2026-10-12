<?php

declare(strict_types=1);

use App\Domains\Payment\Http\Controllers\SimulatePaymentController;
use App\Domains\Payment\Livewire\InspectorEarnings;
use App\Domains\Payment\Livewire\PaymentSimulation;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'not_blocked'])->group(function () {
    Route::get('/payments/{payment}', PaymentSimulation::class)->name('payments.show');
    Route::post('/payments/simulate', SimulatePaymentController::class)->middleware('throttle:20,1')->name('payments.simulate');
    Route::post('/api/v1/payments/simulate', SimulatePaymentController::class)->middleware('throttle:20,1')->name('api.v1.payments.simulate');

    Route::get('/inspector/earnings', InspectorEarnings::class)
        ->middleware(['role:inspector', 'verified_inspector'])
        ->name('inspector.earnings');
});
