<div class="max-w-3xl mx-auto px-4 py-6 space-y-6">
    <div class="space-y-1">
        <h1 class="text-2xl sm:text-3xl font-bold text-text">
            {{ __('listing.edit.title') }}
        </h1>
        <p class="text-sm text-muted">
            {{ $listing->title }}
        </p>
    </div>

    <x-ui.card>
        <div class="space-y-4">
            <x-ui.alert variant="info" :message="__('listing.edit.deferred_notice')" />

            <div class="p-4 rounded-xl border border-muted/20 bg-bg space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs uppercase font-bold text-muted">Status Saat Ini</span>
                    <x-ui.status-chip
                        :label="$listing->status->label()"
                        :variant="$listing->status->color()"
                        :icon="$listing->status->icon()"
                    />
                </div>
                <div class="text-lg font-bold text-text">
                    {{ $listing->title }}
                </div>
                <div class="text-sm text-primary font-semibold">
                    <x-ui.money :amount="$listing->price" />
                </div>
            </div>

            <div class="flex justify-between items-center pt-2">
                <x-ui.button
                    :href="route('listings.mine')"
                    variant="secondary"
                    icon="panah-kiri"
                >
                    Kembali ke Listing Saya
                </x-ui.button>
            </div>
        </div>
    </x-ui.card>
</div>
