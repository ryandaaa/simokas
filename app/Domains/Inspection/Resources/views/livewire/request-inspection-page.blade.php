<div class="mx-auto max-w-2xl px-4 py-6 sm:py-8">
    <x-ui.button :href="route('catalog.show', ['listing' => $listing->id])" variant="ghost" icon="chevron-left">
        {{ __('catalog.detail.back_to_list') }}
    </x-ui.button>

    <div class="mt-4">
        <h1 class="text-xl font-bold text-text sm:text-2xl">{{ __('inspection.request.title') }}</h1>
        <p class="mt-1 text-sm text-muted">{{ __('inspection.request.subtitle') }}</p>
    </div>

    <div wire:loading class="mt-4">
        <x-ui.skeleton variant="card" />
    </div>

    <div wire:loading.remove class="mt-4 space-y-4">
        @if ($errorMessage)
            <x-ui.alert variant="danger" :message="$errorMessage" :retryAction="'$refresh'" :retryLabel="__('inspection.mine.retry')" />
        @endif

        <x-ui.card :title="$listing->title">
            <div class="space-y-3 text-sm">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-muted">{{ $listing->vehicle_type->label() }} • {{ $listing->year }} • {{ $listing->area_name }}</span>
                    <x-ui.status-chip :label="$listing->status->label()" :variant="$listing->status->color()" :icon="$listing->status->icon()" />
                </div>
                <div class="flex items-baseline justify-between border-t border-muted/15 pt-3">
                    <span class="text-muted">{{ __('inspection.request.fee_label') }}</span>
                    <span class="text-2xl font-bold text-primary"><x-ui.money :amount="$fee" /></span>
                </div>
                <div class="flex items-baseline justify-between">
                    <span class="text-muted">{{ __('inspection.request.expires_label') }}</span>
                    <span class="font-medium text-text">30 menit</span>
                </div>
            </div>
        </x-ui.card>

        <x-ui.alert variant="warning" :title="__('inspection.request.warning_title')" :message="__('inspection.request.warning')" />

        @if ($existingJob && $existingBillId)
            <x-ui.alert variant="info" :title="__('inspection.request.existing_title')" />
            <x-ui.button :href="route('payments.show', ['payment' => $existingBillId])" variant="primary" class="w-full sm:w-auto">
                {{ __('inspection.request.continue_to_payment') }}
            </x-ui.button>
        @else
            <x-ui.button
                type="button"
                variant="primary"
                class="w-full sm:w-auto"
                @click="$dispatch('open-modal', 'confirm-request')"
                wire:loading.attr="disabled"
            >
                {{ __('inspection.request.submit') }}
            </x-ui.button>
        @endif
    </div>

    <x-ui.modal
        name="confirm-request"
        :title="__('inspection.request.confirm_title')"
        :description="__('inspection.request.confirm_description')"
    >
        <div class="my-4 space-y-2 rounded-lg border border-muted/15 bg-bg p-4 text-sm">
            <div class="flex justify-between">
                <span class="text-muted">{{ $listing->title }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-muted">{{ __('inspection.request.fee_label') }}:</span>
                <span class="font-bold text-primary"><x-ui.money :amount="$fee" /></span>
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
