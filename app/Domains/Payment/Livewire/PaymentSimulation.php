<?php

declare(strict_types=1);

namespace App\Domains\Payment\Livewire;

use App\Domains\Payment\Actions\ProcessPaymentResult;
use App\Domains\Payment\Enums\PaymentMethod;
use App\Domains\Payment\Enums\PaymentSimulationOutcome;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Exceptions\FreeSimulationOutcomeNotAllowedException;
use App\Domains\Payment\Models\Payment;
use App\Support\Exceptions\DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

final class PaymentSimulation extends Component
{
    public string $paymentId = '';

    public string $method = 'qris';

    public string $outcome = 'success';

    public string $idempotencyKey = '';

    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    public function mount(Payment $payment): void
    {
        $this->authorize('view', $payment);

        if ($payment->isExpired()) {
            abort(410, __('payment.errors.payment_expired'));
        }

        $this->paymentId = $payment->id;
        $this->method = $payment->method instanceof PaymentMethod ? $payment->method->value : PaymentMethod::Qris->value;
        $this->outcome = PaymentSimulationOutcome::Success->value;
        $this->idempotencyKey = (string) Str::uuid();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'outcome' => ['required', Rule::enum(PaymentSimulationOutcome::class)],
            'idempotencyKey' => ['required', 'string'],
        ];
    }

    public function pay(ProcessPaymentResult $processPaymentResult): void
    {
        /** @var Payment $payment */
        $payment = Payment::query()->findOrFail($this->paymentId);

        $this->authorize('pay', $payment);

        if ($payment->isExpired()) {
            abort(410, __('payment.errors.payment_expired'));
        }

        $this->validate();

        $isProduction = $this->isProductionEnvironment();

        // Di environment produksi, mode pilih hasil bebas ditolak di server.
        if ($isProduction) {
            if ($this->outcome !== PaymentSimulationOutcome::Success->value) {
                throw new FreeSimulationOutcomeNotAllowedException;
            }
            $outcomeEnum = PaymentSimulationOutcome::Success;
        } else {
            $outcomeEnum = PaymentSimulationOutcome::from($this->outcome);
        }

        try {
            $methodEnum = PaymentMethod::from($this->method);
            $newKey = $payment->status === PaymentStatus::Failed ? $this->idempotencyKey : null;

            $updatedPayment = $processPaymentResult->handle(
                $payment,
                $methodEnum,
                $outcomeEnum,
                $newKey,
            );

            $this->errorMessage = null;

            if ($updatedPayment->status === PaymentStatus::Success) {
                $this->successMessage = __('payment.messages.payment_successful');
            } elseif ($updatedPayment->status === PaymentStatus::Failed) {
                $this->errorMessage = __('payment.messages.payment_failed');
                $this->idempotencyKey = (string) Str::uuid();
            } else {
                $this->successMessage = __('payment.messages.payment_pending');
            }

            $this->dispatch('close-modal', 'confirm-payment');
        } catch (DomainException $e) {
            $this->dispatch('close-modal', 'confirm-payment');
            $this->errorMessage = $e->getMessage();
        }
    }

    public function retry(): void
    {
        $this->idempotencyKey = (string) Str::uuid();
        $this->errorMessage = null;
        $this->successMessage = null;
    }

    public function render(): View
    {
        /** @var Payment $payment */
        $payment = Payment::query()->findOrFail($this->paymentId);

        return view('payment::livewire.payment-simulation', [
            'payment' => $payment,
            'isProduction' => $this->isProductionEnvironment(),
            'methods' => PaymentMethod::cases(),
            'outcomes' => PaymentSimulationOutcome::cases(),
        ])->layout('components.layouts.app', ['title' => __('payment.title')]);
    }

    private function isProductionEnvironment(): bool
    {
        return app()->isProduction() || config('app.env') === 'production';
    }
}
