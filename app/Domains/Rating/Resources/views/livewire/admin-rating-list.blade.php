<div class="ds-sans max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8 pb-12">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div class="space-y-2">
            <span class="text-ds-blue text-xs font-extrabold uppercase tracking-wider">{{ __('rating.admin.heading') }}</span>
            <h1 class="text-3xl font-extrabold text-ds-ink tracking-tight">
                {{ __('rating.admin.heading') }}
            </h1>
            <p class="text-sm text-ds-muted">
                {{ __('rating.admin.subheading') }}
            </p>
        </div>

        {{-- Filter Buttons --}}
        <div class="flex items-center gap-1 bg-ds-canvas p-1 rounded-full border border-ds-line/80">
            <button
                type="button"
                wire:click="setFilter('all')"
                aria-pressed="{{ $filter === 'all' ? 'true' : 'false' }}"
                class="px-4 py-1.5 min-h-[44px] inline-flex items-center text-sm font-bold rounded-full transition {{ $filter === 'all' ? 'bg-ds-ink text-white shadow-md' : 'text-ds-muted hover:text-ds-ink' }}"
            >
                {{ __('rating.admin.filter_all') }}
            </button>
            <button
                type="button"
                wire:click="setFilter('visible')"
                aria-pressed="{{ $filter === 'visible' ? 'true' : 'false' }}"
                class="px-4 py-1.5 min-h-[44px] inline-flex items-center text-sm font-bold rounded-full transition {{ $filter === 'visible' ? 'bg-ds-ink text-white shadow-md' : 'text-ds-muted hover:text-ds-ink' }}"
            >
                {{ __('rating.admin.filter_visible') }}
            </button>
            <button
                type="button"
                wire:click="setFilter('hidden')"
                aria-pressed="{{ $filter === 'hidden' ? 'true' : 'false' }}"
                class="px-4 py-1.5 min-h-[44px] inline-flex items-center text-sm font-bold rounded-full transition {{ $filter === 'hidden' ? 'bg-ds-ink text-white shadow-md' : 'text-ds-muted hover:text-ds-ink' }}"
            >
                {{ __('rating.admin.filter_hidden') }}
            </button>
        </div>
    </div>

    @if ($feedbackMessage)
        <div role="status" class="p-4 rounded-2xl border border-ds-green-soft bg-ds-green-soft/40 text-xs font-semibold text-ds-ink">
            {{ $feedbackMessage }}
        </div>
    @endif

    @if ($errorMessage)
        <div role="alert" class="p-4 rounded-2xl border border-ds-red-soft bg-ds-red-soft/40 text-xs font-semibold text-ds-ink">
            {{ $errorMessage }}
        </div>
    @endif

    @if ($ratings->isEmpty())
        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-12 text-center">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-ds-soft text-ds-muted flex items-center justify-center text-xl mb-4">
                <i aria-hidden="true" class="fa-solid fa-comments"></i>
            </div>
            <h3 class="text-lg font-extrabold text-ds-ink">{{ __('rating.admin.empty') }}</h3>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($ratings as $rating)
                <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="space-y-2 flex-1">
                            <div class="flex flex-wrap items-center gap-3">
                                {{-- Score badge --}}
                                <div class="flex items-center gap-1 bg-ds-yellow-soft px-3 py-1 rounded-full">
                                    <i aria-hidden="true" class="fa-solid fa-star text-ds-yellow-deep text-sm"></i>
                                    <span class="font-extrabold text-sm text-ds-ink">{{ $rating->score }} / 5</span>
                                </div>

                                {{-- Status chip --}}
                                @if ($rating->is_hidden)
                                    <span class="text-[10px] px-2 py-0.5 rounded-md font-bold bg-ds-red-soft text-ds-red-ink">
                                        {{ __('rating.admin.status_hidden') }}
                                    </span>
                                @else
                                    <span class="text-[10px] px-2 py-0.5 rounded-md font-bold bg-ds-green-soft text-ds-green-ink">
                                        {{ __('rating.admin.status_visible') }}
                                    </span>
                                @endif

                                <span class="text-xs text-ds-muted">
                                    {{ $rating->created_at?->translatedFormat('d M Y, H:i') }} {{ __('rating.admin.timezone') }}
                                </span>
                            </div>

                            <div class="text-sm text-ds-muted">
                                <span class="font-bold text-ds-ink">{{ __('rating.admin.inspector') }}:</span>
                                {{ $rating->inspector?->name ?? '-' }}
                                <span class="mx-2 text-ds-line">•</span>
                                <span class="font-bold text-ds-ink">{{ __('rating.admin.buyer') }}:</span>
                                {{ $rating->buyer?->name ?? '-' }}
                            </div>

                            @if ($rating->comment)
                                <p class="text-sm text-ds-ink bg-ds-canvas/60 p-3 rounded-xl border border-ds-line/60 italic whitespace-pre-wrap">
                                    "{{ $rating->comment }}"
                                </p>
                            @endif
                        </div>

                        {{-- Moderation action button --}}
                        <div class="flex items-center gap-2 pt-2 md:pt-0 border-t md:border-t-0 border-ds-line/60">
                            @if ($rating->is_hidden)
                                <button
                                    type="button"
                                    wire:click="unhide('{{ $rating->id }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="unhide('{{ $rating->id }}')"
                                    class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 min-h-[44px] bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs disabled:opacity-60"
                                >
                                    <span wire:loading.remove wire:target="unhide('{{ $rating->id }}')">
                                        {{ __('rating.admin.unhide') }}
                                    </span>
                                    <span wire:loading wire:target="unhide('{{ $rating->id }}')">...<span class="sr-only">{{ __("ui.actions.loading") }}</span></span>
                                </button>
                            @else
                                <button
                                    type="button"
                                    wire:click="hide('{{ $rating->id }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="hide('{{ $rating->id }}')"
                                    class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 min-h-[44px] bg-white border border-ds-red-soft text-ds-danger hover:bg-ds-red-soft/30 font-bold text-xs rounded-xl transition-all shadow-xs disabled:opacity-60"
                                >
                                    <span wire:loading.remove wire:target="hide('{{ $rating->id }}')">
                                        {{ __('rating.admin.hide') }}
                                    </span>
                                    <span wire:loading wire:target="hide('{{ $rating->id }}')">...<span class="sr-only">{{ __("ui.actions.loading") }}</span></span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="pt-4">
                {{ $ratings->links() }}
            </div>
        </div>
    @endif
</div>
