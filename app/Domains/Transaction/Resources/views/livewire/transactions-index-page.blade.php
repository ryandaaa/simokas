<div class="ds-sans mx-auto max-w-3xl px-4 py-6 sm:py-8 space-y-6 pb-12">
    <div class="space-y-2">
        <span class="text-ds-blue text-xs font-extrabold uppercase tracking-wider">{{ __('transaction.index.title') }}</span>
        <h1 class="text-3xl font-extrabold text-ds-ink tracking-tight">{{ __('transaction.index.title') }}</h1>
        <p class="text-sm text-ds-muted">{{ __('transaction.index.subtitle') }}</p>
    </div>

    <div wire:loading class="space-y-4">
        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6 flex items-center justify-center gap-3 py-10 text-ds-muted">
            <i aria-hidden="true" class="fa-solid fa-circle-notch fa-spin"></i>
            <span class="text-sm font-medium">{{ __('ui.actions.loading') }}</span>
        </div>
    </div>

    <div wire:loading.remove>
        @if ($errorMessage)
            <div role="alert" class="p-4 rounded-2xl border border-ds-red-soft bg-ds-red-soft/40 text-xs font-semibold text-ds-ink">
                {{ $errorMessage }}
                <div class="mt-2">
                    <button type="button" wire:click="retry" class="text-sm font-bold text-ds-blue hover:text-ds-blue-ink">
                        {{ __('ui.actions.retry') }}
                    </button>
                </div>
            </div>
        @elseif ($transactions === null)
            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6 flex items-center justify-center gap-3 py-10 text-ds-muted">
                <i aria-hidden="true" class="fa-solid fa-circle-notch fa-spin"></i>
                <span class="text-sm font-medium">{{ __('ui.actions.loading') }}</span>
            </div>
        @elseif ($transactions->isEmpty())
            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-8 text-center max-w-2xl mx-auto">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-ds-soft text-ds-muted flex items-center justify-center text-xl mb-4">
                    <i aria-hidden="true" class="fa-solid fa-file-lines"></i>
                </div>
                <h3 class="text-lg font-extrabold text-ds-ink">{{ __('transaction.index.empty_title') }}</h3>
                <p class="text-xs text-ds-muted mt-2">{{ __('transaction.index.empty_description') }}</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($transactions as $transaction)
                    @php
                        $isBuyer = (string) $transaction->buyer_id === (string) auth()->id();
                        $statusPill = match ($transaction->status->color()) {
                            'success' => 'bg-ds-green-soft text-ds-green-ink',
                            'warning' => 'bg-ds-amber-soft text-ds-amber-ink',
                            'danger' => 'bg-ds-red-soft text-ds-red-ink',
                            'primary' => 'bg-ds-blue-soft text-ds-blue-ink',
                            default => 'bg-ds-soft text-ds-muted',
                        };
                    @endphp
                    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-md transition-all p-4 sm:p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-extrabold text-ds-ink truncate">{{ $transaction->listing->title }}</p>
                                <p class="mt-0.5 text-xs text-ds-muted">
                                    {{ $isBuyer ? __('transaction.index.role_buyer') : __('transaction.index.role_seller') }}
                                </p>
                                <div class="mt-2 flex items-center gap-2">
                                    <span class="text-[10px] px-2 py-0.5 rounded-md font-bold {{ $statusPill }}">
                                        {{ $transaction->status->label() }}
                                    </span>
                                    <span class="text-sm font-extrabold text-ds-ink">
                                        {{ \App\Support\Money::format($transaction->price_amount) }}
                                    </span>
                                </div>
                            </div>
                            <a href="{{ route('transactions.show', ['transaction' => $transaction->id]) }}"
                                class="shrink-0 inline-flex items-center justify-center px-4 py-1.5 min-h-[44px] text-sm font-bold bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink rounded-xl transition-all shadow-xs">
                                {{ __('transaction.index.view_detail') }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>
