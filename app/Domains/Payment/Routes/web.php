<?php

declare(strict_types=1);

use App\Domains\Payment\Http\Controllers\SimulatePaymentController;
use App\Domains\Payment\Livewire\PaymentSimulation;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'not_blocked'])->group(function () {
    Route::get('/payments/{payment}', PaymentSimulation::class)->name('payments.show');
    Route::post('/payments/simulate', SimulatePaymentController::class)->name('payments.simulate');
    Route::post('/api/v1/payments/simulate', SimulatePaymentController::class)->name('api.v1.payments.simulate');
});
