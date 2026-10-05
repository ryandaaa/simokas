<?php

declare(strict_types=1);

namespace App\Domains\Payment\Livewire;

use App\Domains\Identity\Models\User;
use App\Domains\Payment\Contracts\Ledger;
use App\Domains\Payment\Models\LedgerEntry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

final class InspectorEarnings extends Component
{
    use WithPagination;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->authorize('viewEarnings', LedgerEntry::class);
    }

    public function reload(): void
    {
        $this->errorMessage = null;
        $this->resetPage();
    }

    public function render(): View
    {
        $this->authorize('viewEarnings', LedgerEntry::class);

        /** @var User $user */
        $user = Auth::user();

        $balance = 0;
        /** @var Collection<int, object> $breakdown */
        $breakdown = new Collection;
        /** @var LengthAwarePaginator<int, LedgerEntry>|null $entries */
        $entries = null;

        try {
            $balance = app(Ledger::class)->balanceFor($user->id);

            $breakdown = LedgerEntry::query()
                ->where('account_user_id', $user->id)
                ->selectRaw('entry_type, COUNT(*) as entries_count, SUM(amount) as total_amount')
                ->groupBy('entry_type')
                ->get();

            /** @var LengthAwarePaginator<int, LedgerEntry> $entries */
            $entries = LedgerEntry::query()
                ->where('account_user_id', $user->id)
                ->with('payment')
                ->latest()
                ->paginate(12);
        } catch (Throwable $exception) {
            report($exception);

            $this->errorMessage = __('payment.earnings.load_failed');
        }

        return view('payment::livewire.inspector-earnings', [
            'balance' => $balance,
            'breakdown' => $breakdown,
            'entries' => $entries,
        ])->layout('components.layouts.app', ['title' => __('payment.earnings.title')]);
    }
}
