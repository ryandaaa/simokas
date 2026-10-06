<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-obs tracking-tight">
                {{ __('rating.admin.heading') }}
            </h1>
            <p class="text-sm text-charcoal mt-1">
                {{ __('rating.admin.subheading') }}
            </p>
        </div>

        {{-- Filter Buttons --}}
        <div class="flex items-center gap-2 bg-fog/40 p-1 rounded-full border border-fog">
            <button
                type="button"
                wire:click="setFilter('all')"
                class="px-4 py-1.5 text-sm font-medium rounded-full transition {{ $filter === 'all' ? 'bg-paper text-obs shadow-sm' : 'text-charcoal hover:text-obs' }}"
            >
                {{ __('rating.admin.filter_all') }}
            </button>
            <button
                type="button"
                wire:click="setFilter('visible')"
                class="px-4 py-1.5 text-sm font-medium rounded-full transition {{ $filter === 'visible' ? 'bg-paper text-obs shadow-sm' : 'text-charcoal hover:text-obs' }}"
            >
                {{ __('rating.admin.filter_visible') }}
            </button>
            <button
                type="button"
                wire:click="setFilter('hidden')"
                class="px-4 py-1.5 text-sm font-medium rounded-full transition {{ $filter === 'hidden' ? 'bg-paper text-obs shadow-sm' : 'text-charcoal hover:text-obs' }}"
            >
                {{ __('rating.admin.filter_hidden') }}
            </button>
        </div>
    </div>

    @if ($feedbackMessage)
        <x-ui.alert variant="success" :message="$feedbackMessage" />
    @endif

    @if ($errorMessage)
        <x-ui.alert variant="danger" :message="$errorMessage" />
    @endif

    @if ($ratings->isEmpty())
        <x-ui.card class="p-12 text-center">
            <x-ui.empty-state
                icon="chat-bubble-bottom-center-text"
                :title="__('rating.admin.empty')"
                description=""
            />
        </x-ui.card>
    @else
        <div class="space-y-4">
            @foreach ($ratings as $rating)
                <x-ui.card class="p-6">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="space-y-2 flex-1">
                            <div class="flex flex-wrap items-center gap-3">
                                {{-- Score badge --}}
                                <div class="flex items-center gap-1 text-warning bg-linen/50 px-3 py-1 rounded-full border border-fog">
                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    <span class="font-bold text-sm text-obs">{{ $rating->score }} / 5</span>
                                </div>

                                {{-- Status chip --}}
                                @if ($rating->is_hidden)
                                    <x-ui.status-chip
                                        :label="__('rating.admin.status_hidden')"
                                        color="danger"
                                    />
                                @else
                                    <x-ui.status-chip
                                        :label="__('rating.admin.status_visible')"
                                        color="success"
                                    />
                                @endif

                                <span class="text-xs text-muted">
                                    {{ $rating->created_at?->format('d M Y, H:i') }} WIB
                                </span>
                            </div>

                            <div class="text-sm text-charcoal">
                                <span class="font-medium text-obs">{{ __('rating.admin.inspector') }}:</span>
                                {{ $rating->inspector?->name ?? '-' }}
                                <span class="mx-2 text-fog">•</span>
                                <span class="font-medium text-obs">{{ __('rating.admin.buyer') }}:</span>
                                {{ $rating->buyer?->name ?? '-' }}
                            </div>

                            @if ($rating->comment)
                                <p class="text-sm text-charcoal bg-paper/60 p-3 rounded-lg border border-fog italic whitespace-pre-wrap">
                                    "{{ $rating->comment }}"
                                </p>
                            @endif
                        </div>

                        {{-- Moderation action button --}}
                        <div class="flex items-center gap-2 pt-2 md:pt-0 border-t md:border-t-0 border-fog">
                            @if ($rating->is_hidden)
                                <x-ui.button
                                    type="button"
                                    variant="secondary"
                                    wire:click="unhide('{{ $rating->id }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="unhide('{{ $rating->id }}')"
                                    class="w-full md:w-auto"
                                >
                                    <span wire:loading.remove wire:target="unhide('{{ $rating->id }}')">
                                        {{ __('rating.admin.unhide') }}
                                    </span>
                                    <span wire:loading wire:target="unhide('{{ $rating->id }}')">
                                        ...
                                    </span>
                                </x-ui.button>
                            @else
                                <x-ui.button
                                    type="button"
                                    variant="danger"
                                    wire:click="hide('{{ $rating->id }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="hide('{{ $rating->id }}')"
                                    class="w-full md:w-auto"
                                >
                                    <span wire:loading.remove wire:target="hide('{{ $rating->id }}')">
                                        {{ __('rating.admin.hide') }}
                                    </span>
                                    <span wire:loading wire:target="hide('{{ $rating->id }}')">
                                        ...
                                    </span>
                                </x-ui.button>
                            @endif
                        </div>
                    </div>
                </x-ui.card>
            @endforeach

            <div class="pt-4">
                {{ $ratings->links() }}
            </div>
        </div>
    @endif
</div>
