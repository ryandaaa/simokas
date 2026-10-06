<div class="mx-auto max-w-3xl px-4 py-6 sm:py-8">
    <div class="mt-4">
        <h1 class="text-xl font-bold text-text sm:text-2xl">{{ __('transaction.index.title') }}</h1>
        <p class="mt-1 text-sm text-muted">{{ __('transaction.index.subtitle') }}</p>
    </div>

    <div wire:loading class="mt-4 space-y-4">
        <x-ui.skeleton variant="card" />
        <x-ui.skeleton variant="card" />
    </div>

    <div wire:loading.remove class="mt-4">
        @if ($errorMessage)
            <x-ui.alert variant="danger" :message="$errorMessage" :retryAction="'retry'" :retryLabel="__('ui.actions.retry')" />
        @elseif ($transactions === null)
            <x-ui.skeleton variant="card" />
        @elseif ($transactions->isEmpty())
            <x-ui.empty-state
                icon="dokumen"
                :title="__('transaction.index.empty_title')"
                :description="__('transaction.index.empty_description')"
            />
        @else
            <div class="space-y-3">
                @foreach ($transactions as $transaction)
                    @php
                        $isBuyer = (string) $transaction->buyer_id === (string) auth()->id();
                    @endphp
                    <x-ui.card>
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-bold text-obs truncate">{{ $transaction->listing->title }}</p>
                                <p class="mt-0.5 text-xs text-muted">
                                    {{ $isBuyer ? __('transaction.index.role_buyer') : __('transaction.index.role_seller') }}
                                </p>
                                <div class="mt-2 flex items-center gap-2">
                                    <x-ui.status-chip
                                        :label="$transaction->status->label()"
                                        :variant="$transaction->status->color()"
                                        :icon="$transaction->status->icon()"
                                    />
                                    <x-ui.money :amount="$transaction->price_amount" />
                                </div>
                            </div>
                            <x-ui.button
                                :href="route('transactions.show', ['transaction' => $transaction->id])"
                                variant="secondary"
                                class="!min-h-[36px] !px-4 !py-1.5 !text-sm shrink-0"
                            >
                                {{ __('transaction.index.view_detail') }}
                            </x-ui.button>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>
