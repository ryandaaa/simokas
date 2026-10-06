<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Livewire;

use App\Domains\Transaction\Models\Transaction;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

/**
 * Paginated list of the current user's transactions (TRX-1).
 *
 * Shows transactions where the user is the buyer or the seller of the
 * listing, newest first. Eager-loads the listing to avoid N+1.
 */
final class TransactionsIndexPage extends Component
{
    use WithPagination;

    public ?string $errorMessage = null;

    public function retry(): void
    {
        $this->errorMessage = null;
        $this->resetPage();
    }

    public function render(): View
    {
        $transactions = null;

        try {
            $userId = Auth::id();

            $transactions = Transaction::query()
                ->where(function ($query) use ($userId): void {
                    $query->where('buyer_id', $userId)
                        ->orWhereHas('listing', function ($listingQuery) use ($userId): void {
                            $listingQuery->where('seller_id', $userId);
                        });
                })
                ->with('listing')
                ->latest()
                ->paginate(10);
        } catch (Throwable) {
            $this->errorMessage = __('transaction.index.load_failed');
        }

        return view('transaction::livewire.transactions-index-page', [
            'transactions' => $transactions,
        ])->layout('components.layouts.app', ['title' => __('transaction.index.title')]);
    }
}
