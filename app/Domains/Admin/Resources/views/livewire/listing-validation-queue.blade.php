<div class="max-w-6xl mx-auto py-6 space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-text">
                {{ __('admin.listing_queue.title') }}
            </h1>
            <p class="text-sm text-muted mt-1">
                {{ __('admin.listing_queue.subtitle') }}
            </p>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if ($successMessage)
        <x-ui.alert variant="success" :message="$successMessage" />
    @endif

    @if ($errorMessage)
        <x-ui.alert variant="danger" :message="$errorMessage" />
    @endif

    {{-- Detail Listing View (data privat hanya untuk admin) --}}
    @if ($selectedDetail)
        <x-ui.card
            :title="__('admin.listing_queue.detail_title', ['title' => $selectedDetail->title])"
            class="border-2 border-primary/30"
        >
            <div class="space-y-6">
                <div class="flex flex-wrap items-center gap-3">
                    <x-ui.status-chip
                        :label="__('listing.status.pending_review')"
                        variant="warning"
                        icon="jam"
                    />
                    <span class="text-xs text-muted">
                        {{ __('admin.listing_queue.seller') }}: {{ $selectedDetail->sellerName }}
                    </span>
                    <span class="text-xs text-muted">&bull;</span>
                    <span class="text-xs text-muted">
                        {{ __('admin.listing_queue.waiting_since') }}:
                        <x-ui.datetime :value="$selectedDetail->createdAt" />
                    </span>
                </div>

                {{-- Data Kendaraan --}}
                <div>
                    <h3 class="text-base font-semibold text-text mb-3">
                        {{ __('admin.listing_queue.vehicle_data') }}
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 p-4 rounded-lg bg-bg">
                        <div>
                            <span class="text-xs text-muted block">{{ $selectedDetail->brand }} {{ $selectedDetail->model }}</span>
                            <span class="text-sm font-medium text-text">{{ $selectedDetail->year }} &bull; {{ $selectedDetail->odometerKm }} km</span>
                        </div>
                        <div>
                            <span class="text-xs text-muted block">{{ __('listing.fields.price') }}</span>
                            <span class="text-sm font-medium text-text"><x-ui.money :amount="$selectedDetail->price" /></span>
                        </div>
                        <div>
                            <span class="text-xs text-muted block">{{ __('listing.fields.color') }}</span>
                            <span class="text-sm font-medium text-text">{{ $selectedDetail->color }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-muted block">{{ __('listing.fields.area_name') }}</span>
                            <span class="text-sm font-medium text-text">{{ $selectedDetail->areaName ?? '-' }}</span>
                        </div>
                    </div>
                    @if ($selectedDetail->description)
                        <p class="text-sm text-text mt-3">{{ $selectedDetail->description }}</p>
                    @endif
                </div>

                {{-- Foto --}}
                <div>
                    <h3 class="text-base font-semibold text-text mb-3">
                        {{ __('admin.listing_queue.photos') }} ({{ count($selectedDetail->photos) }})
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach ($selectedDetail->photos as $photo)
                            <a href="{{ $photo['url'] }}" target="_blank" rel="noopener noreferrer" class="block rounded-lg overflow-hidden border border-muted/20">
                                <img
                                    src="{{ $photo['url'] }}"
                                    alt="{{ $selectedDetail->title }}"
                                    class="w-full aspect-16/9 object-cover"
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
                    <h3 class="text-base font-semibold text-text mb-3">
                        {{ __('admin.listing_queue.location') }}
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-lg bg-bg text-sm">
                        <div>
                            <span class="text-xs text-muted block">{{ __('listing.fields.address_full') }}</span>
                            <span class="font-medium text-text">{{ $selectedDetail->addressFull }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-muted block">{{ __('listing.fields.inspection_place') }}</span>
                            <span class="font-medium text-text">{{ $selectedDetail->inspectionPlace }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-muted block">{{ __('listing.fields.lat') }}, {{ __('listing.fields.lng') }}</span>
                            <span class="font-medium text-text">{{ $selectedDetail->lat }}, {{ $selectedDetail->lng }}</span>
                        </div>
                    </div>
                </div>

                {{-- Opsi Waktu Inspeksi --}}
                <div>
                    <h3 class="text-base font-semibold text-text mb-3">
                        {{ __('admin.listing_queue.inspection_slots') }} ({{ count($selectedDetail->slots) }})
                    </h3>
                    <ul class="divide-y divide-muted/15 border border-muted/20 rounded-lg overflow-hidden bg-surface">
                        @foreach ($selectedDetail->slots as $slot)
                            <li class="p-3 text-sm text-text flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3">
                                <x-ui.datetime :value="$slot['slot_start']" />
                                @if ($slot['note'])
                                    <span class="text-xs text-muted">{{ $slot['note'] }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Action Bar --}}
                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 pt-4 border-t border-muted/15">
                    <x-ui.button
                        type="button"
                        variant="secondary"
                        wire:click="closeDetail"
                    >
                        {{ __('admin.listing_queue.close_detail') }}
                    </x-ui.button>

                    <div class="flex items-center gap-3">
                        <x-ui.button
                            type="button"
                            variant="danger"
                            wire:click="confirmReject('{{ $selectedDetail->id }}')"
                        >
                            <x-ui.icon name="silang" class="w-4 h-4 mr-1.5" />
                            {{ __('admin.listing_queue.reject') }}
                        </x-ui.button>

                        <x-ui.button
                            type="button"
                            variant="primary"
                            wire:click="confirmApprove('{{ $selectedDetail->id }}')"
                        >
                            <x-ui.icon name="centang" class="w-4 h-4 mr-1.5" />
                            {{ __('admin.listing_queue.approve') }}
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </x-ui.card>
    @endif

    {{-- Validation Queue List --}}
    @if ($queue->isEmpty())
        <x-ui.card>
            <x-ui.empty-state
                icon="dokumen"
                :title="__('admin.listing_queue.empty_title')"
                :description="__('admin.listing_queue.empty_description')"
            />
        </x-ui.card>
    @else
        <div class="space-y-4">
            <h2 class="text-lg font-bold text-text">
                {{ __('admin.listing_queue.title') }} ({{ $queue->total() }})
            </h2>

            <div wire:loading class="text-sm text-muted">
                {{ __('admin.listing_queue.processing') }}
            </div>

            <div class="grid grid-cols-1 gap-4">
                @foreach ($queue as $item)
                    <x-ui.card class="transition-shadow hover:shadow-md">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="flex gap-4">
                                @if ($item->coverUrl)
                                    <img
                                        src="{{ $item->coverUrl }}"
                                        alt="{{ $item->title }}"
                                        class="w-24 h-18 object-cover rounded-lg shrink-0 aspect-[4/3]"
                                        width="96"
                                        height="72"
                                        loading="lazy"
                                    />
                                @endif
                                <div class="space-y-2">
                                    <div class="flex items-center gap-3">
                                        <h3 class="text-base font-bold text-text">{{ $item->title }}</h3>
                                        <x-ui.status-chip
                                            :label="__('listing.status.pending_review')"
                                            variant="warning"
                                            icon="jam"
                                        />
                                    </div>
                                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted">
                                        <span>{{ $item->brand }} {{ $item->model }} &bull; {{ $item->year }}</span>
                                        <span>&bull;</span>
                                        <span><x-ui.money :amount="$item->price" /></span>
                                        @if ($item->areaName)
                                            <span>&bull;</span>
                                            <span>{{ $item->areaName }}</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-muted">
                                        {{ $item->sellerName }} &bull;
                                        {{ __('admin.listing_queue.waiting_since') }}:
                                        <x-ui.datetime :value="$item->createdAt" />
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <x-ui.button
                                    type="button"
                                    variant="secondary"
                                    wire:click="openDetail('{{ $item->id }}')"
                                >
                                    <x-ui.icon name="mata" class="w-4 h-4 mr-1.5" />
                                    {{ __('admin.listing_queue.view_detail') }}
                                </x-ui.button>

                                <x-ui.button
                                    type="button"
                                    variant="danger"
                                    wire:click="confirmReject('{{ $item->id }}')"
                                >
                                    {{ __('admin.listing_queue.reject') }}
                                </x-ui.button>

                                <x-ui.button
                                    type="button"
                                    variant="primary"
                                    wire:click="confirmApprove('{{ $item->id }}')"
                                >
                                    {{ __('admin.listing_queue.approve') }}
                                </x-ui.button>
                            </div>
                        </div>
                    </x-ui.card>
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
                <x-ui.textarea
                    id="rejection_reason_input"
                    wire:model="rejectionReason"
                    :label="__('admin.listing_queue.reason_label')"
                    :placeholder="__('admin.listing_queue.reason_placeholder')"
                    :error="$errors->first('rejectionReason')"
                    rows="4"
                    required
                />

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-4 border-t border-muted/15">
                    <x-ui.button
                        type="button"
                        variant="secondary"
                        wire:click="cancelReject"
                    >
                        {{ __('admin.listing_queue.cancel') }}
                    </x-ui.button>
                    <x-ui.button
                        type="submit"
                        variant="danger"
                        wire:loading.attr="disabled"
                        wire:target="reject"
                    >
                        <span wire:loading.remove wire:target="reject">{{ __('admin.listing_queue.confirm_reject') }}</span>
                        <span wire:loading wire:target="reject">{{ __('admin.listing_queue.processing') }}</span>
                    </x-ui.button>
                </div>
            </form>
        </x-ui.modal>
    @endif
</div>
