<div class="ds-sans mx-auto max-w-2xl px-4 py-6 sm:py-8 space-y-6 pb-12">
    <a href="{{ route('catalog.show', ['listing' => $listing->id]) }}" class="inline-flex items-center gap-1 text-xs font-bold text-ds-blue hover:text-ds-blue-ink">
        <i aria-hidden="true" class="fa-solid fa-chevron-left"></i>
        {{ __('catalog.detail.back_to_list') }}
    </a>

    <div class="space-y-2">
        <span class="text-ds-blue text-xs font-extrabold uppercase tracking-wider">{{ __('inspection.request.title') }}</span>
        <h1 class="text-3xl font-extrabold text-ds-ink tracking-tight">{{ __('inspection.request.title') }}</h1>
        <p class="text-sm text-ds-muted">{{ __('inspection.request.subtitle') }}</p>
    </div>

    <div wire:loading>
        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6 flex items-center justify-center gap-3 py-10 text-ds-muted">
            <i aria-hidden="true" class="fa-solid fa-circle-notch fa-spin"></i>
            <span class="text-sm font-medium">{{ __('ui.actions.loading') }}</span>
        </div>
    </div>

    <div wire:loading.remove class="space-y-4">
        @if ($errorMessage)
            <div role="alert" class="p-4 rounded-2xl border border-ds-red-soft bg-ds-red-soft/40 text-xs font-semibold text-ds-ink">
                {{ $errorMessage }}
                <div class="mt-2">
                    <button type="button" wire:click="$refresh" class="text-sm font-bold text-ds-blue hover:text-ds-blue-ink">
                        {{ __('inspection.mine.retry') }}
                    </button>
                </div>
            </div>
        @endif

        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
            <h2 class="text-lg font-extrabold text-ds-ink">{{ $listing->title }}</h2>
            <div class="mt-3 space-y-3 text-sm">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-ds-muted">{{ $listing->vehicle_type->label() }} • {{ $listing->year }} • {{ $listing->area_name }}</span>
                    @php
                        $statusPill = match ($listing->status->color()) {
                            'success' => 'bg-ds-green-soft text-ds-green-ink',
                            'warning' => 'bg-ds-amber-soft text-ds-amber-ink',
                            'danger' => 'bg-ds-red-soft text-ds-red-ink',
                            'primary' => 'bg-ds-blue-soft text-ds-blue-ink',
                            default => 'bg-ds-soft text-ds-muted',
                        };
                    @endphp
                    <span class="text-[10px] px-2 py-0.5 rounded-md font-bold {{ $statusPill }}">{{ $listing->status->label() }}</span>
                </div>
                <div class="flex items-baseline justify-between border-t border-ds-line/60 pt-3">
                    <span class="text-ds-muted">{{ __('inspection.request.fee_label') }}</span>
                    <span class="text-2xl font-extrabold text-ds-ink">{{ \App\Support\Money::format($fee) }}</span>
                </div>
                <div class="flex items-baseline justify-between">
                    <span class="text-ds-muted">{{ __('inspection.request.expires_label') }}</span>
                    <span class="font-bold text-ds-ink">30 menit</span>
                </div>
            </div>
        </div>

        <div class="p-4 rounded-2xl border border-ds-amber-soft bg-ds-amber-soft/40 flex items-start gap-3">
            <i aria-hidden="true" class="fa-solid fa-triangle-exclamation text-ds-amber-ink mt-0.5"></i>
            <div>
                <p class="text-xs font-extrabold text-ds-ink">{{ __('inspection.request.warning_title') }}</p>
                <p class="mt-1 text-xs text-ds-muted leading-relaxed">{{ __('inspection.request.warning') }}</p>
            </div>
        </div>

        @if ($existingJob && $existingBillId)
            <div class="p-4 rounded-2xl border border-ds-blue-soft bg-ds-blue-soft/40 text-xs font-semibold text-ds-ink">
                {{ __('inspection.request.existing_title') }}
            </div>
            <a href="{{ route('payments.show', ['payment' => $existingBillId]) }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md">
                {{ __('inspection.request.continue_to_payment') }}
                <i aria-hidden="true" class="fa-solid fa-arrow-right text-xs"></i>
            </a>
        @else
            <button
                type="button"
                @click="$dispatch('open-modal', 'confirm-request')"
                wire:loading.attr="disabled"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md disabled:opacity-60"
            >
                {{ __('inspection.request.submit') }}
                <i aria-hidden="true" class="fa-solid fa-arrow-right text-xs"></i>
            </button>
        @endif
    </div>

    <x-ui.modal
        name="confirm-request"
        :title="__('inspection.request.confirm_title')"
        :description="__('inspection.request.confirm_description')"
    >
        <div class="my-4 space-y-2 rounded-2xl border border-ds-line/80 bg-ds-canvas/60 p-4 text-sm">
            <div class="flex justify-between">
                <span class="text-ds-muted font-bold text-ds-ink">{{ $listing->title }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-ds-muted">{{ __('inspection.request.fee_label') }}:</span>
                <span class="font-extrabold text-ds-ink">{{ \App\Support\Money::format($fee) }}</span>
            </div>
        </div>

        <x-slot:footer>
            <x-ui.button
                type="button"
                variant="secondary"
                @click="open = false"
                wire:loading.attr="disabled"
            >
                {{ __('inspection.request.cancel') }}
            </x-ui.button>

            <x-ui.button
                type="button"
                variant="primary"
                wire:click="submit"
                wire:loading.attr="disabled"
                wire:target="submit"
            >
                <span wire:loading.remove wire:target="submit">
                    {{ __('inspection.request.confirm_yes') }}
                </span>
                <span wire:loading wire:target="submit">
                    {{ __('inspection.request.submitting') }}
                </span>
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
