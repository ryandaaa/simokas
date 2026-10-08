<div class="ds-sans mx-auto max-w-2xl px-4 py-6 sm:py-8 space-y-6 pb-12">
    <a href="{{ route('catalog.show', ['listing' => $listing->id]) }}" class="min-h-[44px] inline-flex items-center gap-1 text-xs font-bold text-ds-blue hover:text-ds-blue-ink">
        <i aria-hidden="true" class="fa-solid fa-chevron-left"></i>
        {{ __('transaction.offer.back_to_listing') }}
    </a>

    <div class="space-y-2">
        <h1 class="text-3xl font-extrabold text-ds-ink tracking-tight">{{ __('transaction.offer.title') }}</h1>
        <p class="text-sm text-ds-muted">{{ __('transaction.offer.subtitle') }}</p>
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
            </div>
        @endif

        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
            <h2 class="text-lg font-extrabold text-ds-ink">{{ $listing->title }}</h2>
            <div class="space-y-3 text-sm mt-3">
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
                    <span class="text-ds-muted">{{ __('transaction.offer.listing_price') }}</span>
                    <span class="text-2xl font-extrabold text-ds-ink">{{ \App\Support\Money::format($listing->price) }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
            <form wire:submit.prevent="submit" class="space-y-4">
                <div class="w-full">
                    <label for="priceAmount" class="block text-xs font-bold text-ds-ink mb-1.5">
                        {{ __('transaction.offer.price_label') }}
                        <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                        <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                    </label>
                    <input type="text" id="priceAmount" name="priceAmount" inputmode="numeric" wire:model="priceAmount" required
                        class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                    <p class="text-xs text-ds-faint mt-1.5">{{ __('transaction.offer.price_hint') }}</p>
                    @error('priceAmount')
                        <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="w-full">
                    <label for="notes" class="block text-xs font-bold text-ds-ink mb-1.5">
                        {{ __('transaction.offer.notes_label') }}
                    </label>
                    <textarea id="notes" name="notes" wire:model="notes" rows="3"
                        class="w-full px-4 py-3 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors"></textarea>
                    <p class="text-xs text-ds-faint mt-1.5">{{ __('transaction.offer.notes_hint') }}</p>
                    @error('notes')
                        <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="submit"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="submit">
                        {{ __('transaction.offer.submit') }}
                    </span>
                    <span wire:loading wire:target="submit">
                        {{ __('transaction.offer.submitting') }}
                    </span>
                </button>
            </form>
        </div>
    </div>
</div>
