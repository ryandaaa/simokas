<div class="mx-auto max-w-2xl px-4 py-6 sm:py-8">
    <h1 class="text-xl font-bold text-text sm:text-2xl">{{ __('inspection.mine.title') }}</h1>
    <p class="mt-1 text-sm text-muted">{{ __('inspection.mine.subtitle') }}</p>

    <div wire:loading class="mt-4 space-y-3">
        <x-ui.skeleton variant="card" />
        <x-ui.skeleton variant="card" />
    </div>

    <div wire:loading.remove class="mt-4">
        @if ($errorMessage)
            <x-ui.alert variant="danger" :message="$errorMessage" />
            <div class="mt-3">
                <x-ui.button type="button" variant="secondary" wire:click="retry" wire:loading.attr="disabled">
                    {{ __('inspection.mine.retry') }}
                </x-ui.button>
            </div>
        @elseif ($jobs === null)
            <x-ui.alert variant="danger" :message="__('inspection.mine.load_failed')" />
            <div class="mt-3">
                <x-ui.button type="button" variant="secondary" wire:click="retry" wire:loading.attr="disabled">
                    {{ __('inspection.mine.retry') }}
                </x-ui.button>
            </div>
        @elseif ($jobs->isEmpty())
            <x-ui.empty-state
                icon="dokumen"
                :title="__('inspection.mine.empty_title')"
                :description="__('inspection.mine.empty_desc')"
                :actionLabel="__('inspection.mine.browse')"
                :actionUrl="route('catalog.index')"
            />
        @else
            <div class="space-y-3">
                @foreach ($jobs as $job)
                    <x-ui.card>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-text">{{ $job->listing?->title ?? $job->listing_id }}</p>
                                <p class="mt-0.5 text-xs text-muted">
                                    <x-ui.money :amount="$job->fee_amount" />
                                    •
                                    <x-ui.datetime :value="$job->created_at" format="date" />
                                </p>
                            </div>
                            <x-ui.status-chip :label="$job->status->label()" :variant="$job->status->color()" :icon="$job->status->icon()" />
                        </div>
                    </x-ui.card>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $jobs->links() }}
            </div>
        @endif
    </div>
</div>
