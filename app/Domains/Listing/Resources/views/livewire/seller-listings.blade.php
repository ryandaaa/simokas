<div class="ds-sans max-w-6xl mx-auto px-4 py-6 space-y-8 pb-12">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div class="space-y-2">
            <h1 class="text-3xl font-extrabold text-ds-ink tracking-tight">
                {{ __('listing.mine.title') }}
            </h1>
            <p class="text-sm text-ds-muted">
                {{ __('listing.mine.subtitle') }}
            </p>
        </div>

        <div>
            <a href="{{ route('listings.create') }}"
                class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md">
                <i aria-hidden="true" class="fa-solid fa-plus text-xs"></i>
                {{ __('listing.mine.btn_create') }}
            </a>
        </div>
    </div>

    {{-- Flash Message --}}
    @if (session()->has('success'))
        <div role="status" class="p-4 rounded-2xl border border-ds-green-soft bg-ds-green-soft/40 text-xs font-semibold text-ds-ink">
            {{ session('success') }}
        </div>
    @endif

    @if ($successMessage)
        <div role="status" class="p-4 rounded-2xl border border-ds-green-soft bg-ds-green-soft/40 text-xs font-semibold text-ds-ink">
            {{ $successMessage }}
        </div>
    @endif

    @if ($errorMessage)
        <div role="alert" class="p-4 rounded-2xl border border-ds-red-soft bg-ds-red-soft/40 text-xs font-semibold text-ds-ink">
            {{ $errorMessage }}
        </div>
    @endif

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

    {{-- Content --}}
    @if ($listings->isEmpty())
        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-8 text-center max-w-2xl mx-auto">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-ds-soft text-ds-muted flex items-center justify-center text-xl mb-4">
                <i aria-hidden="true" class="fa-solid fa-file-lines"></i>
            </div>
            <h3 class="text-lg font-extrabold text-ds-ink">{{ __('listing.mine.empty_title') }}</h3>
            <p class="text-xs text-ds-muted mt-2">{{ __('listing.mine.empty_desc') }}</p>
            <div class="flex justify-center mt-6">
                <a href="{{ route('listings.create') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md">
                    <i aria-hidden="true" class="fa-solid fa-plus text-xs"></i>
                    {{ __('listing.mine.btn_create') }}
                </a>
            </div>
        </div>
    @else
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-6">
            @foreach ($listings as $listing)
                <article class="group bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col justify-between relative">
                    <div>
                        {{-- Photo Thumbnail --}}
                        <div class="relative aspect-16/10 overflow-hidden bg-ds-soft">
                            @if ($listing->coverPhoto !== null)
                                <img
                                    src="{{ route('listings.photos.show', ['listing' => $listing->id, 'photo' => $listing->coverPhoto->id]) }}"
                                    alt="{{ $listing->title }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                    width="400"
                                    height="225"
                                    loading="lazy"
                                />
                            @else
                                <div class="w-full h-full flex items-center justify-center text-ds-faint text-3xl">
                                    <i aria-hidden="true" class="fa-solid fa-camera"></i>
                                </div>
                            @endif

                            {{-- Status Chip --}}
                            <div class="absolute top-2 right-2 sm:top-3 sm:right-3">
                                <span class="text-[9px] sm:text-[10px] px-2 sm:px-2.5 py-1 rounded-full font-bold uppercase tracking-wider {{ $chip($listing->status->color()) }}">
                                    {{ $listing->status->label() }}
                                </span>
                            </div>
                        </div>

                        {{-- Card Details --}}
                        <div class="p-3 sm:p-4 space-y-1.5 sm:space-y-2">
                            <p class="text-[10px] sm:text-[11px] uppercase font-bold tracking-wider text-ds-muted truncate">
                                {{ $listing->vehicle_type->label() }} &bull; {{ $listing->year }}
                            </p>
                            <h2 class="text-sm sm:text-base font-extrabold text-ds-ink truncate group-hover:text-ds-blue transition-colors" title="{{ $listing->title }}">
                                {{ $listing->title }}
                            </h2>
                            <p class="text-base sm:text-xl font-extrabold text-ds-ink truncate">
                                {{ \App\Support\Money::format($listing->price) }}
                            </p>
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] sm:text-xs text-ds-muted pt-0.5 sm:pt-1">
                                <span class="whitespace-nowrap">{{ number_format($listing->odometer_km, 0, ',', '.') }} km</span>
                                <span aria-hidden="true">&bull;</span>
                                <span class="whitespace-nowrap">{{ $listing->transmission->label() }}</span>
                                @if ($listing->area_name)
                                    <span aria-hidden="true">&bull;</span>
                                    <span class="truncate">{{ $listing->area_name }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Card Footer --}}
                    <div class="px-3 sm:px-4 py-2.5 sm:py-3 border-t border-ds-line/60 bg-ds-canvas/80 space-y-2">
                        @if ($listing->status->isRejected() && $listing->rejection_reason)
                            <div role="alert" class="p-3 rounded-xl border border-ds-red-soft bg-ds-red-soft/40">
                                <p class="text-xs font-extrabold text-ds-ink">{{ __('listing.mine.rejection_reason') }}</p>
                                <p class="mt-1 text-xs text-ds-muted">{{ $listing->rejection_reason }}</p>
                            </div>
                            <button
                                type="button"
                                wire:click="confirmResubmit('{{ $listing->id }}')"
                                wire:loading.attr="disabled"
                                class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 min-h-[44px] bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-xs rounded-xl transition-all shadow-md disabled:opacity-60"
                            >
                                {{ __('listing.mine.resubmit') }}
                            </button>
                        @endif
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[11px] sm:text-xs text-ds-muted truncate">
                                {{ $listing->created_at?->diffForHumans() }}
                            </span>

                            <a
                                href="{{ route('listings.edit', $listing) }}"
                                class="min-h-[44px] shrink-0 inline-flex items-center gap-1 text-[11px] sm:text-xs font-bold text-ds-blue hover:text-ds-blue-ink"
                            >
                                <span>{{ __('listing.mine.edit') }}</span>
                                <i aria-hidden="true" class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="pt-4">
            <x-ui.pagination :paginator="$listings" />
        </div>
    @endif

    {{-- Modal Konfirmasi Kirim Ulang --}}
    @if ($confirmingResubmitId)
        <x-ui.modal
            name="modal-resubmit-listing"
            :show="true"
            :title="__('listing.mine.resubmit_modal_title')"
            :description="__('listing.mine.resubmit_modal_description')"
        >
            <div class="p-2 text-sm text-text">
                <p>{{ __('listing.mine.resubmit_modal_description') }}</p>
            </div>

            <x-slot:footer>
                <x-ui.button
                    type="button"
                    variant="secondary"
                    wire:click="cancelResubmit"
                >
                    {{ __('listing.mine.cancel') }}
                </x-ui.button>
                <x-ui.button
                    type="button"
                    variant="primary"
                    wire:click="resubmit"
                    wire:loading.attr="disabled"
                    wire:target="resubmit"
                >
                    <span wire:loading.remove wire:target="resubmit">{{ __('listing.mine.confirm_resubmit') }}</span>
                    <span wire:loading wire:target="resubmit">{{ __('listing.mine.processing') }}</span>
                </x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>
