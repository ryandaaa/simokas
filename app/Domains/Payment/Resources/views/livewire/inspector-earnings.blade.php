<div class="ds-sans max-w-2xl mx-auto py-6 sm:py-8 px-4 sm:px-0 space-y-8 pb-12">
    <div class="space-y-2">
        <h1 class="text-3xl font-extrabold text-ds-ink tracking-tight">{{ __('payment.earnings.title') }}</h1>
        <p class="text-xs sm:text-sm text-ds-muted">{{ __('payment.earnings.subtitle') }}</p>
    </div>

    <div wire:loading class="space-y-3">
        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6 flex items-center justify-center gap-3 text-ds-muted">
            <i aria-hidden="true" class="fa-solid fa-circle-notch fa-spin"></i>
            <span class="text-sm font-medium">{{ __('ui.actions.loading') }}</span>
        </div>
    </div>

    @php
        $chip = function (?string $variant): string {
            return match ($variant) {
                'success' => 'bg-ds-green-soft text-ds-green-ink',
                'warning' => 'bg-ds-amber-soft text-ds-amber-ink',
                'danger' => 'bg-ds-red-soft text-ds-red-ink',
                'primary' => 'bg-ds-blue-soft text-ds-blue-ink',
                default => 'bg-ds-soft text-ds-muted',
            };
        };
    @endphp

    @if ($errorMessage)
        <div role="alert" class="p-4 rounded-2xl border border-ds-red-soft bg-ds-red-soft/40">
            <p class="text-xs font-extrabold text-ds-ink">{{ __('payment.earnings.load_failed_title') }}</p>
            <p class="mt-1 text-xs text-ds-muted">{{ $errorMessage }}</p>
            <div class="mt-3">
                <button type="button" wire:click="reload" class="text-sm font-bold text-ds-blue hover:text-ds-blue-ink">
                    {{ __('payment.earnings.retry') }}
                </button>
            </div>
        </div>
    @else
        <div>
            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                <h2 class="text-sm font-extrabold text-ds-ink">{{ __('payment.earnings.balance') }}</h2>
                <p class="text-2xl sm:text-3xl font-extrabold text-ds-ink mt-2">
                    {{ \App\Support\Money::format($balance) }}
                </p>
                <p class="text-xs text-ds-muted mt-2">{{ __('payment.earnings.balance_hint') }}</p>
            </div>
        </div>

        @if ($breakdown->isNotEmpty())
            <div>
                <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                    <h2 class="text-sm font-extrabold text-ds-ink">{{ __('payment.earnings.breakdown') }}</h2>
                    <ul class="divide-y divide-ds-line/60 mt-2">
                        @foreach ($breakdown as $row)
                            <li class="flex items-center justify-between gap-3 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="text-[10px] px-2 py-0.5 rounded-md font-bold {{ $chip($row->entry_type->color()) }}">
                                        {{ $row->entry_type->label() }}
                                    </span>
                                    <span class="text-xs text-ds-muted">{{ trans_choice('payment.earnings.entries_count', (int) $row->entries_count) }}</span>
                                </div>
                                <span class="text-sm font-extrabold text-ds-ink">{{ \App\Support\Money::format((int) $row->total_amount) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div>
            <h2 class="text-base font-extrabold text-ds-ink mb-3">{{ __('payment.earnings.entries_title') }}</h2>

            @if ($entries !== null && $entries->count() > 0)
                <ul class="space-y-3">
                    @foreach ($entries as $entry)
                        <li>
                            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-4 hover:shadow-md transition-all">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="space-y-1.5">
                                        <span class="text-[10px] px-2 py-0.5 rounded-md font-bold {{ $chip($entry->entry_type->color()) }}">
                                            {{ $entry->entry_type->label() }}
                                        </span>
                                        <p class="text-xs text-ds-muted">{{ $entry->payment?->purpose?->label() }}</p>
                                        <p class="text-xs text-ds-faint">{{ $entry->created_at?->translatedFormat('d M Y H:i') }}</p>
                                    </div>
                                    <span class="text-sm font-extrabold text-ds-ink">{{ \App\Support\Money::format((int) $entry->amount) }}</span>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-4">
                    <x-ui.pagination :paginator="$entries" />
                </div>
            @else
                <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-8 text-center max-w-2xl mx-auto">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-ds-soft text-ds-muted flex items-center justify-center text-xl mb-4">
                        <i aria-hidden="true" class="fa-solid fa-file-lines"></i>
                    </div>
                    <h3 class="text-lg font-extrabold text-ds-ink">{{ __('payment.earnings.empty_title') }}</h3>
                    <p class="text-xs text-ds-muted mt-2">{{ __('payment.earnings.empty_description') }}</p>
                </div>
            @endif
        </div>
    @endif
</div>
