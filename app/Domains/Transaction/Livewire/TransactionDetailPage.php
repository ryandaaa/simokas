<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Livewire;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Setting\Contracts\SettingsReader;
use App\Domains\Transaction\Actions\AcceptOffer;
use App\Domains\Transaction\Actions\ApproveCancellationRequest;
use App\Domains\Transaction\Actions\CancelTransaction;
use App\Domains\Transaction\Actions\ConfirmHandover;
use App\Domains\Transaction\Actions\OpenTransactionPayment;
use App\Domains\Transaction\Actions\RejectOffer;
use App\Domains\Transaction\Actions\RequestTransactionCancellation;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Support\PurchaseBreakdown;
use App\Support\Exceptions\DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Transaction detail page (TRX-1, TRX-2).
 *
 * Buyer and seller see the offer state. The seller sees the payout
 * breakdown (K2, using the transaction's copied numbers) and can accept
 * or reject an offered transaction through confirmation modals.
 * The buyer can open the payment bill once accepted (TRX-2) and confirm
 * the handover once the funds are held; an admin can confirm as well.
 */
final class TransactionDetailPage extends Component
{
    public string $transactionId = '';

    public ?string $errorMessage = null;

    public ?string $actionSuccess = null;

    public ?string $cancelReason = null;

    public function mount(Transaction $transaction): void
    {
        Gate::authorize('view', $transaction);

        $this->transactionId = (string) $transaction->getKey();
    }

    public function accept(AcceptOffer $action): void
    {
        /** @var Transaction $transaction */
        $transaction = Transaction::query()->findOrFail($this->transactionId);

        Gate::authorize('accept', $transaction);

        /** @var User $seller */
        $seller = Auth::user();

        try {
            $action->handle($seller, $transaction);
            $this->actionSuccess = __('transaction.detail.accept_success');
            $this->errorMessage = null;
        } catch (DomainException $e) {
            $this->errorMessage = $e->getMessage();
            $this->actionSuccess = null;
        }
    }

    public function reject(RejectOffer $action): void
    {
        /** @var Transaction $transaction */
        $transaction = Transaction::query()->findOrFail($this->transactionId);

        Gate::authorize('reject', $transaction);

        /** @var User $seller */
        $seller = Auth::user();

        try {
            $action->handle($seller, $transaction);
            $this->actionSuccess = __('transaction.detail.reject_success');
            $this->errorMessage = null;
        } catch (DomainException $e) {
            $this->errorMessage = $e->getMessage();
            $this->actionSuccess = null;
        }
    }

    public function pay(OpenTransactionPayment $action)
    {
        /** @var Transaction $transaction */
        $transaction = Transaction::query()->findOrFail($this->transactionId);

        Gate::authorize('pay', $transaction);

        /** @var User $buyer */
        $buyer = Auth::user();

        try {
            $bill = $action->handle($buyer, $transaction);
            $this->errorMessage = null;
        } catch (DomainException $e) {
            $this->errorMessage = $e->getMessage();
            $this->actionSuccess = null;

            return null;
        }

        return redirect()->route('payments.show', ['payment' => $bill->id]);
    }

    public function confirmHandover(ConfirmHandover $action): void
    {
        /** @var Transaction $transaction */
        $transaction = Transaction::query()->findOrFail($this->transactionId);

        Gate::authorize('confirmHandover', $transaction);

        /** @var User $user */
        $user = Auth::user();

        try {
            $action->handle($user, $transaction);
            $this->actionSuccess = __('transaction.detail.handover_success');
            $this->errorMessage = null;
        } catch (DomainException $e) {
            $this->errorMessage = $e->getMessage();
            $this->actionSuccess = null;
        }
    }

    public function cancel(CancelTransaction $action): void
    {
        /** @var Transaction $transaction */
        $transaction = Transaction::query()->findOrFail($this->transactionId);

        Gate::authorize('cancel', $transaction);

        /** @var User $user */
        $user = Auth::user();

        try {
            $action->handle($user, $transaction, $this->cancelReason);
            $this->actionSuccess = __('transaction.detail.cancel_success');
            $this->errorMessage = null;
            $this->cancelReason = null;
        } catch (DomainException $e) {
            $this->errorMessage = $e->getMessage();
            $this->actionSuccess = null;
        }
    }

    public function requestCancellation(RequestTransactionCancellation $action): void
    {
        /** @var Transaction $transaction */
        $transaction = Transaction::query()->findOrFail($this->transactionId);

        Gate::authorize('requestCancellation', $transaction);

        /** @var User $user */
        $user = Auth::user();

        try {
            $action->handle($user, $transaction);
            $this->actionSuccess = __('transaction.detail.cancel_request_success');
            $this->errorMessage = null;
        } catch (DomainException $e) {
            $this->errorMessage = $e->getMessage();
            $this->actionSuccess = null;
        }
    }

    public function approveCancellation(ApproveCancellationRequest $action): void
    {
        /** @var Transaction $transaction */
        $transaction = Transaction::query()->findOrFail($this->transactionId);

        Gate::authorize('approveCancellation', $transaction);

        /** @var User $user */
        $user = Auth::user();

        try {
            $action->handle($user, $transaction, (string) $this->cancelReason);
            $this->actionSuccess = __('transaction.detail.cancel_success');
            $this->errorMessage = null;
            $this->cancelReason = null;
        } catch (DomainException $e) {
            $this->errorMessage = $e->getMessage();
            $this->actionSuccess = null;
        }
    }

    public function render(): View
    {
        /** @var Transaction $transaction */
        $transaction = Transaction::query()->with('listing')->findOrFail($this->transactionId);

        Gate::authorize('view', $transaction);

        /** @var User $viewer */
        $viewer = Auth::user();

        $isSeller = (string) $transaction->listing->seller_id === (string) $viewer->getKey();
        $isBuyer = (string) $transaction->buyer_id === (string) $viewer->getKey();
        $isAdmin = Gate::allows('admin.access');

        $breakdown = $this->sellerBreakdown($transaction);

        $canPay = $isBuyer && $transaction->status === TransactionStatus::Accepted;
        $payBlockedByJob = $canPay && InspectionJob::query()
            ->where('listing_id', $transaction->listing_id)
            ->active()
            ->exists();
        $canConfirmHandover = ($isBuyer || $isAdmin) && $transaction->status === TransactionStatus::PaidHeld;

        $canCancel = Gate::allows('cancel', $transaction);
        $canRequestCancellation = $isBuyer
            && $transaction->status === TransactionStatus::PaidHeld
            && $transaction->cancel_requested_at === null
            && Gate::allows('requestCancellation', $transaction);
        $hasCancelRequest = $transaction->status === TransactionStatus::PaidHeld
            && $transaction->cancel_requested_at !== null;
        $canApproveCancellation = $isSeller
            && $hasCancelRequest
            && Gate::allows('approveCancellation', $transaction);
        $showNeedsAttention = $transaction->needs_attention_at !== null
            && ($isBuyer || $isSeller || $isAdmin);

        return view('transaction::livewire.transaction-detail-page', [
            'transaction' => $transaction,
            'isSeller' => $isSeller,
            'isBuyer' => $isBuyer,
            'isAdmin' => $isAdmin,
            'breakdown' => $breakdown,
            'canPay' => $canPay,
            'payBlockedByJob' => $payBlockedByJob,
            'canConfirmHandover' => $canConfirmHandover,
            'canCancel' => $canCancel,
            'canRequestCancellation' => $canRequestCancellation,
            'hasCancelRequest' => $hasCancelRequest,
            'canApproveCancellation' => $canApproveCancellation,
            'showNeedsAttention' => $showNeedsAttention,
        ])->layout('components.layouts.app', ['title' => __('transaction.detail.title')]);
    }

    /**
     * Seller payout breakdown (K2) from the transaction's copied numbers.
     *
     * @return array{price: int, commissionPct: int|null, commission: int|null, sellerNet: int}
     */
    private function sellerBreakdown(Transaction $transaction): array
    {
        if ($transaction->commission_pct === null) {
            return [
                'price' => $transaction->price_amount,
                'commissionPct' => null,
                'commission' => null,
                'sellerNet' => $transaction->price_amount,
            ];
        }

        /** @var SettingsReader $settings */
        $settings = app(SettingsReader::class);

        $breakdown = PurchaseBreakdown::calculate(
            (int) $transaction->price_amount,
            (int) $transaction->commission_pct,
            $settings->getPlatformFeePct(),
        );

        return [
            'price' => $transaction->price_amount,
            'commissionPct' => $transaction->commission_pct,
            'commission' => $breakdown['commission'],
            'sellerNet' => $breakdown['seller'],
        ];
    }
}
