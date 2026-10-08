<div class="ds-sans max-w-6xl mx-auto py-6 space-y-8 pb-12">
    {{-- Page Header --}}
    <div class="space-y-2">
        <span class="text-ds-blue text-xs font-extrabold uppercase tracking-wider">{{ __('admin.listing_queue.title') }}</span>
        <h1 class="text-3xl font-extrabold text-ds-ink tracking-tight">
            {{ __('admin.listing_queue.title') }}
        </h1>
        <p class="text-sm text-ds-muted">
            {{ __('admin.listing_queue.subtitle') }}
        </p>
    </div>

    {{-- Alert Messages --}}
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

    {{-- Detail Listing View (data privat hanya untuk admin) --}}
    @if ($selectedDetail)
        <div class="bg-white rounded-2xl border-2 border-ds-blue/30 shadow-xs p-6">
            <h2 class="text-lg font-extrabold text-ds-ink">{{ __('admin.listing_queue.detail_title', ['title' => $selectedDetail->title]) }}</h2>
            <div class="space-y-6 mt-4">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-[10px] px-2.5 py-1 rounded-full font-bold uppercase tracking-wider bg-ds-amber-soft text-ds-amber-ink">
                        <i aria-hidden="true" class="fa-regular fa-clock mr-1"></i>
                        {{ __('listing.status.pending_review') }}
                    </span>
                    <span class="text-xs text-ds-muted">
                        {{ __('admin.listing_queue.seller') }}: {{ $selectedDetail->sellerName }}
                    </span>
                    <span class="text-xs text-ds-muted">&bull;</span>
                    <span class="text-xs text-ds-muted">
                        {{ __('admin.listing_queue.waiting_since') }}:
                        <x-ui.datetime :value="$selectedDetail->createdAt" />
                    </span>
                </div>

                {{-- Data Kendaraan --}}
                <div>
                    <h3 class="text-base font-extrabold text-ds-ink mb-3">
                        {{ __('admin.listing_queue.vehicle_data') }}
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 p-4 rounded-2xl bg-ds-canvas/60 border border-ds-line/60">
                        <div>
                            <span class="text-xs text-ds-muted block">{{ $selectedDetail->brand }} {{ $selectedDetail->model }}</span>
                            <span class="text-sm font-bold text-ds-ink">{{ $selectedDetail->year }} &bull; {{ $selectedDetail->odometerKm }} km</span>
                        </div>
                        <div>
                            <span class="text-xs text-ds-muted block">{{ __('listing.fields.price') }}</span>
                            <span class="text-sm font-bold text-ds-ink">{{ \App\Support\Money::format($selectedDetail->price) }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-ds-muted block">{{ __('listing.fields.color') }}</span>
                            <span class="text-sm font-bold text-ds-ink">{{ $selectedDetail->color }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-ds-muted block">{{ __('listing.fields.area_name') }}</span>
                            <span class="text-sm font-bold text-ds-ink">{{ $selectedDetail->areaName ?? '-' }}</span>
                        </div>
                    </div>
                    @if ($selectedDetail->description)
                        <p class="text-sm text-ds-ink mt-3 leading-relaxed">{{ $selectedDetail->description }}</p>
                    @endif
                </div>

                {{-- Foto --}}
                <div>
                    <h3 class="text-base font-extrabold text-ds-ink mb-3">
                        {{ __('admin.listing_queue.photos') }} ({{ count($selectedDetail->photos) }})
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach ($selectedDetail->photos as $photo)
                            <a href="{{ $photo['url'] }}" target="_blank" rel="noopener noreferrer" class="block rounded-2xl overflow-hidden border border-ds-line/80 group">
                                <img
                                    src="{{ $photo['url'] }}"
                                    alt="{{ $selectedDetail->title }}"
                                    class="w-full aspect-16/10 object-cover group-hover:scale-105 transition-transform duration-500"
                                    width="400"
                                    height="225"
                                    loading="lazy"
                                />
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Lokasi & Tempat Inspeksi (privat) --}}
                <div>
                    <h3 class="text-base font-extrabold text-ds-ink mb-3">
                        {{ __('admin.listing_queue.location') }}
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-2xl bg-ds-canvas/60 border border-ds-line/60 text-sm">
                        <div>
                            <span class="text-xs text-ds-muted block">{{ __('listing.fields.address_full') }}</span>
                            <span class="font-bold text-ds-ink">{{ $selectedDetail->addressFull }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-ds-muted block">{{ __('listing.fields.inspection_place') }}</span>
                            <span class="font-bold text-ds-ink">{{ $selectedDetail->inspectionPlace }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-ds-muted block">{{ __('listing.fields.lat') }}, {{ __('listing.fields.lng') }}</span>
                            <span class="font-bold text-ds-ink">{{ $selectedDetail->lat }}, {{ $selectedDetail->lng }}</span>
                        </div>
                    </div>
                </div>

                {{-- Opsi Waktu Inspeksi --}}
                <div>
                    <h3 class="text-base font-extrabold text-ds-ink mb-3">
                        {{ __('admin.listing_queue.inspection_slots') }} ({{ count($selectedDetail->slots) }})
                    </h3>
                    <ul class="divide-y divide-ds-line/60 border border-ds-line/80 rounded-2xl overflow-hidden bg-white">
                        @foreach ($selectedDetail->slots as $slot)
                            <li class="p-3 text-sm text-ds-ink flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3">
                                <span class="font-bold">{{ $slot['slot_start'] }}</span>
                                @if ($slot['note'])
                                    <span class="text-xs text-ds-muted">{{ $slot['note'] }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Action Bar --}}
                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 pt-4 border-t border-ds-line/60">
                    <button
                        type="button"
                        wire:click="closeDetail"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs"
                    >
                        {{ __('admin.listing_queue.close_detail') }}
                    </button>

                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            wire:click="confirmReject('{{ $selectedDetail->id }}')"
                            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-ds-red-soft text-ds-danger hover:bg-ds-red-soft/30 font-bold text-xs rounded-xl transition-all shadow-xs"
                        >
                            <i aria-hidden="true" class="fa-solid fa-xmark text-xs mr-1.5"></i>
                            {{ __('admin.listing_queue.reject') }}
                        </button>

                        <button
                            type="button"
                            wire:click="confirmApprove('{{ $selectedDetail->id }}')"
                            class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md"
                        >
                            <i aria-hidden="true" class="fa-solid fa-check text-xs mr-1.5"></i>
                            {{ __('admin.listing_queue.approve') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Validation Queue List --}}
    @if ($queue->isEmpty())
        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-8 text-center max-w-2xl mx-auto">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-ds-green-soft text-ds-green-ink flex items-center justify-center text-xl mb-4">
                <i aria-hidden="true" class="fa-solid fa-check"></i>
            </div>
            <h3 class="text-lg font-extrabold text-ds-ink">{{ __('admin.listing_queue.empty_title') }}</h3>
            <p class="text-xs text-ds-muted mt-2">{{ __('admin.listing_queue.empty_description') }}</p>
        </div>
    @else
        <div class="space-y-4">
            <h2 class="text-xl font-extrabold text-ds-ink">
                {{ __('admin.listing_queue.title') }} ({{ $queue->total() }})
            </h2>

            <div wire:loading class="text-sm text-ds-muted">
                {{ __('admin.listing_queue.processing') }}
            </div>

            <div class="grid grid-cols-1 gap-4">
                @foreach ($queue as $item)
                    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-md transition-all p-4 sm:p-5">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="flex gap-4">
                                @if ($item->coverUrl)
                                    <img
                                        src="{{ $item->coverUrl }}"
                                        alt="{{ $item->title }}"
                                        class="w-24 h-18 object-cover rounded-xl shrink-0 aspect-4/3"
                                        width="96"
                                        height="72"
                                        loading="lazy"
                                    />
                                @endif
                                <div class="space-y-2">
                                    <div class="flex items-center gap-3">
                                        <h3 class="text-base font-extrabold text-ds-ink">{{ $item->title }}</h3>
                                        <span class="text-[10px] px-2 py-0.5 rounded-md font-bold bg-ds-amber-soft text-ds-amber-ink uppercase tracking-wider">
                                            <i aria-hidden="true" class="fa-regular fa-clock mr-1"></i>
                                            {{ __('listing.status.pending_review') }}
                                        </span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-ds-muted">
                                        <span>{{ $item->brand }} {{ $item->model }} &bull; {{ $item->year }}</span>
                                        <span>&bull;</span>
                                        <span class="font-extrabold text-ds-ink">{{ \App\Support\Money::format($item->price) }}</span>
                                        @if ($item->areaName)
                                            <span>&bull;</span>
                                            <span>{{ $item->areaName }}</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-ds-muted">
                                        {{ $item->sellerName }} &bull;
                                        {{ __('admin.listing_queue.waiting_since') }}:
                                        <x-ui.datetime :value="$item->createdAt" />
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    wire:click="openDetail('{{ $item->id }}')"
                                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2 min-h-[44px] bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs"
                                >
                                    <i aria-hidden="true" class="fa-solid fa-eye text-xs mr-1.5"></i>
                                    {{ __('admin.listing_queue.view_detail') }}
                                </button>

                                <button
                                    type="button"
                                    wire:click="confirmReject('{{ $item->id }}')"
                                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2 min-h-[44px] bg-white border border-ds-red-soft text-ds-danger hover:bg-ds-red-soft/30 font-bold text-xs rounded-xl transition-all shadow-xs"
                                >
                                    {{ __('admin.listing_queue.reject') }}
                                </button>

                                <button
                                    type="button"
                                    wire:click="confirmApprove('{{ $item->id }}')"
                                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2 min-h-[44px] bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-xs rounded-xl transition-all shadow-md"
                                >
                                    {{ __('admin.listing_queue.approve') }}
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <x-ui.pagination :paginator="$queue" />
        </div>
    @endif

    {{-- Modal Konfirmasi Setujui --}}
    @if ($confirmingApproveId)
        <x-ui.modal
            name="modal-approve-listing"
            :show="true"
            :title="__('admin.listing_queue.approve_modal_title')"
            :description="__('admin.listing_queue.approve_modal_description')"
        >
            <div class="p-2 text-sm text-text">
                <p>{{ __('admin.listing_queue.approve_modal_description') }}</p>
            </div>

            <x-slot:footer>
                <x-ui.button
                    type="button"
                    variant="secondary"
                    wire:click="cancelApprove"
                >
                    {{ __('admin.listing_queue.cancel') }}
                </x-ui.button>
                <x-ui.button
                    type="button"
                    variant="primary"
                    wire:click="approve"
                    wire:loading.attr="disabled"
                    wire:target="approve"
                >
                    <span wire:loading.remove wire:target="approve">{{ __('admin.listing_queue.confirm_approve') }}</span>
                    <span wire:loading wire:target="approve">{{ __('admin.listing_queue.processing') }}</span>
                </x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif

    {{-- Modal Tolak dengan Alasan Wajib --}}
    @if ($confirmingRejectId)
        <x-ui.modal
            name="modal-reject-listing"
            :show="true"
            :title="__('admin.listing_queue.reject_modal_title')"
            :description="__('admin.listing_queue.reject_modal_description')"
        >
            <form wire:submit="reject" class="space-y-4">
                <div class="w-full">
                    <label for="rejection_reason_input" class="block text-xs font-bold text-ds-ink mb-1.5">
                        {{ __('admin.listing_queue.reason_label') }}
                        <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                        <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                    </label>
                    <textarea id="rejection_reason_input" wire:model="rejectionReason" rows="4" required
                        placeholder="{{ __('admin.listing_queue.reason_placeholder') }}"
                        class="w-full px-4 py-3 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors"></textarea>
                    @error('rejectionReason')
                        <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-4 border-t border-ds-line/60">
                    <button
                        type="button"
                        wire:click="cancelReject"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs"
                    >
                        {{ __('admin.listing_queue.cancel') }}
                    </button>
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="reject"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-ds-red-soft text-ds-danger hover:bg-ds-red-soft/30 font-bold text-xs rounded-xl transition-all shadow-xs disabled:opacity-60"
                    >
                        <span wire:loading.remove wire:target="reject">{{ __('admin.listing_queue.confirm_reject') }}</span>
                        <span wire:loading wire:target="reject">{{ __('admin.listing_queue.processing') }}</span>
                    </button>
                </div>
            </form>
        </x-ui.modal>
    @endif
</div>
