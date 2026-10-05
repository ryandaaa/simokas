<div class="max-w-6xl mx-auto px-4 py-6 space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-text">
                {{ __('listing.mine.title') }}
            </h1>
            <p class="text-sm text-muted">
                {{ __('listing.mine.subtitle') }}
            </p>
        </div>

        <div>
            <x-ui.button
                :href="route('listings.create')"
                variant="primary"
                icon="plus"
            >
                {{ __('listing.mine.btn_create') }}
            </x-ui.button>
        </div>
    </div>

    {{-- Flash Message --}}
    @if (session()->has('success'))
        <x-ui.alert variant="success" :message="session('success')" dismissible />
    @endif

    @if ($successMessage)
        <x-ui.alert variant="success" :message="$successMessage" />
    @endif

    @if ($errorMessage)
        <x-ui.alert variant="danger" :message="$errorMessage" />
    @endif

    {{-- Content --}}
    @if ($listings->isEmpty())
        <x-ui.card>
            <x-ui.empty-state
                icon="dokumen"
                :title="__('listing.mine.empty_title')"
                :description="__('listing.mine.empty_desc')"
                :actionLabel="__('listing.mine.btn_create')"
                :actionUrl="route('listings.create')"
                actionIcon="plus"
            />
        </x-ui.card>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($listings as $listing)
                <div class="rounded-xl border border-muted/20 bg-surface overflow-hidden shadow-sm flex flex-col justify-between hover:shadow-md transition">
                    <div>
                        {{-- Photo Thumbnail --}}
                        <div class="aspect-16/9 bg-muted/10 relative overflow-hidden">
                            @if ($listing->coverPhoto !== null)
                                <img
                                    src="{{ route('listings.photos.show', ['listing' => $listing->id, 'photo' => $listing->coverPhoto->id]) }}"
                                    alt="{{ $listing->title }}"
                                    class="w-full h-full object-cover"
                                    loading="lazy"
                                />
                            @else
                                <div class="w-full h-full flex items-center justify-center text-muted">
                                    <x-ui.icon name="kamera" class="w-10 h-10" />
                                </div>
                            @endif

                            {{-- Status Chip --}}
                            <div class="absolute top-2 right-2">
                                <x-ui.status-chip
                                    :label="$listing->status->label()"
                                    :variant="$listing->status->color()"
                                    :icon="$listing->status->icon()"
                                />
                            </div>
                        </div>

                        {{-- Card Details --}}
                        <div class="p-4 space-y-2">
                            <span class="text-xs uppercase font-bold tracking-wider text-muted">
                                {{ $listing->vehicle_type->label() }} &bull; {{ $listing->year }}
                            </span>
                            <h2 class="text-base font-bold text-text line-clamp-1" title="{{ $listing->title }}">
                                {{ $listing->title }}
                            </h2>
                            <div class="text-lg font-bold text-primary">
                                <x-ui.money :amount="$listing->price" />
                            </div>
                            <div class="flex items-center gap-3 text-xs text-muted pt-1">
                                <span>{{ number_format($listing->odometer_km, 0, ',', '.') }} km</span>
                                <span>&bull;</span>
                                <span>{{ $listing->transmission->label() }}</span>
                                @if ($listing->area_name)
                                    <span>&bull;</span>
                                    <span>{{ $listing->area_name }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Card Footer --}}
                    <div class="px-4 py-3 border-t border-muted/10 bg-bg/50 space-y-2">
                        @if ($listing->status->isRejected() && $listing->rejection_reason)
                            <x-ui.alert
                                variant="danger"
                                :title="__('listing.mine.rejection_reason')"
                                :message="$listing->rejection_reason"
                            />
                            <x-ui.button
                                type="button"
                                variant="primary"
                                wire:click="confirmResubmit('{{ $listing->id }}')"
                                wire:loading.attr="disabled"
                            >
                                {{ __('listing.mine.resubmit') }}
                            </x-ui.button>
                        @endif
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-muted">
                                {{ $listing->created_at?->diffForHumans() }}
                            </span>

                            <a
                                href="{{ route('listings.edit', $listing) }}"
                                class="text-xs font-semibold text-primary hover:underline inline-flex items-center gap-1"
                            >
                                <span>{{ __('listing.mine.edit') }}</span>
                                <x-ui.icon name="panah-kanan" class="w-3.5 h-3.5" />
                            </a>
                        </div>
                    </div>
                </div>
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
