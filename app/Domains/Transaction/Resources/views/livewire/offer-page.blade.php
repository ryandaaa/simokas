<div class="mx-auto max-w-2xl px-4 py-6 sm:py-8">
    <x-ui.button :href="route('catalog.show', ['listing' => $listing->id])" variant="ghost" icon="chevron-left">
        {{ __('transaction.offer.back_to_listing') }}
    </x-ui.button>

    <div class="mt-4">
        <h1 class="text-xl font-bold text-text sm:text-2xl">{{ __('transaction.offer.title') }}</h1>
        <p class="mt-1 text-sm text-muted">{{ __('transaction.offer.subtitle') }}</p>
    </div>

    <div wire:loading class="mt-4">
        <x-ui.skeleton variant="card" />
    </div>

    <div wire:loading.remove class="mt-4 space-y-4">
        @if ($errorMessage)
            <x-ui.alert variant="danger" :message="$errorMessage" />
        @endif

        <x-ui.card :title="$listing->title">
            <div class="space-y-3 text-sm">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-muted">{{ $listing->vehicle_type->label() }} • {{ $listing->year }} • {{ $listing->area_name }}</span>
                    <x-ui.status-chip :label="$listing->status->label()" :variant="$listing->status->color()" :icon="$listing->status->icon()" />
                </div>
                <div class="flex items-baseline justify-between border-t border-muted/15 pt-3">
                    <span class="text-muted">{{ __('transaction.offer.listing_price') }}</span>
                    <span class="text-2xl font-bold text-primary"><x-ui.money :amount="$listing->price" /></span>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <form wire:submit.prevent="submit" class="space-y-4">
                <x-ui.input
                    name="priceAmount"
                    :label="__('transaction.offer.price_label')"
                    :hint="__('transaction.offer.price_hint')"
                    :error="$errors->first('priceAmount')"
                    required
                    inputmode="numeric"
                    wire:model="priceAmount"
                />

                <x-ui.textarea
                    name="notes"
                    :label="__('transaction.offer.notes_label')"
                    :hint="__('transaction.offer.notes_hint')"
                    :error="$errors->first('notes')"
                    :rows="3"
                    wire:model="notes"
                />

                <x-ui.button
                    type="submit"
                    variant="primary"
                    class="w-full sm:w-auto"
                    wire:loading.attr="disabled"
                    wire:target="submit"
                >
                    <span wire:loading.remove wire:target="submit">
                        {{ __('transaction.offer.submit') }}
                    </span>
                    <span wire:loading wire:target="submit">
                        {{ __('transaction.offer.submitting') }}
                    </span>
                </x-ui.button>
            </form>
        </x-ui.card>
    </div>
</div>
