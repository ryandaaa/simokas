<div class="max-w-2xl mx-auto py-6 sm:py-8 px-4 sm:px-0">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-text">{{ __('payment.earnings.title') }}</h1>
        <p class="text-xs sm:text-sm text-muted mt-1">{{ __('payment.earnings.subtitle') }}</p>
    </div>

    <div wire:loading class="mb-6 space-y-3">
        <x-ui.skeleton variant="title" />
        <x-ui.skeleton variant="card" />
        <x-ui.skeleton variant="text" />
    </div>

    @if ($errorMessage)
        <x-ui.alert
            variant="danger"
            :title="__('payment.earnings.load_failed_title')"
            :message="$errorMessage"
            :retryLabel="__('payment.earnings.retry')"
            retryAction="reload"
        />
    @else
        <div class="mb-6">
            <x-ui.card :title="__('payment.earnings.balance')">
                <span class="text-2xl sm:text-3xl font-bold text-primary">
                    <x-ui.money :amount="$balance" />
                </span>
                <p class="text-xs text-muted mt-2">{{ __('payment.earnings.balance_hint') }}</p>
            </x-ui.card>
        </div>

        @if ($breakdown->isNotEmpty())
            <div class="mb-6">
                <x-ui.card :title="__('payment.earnings.breakdown')">
                    <ul class="divide-y divide-muted/15">
                        @foreach ($breakdown as $row)
                            <li class="flex items-center justify-between gap-3 py-3">
                                <div class="flex items-center gap-3">
                                    <x-ui.status-chip
                                        :label="$row->entry_type->label()"
                                        :variant="$row->entry_type->color()"
                                        :icon="$row->entry_type->icon()"
                                    />
                                    <span class="text-xs text-muted">{{ trans_choice('payment.earnings.entries_count', (int) $row->entries_count) }}</span>
                                </div>
                                <x-ui.money :amount="(int) $row->total_amount" />
                            </li>
                        @endforeach
                    </ul>
                </x-ui.card>
            </div>
        @endif

        <div>
            <h2 class="text-base font-semibold text-text mb-3">{{ __('payment.earnings.entries_title') }}</h2>

            @if ($entries !== null && $entries->count() > 0)
                <ul class="space-y-3">
                    @foreach ($entries as $entry)
                        <li>
                            <x-ui.card>
                                <div class="flex items-center justify-between gap-3">
                                    <div class="space-y-1.5">
                                        <x-ui.status-chip
                                            :label="$entry->entry_type->label()"
                                            :variant="$entry->entry_type->color()"
                                            :icon="$entry->entry_type->icon()"
                                        />
                                        <p class="text-xs text-muted">{{ $entry->payment?->purpose?->label() }}</p>
                                        <x-ui.datetime :value="$entry->created_at" class="text-xs" />
                                    </div>
                                    <x-ui.money :amount="(int) $entry->amount" />
                                </div>
                            </x-ui.card>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-4">
                    <x-ui.pagination :paginator="$entries" />
                </div>
            @else
                <x-ui.empty-state
                    icon="dokumen"
                    :title="__('payment.earnings.empty_title')"
                    :description="__('payment.earnings.empty_description')"
                />
            @endif
        </div>
    @endif
</div>
