<div class="max-w-6xl mx-auto py-6 space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-text">
                {{ __('admin.inspector_queue.title') }}
            </h1>
            <p class="text-sm text-muted mt-1">
                {{ __('admin.inspector_queue.subtitle') }}
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

    {{-- Detail Inspector View --}}
    @if ($selectedProfile)
        <x-ui.card
            :title="__('admin.inspector_queue.detail_title', ['name' => $selectedProfile->user->name])"
            class="border-2 border-primary/30"
        >
            <div class="space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 p-4 rounded-lg bg-bg">
                    <div>
                        <span class="text-xs text-muted block">{{ __('admin.inspector_queue.email') }}</span>
                        <span class="text-sm font-medium text-text">{{ $selectedProfile->user->email }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-muted block">{{ __('admin.inspector_queue.phone') }}</span>
                        <span class="text-sm font-medium text-text">{{ $selectedProfile->user->phone ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-muted block">{{ __('admin.inspector_queue.experience') }}</span>
                        <span class="text-sm font-medium text-text">
                            {{ __('admin.inspector_queue.experience_years', ['years' => $selectedProfile->experience_years]) }}
                        </span>
                    </div>
                    <div>
                        <span class="text-xs text-muted block">{{ __('admin.inspector_queue.specialties') }}</span>
                        <div class="flex flex-wrap gap-1 mt-1">
                            @foreach ($selectedProfile->vehicle_types as $type)
                                <x-ui.status-chip
                                    :label="__('inspector.vehicle_types.' . $type)"
                                    variant="primary"
                                />
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Document List --}}
                <div>
                    <h3 class="text-base font-semibold text-text mb-3">
                        {{ __('admin.inspector_queue.documents') }} ({{ $selectedProfile->documents->count() }})
                    </h3>
                    <div class="divide-y divide-muted/15 border border-muted/20 rounded-lg overflow-hidden bg-surface">
                        @forelse ($selectedProfile->documents as $doc)
                            <div class="p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <x-ui.icon name="dokumen" class="w-5 h-5 text-primary" />
                                        <span class="font-medium text-sm text-text">{{ $doc->type->label() }}</span>
                                    </div>
                                    <p class="text-xs text-muted">
                                        {{ __('admin.inspector_queue.uploaded_at') }}:
                                        <x-ui.datetime :value="$doc->created_at" />
                                    </p>
                                </div>
                                <div>
                                    @if ($doc->isUrl())
                                        <a
                                            href="{{ $doc->getSafeUrl() }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-primary hover:bg-muted/10 transition-colors border border-primary/20"
                                        >
                                            <x-ui.icon name="tautan" class="w-4 h-4" />
                                            {{ __('inspector.documents.open_url') }}
                                        </a>
                                    @elseif ($doc->isStoredFile())
                                        <a
                                            href="{{ $doc->getTemporarySignedUrl() }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-primary hover:bg-muted/10 transition-colors border border-primary/20"
                                        >
                                            <x-ui.icon name="mata" class="w-4 h-4" />
                                            {{ __('inspector.documents.view') }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="p-4 text-center text-sm text-muted">
                                {{ __('inspector.documents.not_found') }}
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Action Bar --}}
                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 pt-4 border-t border-muted/15">
                    <x-ui.button
                        type="button"
                        variant="secondary"
                        wire:click="closeDetail"
                    >
                        {{ __('admin.inspector_queue.close_detail') }}
                    </x-ui.button>

                    <div class="flex items-center gap-3">
                        <x-ui.button
                            type="button"
                            variant="danger"
                            wire:click="confirmReject('{{ $selectedProfile->id }}')"
                        >
                            <x-ui.icon name="silang" class="w-4 h-4 mr-1.5" />
                            {{ __('admin.inspector_queue.reject') }}
                        </x-ui.button>

                        <x-ui.button
                            type="button"
                            variant="primary"
                            wire:click="confirmApprove('{{ $selectedProfile->id }}')"
                        >
                            <x-ui.icon name="centang" class="w-4 h-4 mr-1.5" />
                            {{ __('admin.inspector_queue.approve') }}
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </x-ui.card>
    @endif

    {{-- Verification Queue List --}}
    @if ($profiles->isEmpty())
        <x-ui.card>
            <x-ui.empty-state
                icon="dokumen"
                :title="__('admin.inspector_queue.empty_title')"
                :description="__('admin.inspector_queue.empty_description')"
            />
        </x-ui.card>
    @else
        <div class="space-y-4">
            <h2 class="text-lg font-bold text-text">
                {{ __('admin.inspector_queue.title') }} ({{ $profiles->count() }})
            </h2>

            <div class="grid grid-cols-1 gap-4">
                @foreach ($profiles as $profile)
                    <x-ui.card class="transition-shadow hover:shadow-md">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="space-y-2">
                                <div class="flex items-center gap-3">
                                    <h3 class="text-base font-bold text-text">{{ $profile->user->name }}</h3>
                                    <x-ui.status-chip
                                        :label="$profile->verification_status->label()"
                                        :variant="$profile->verification_status->color()"
                                        :icon="$profile->verification_status->icon()"
                                    />
                                </div>
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted">
                                    <span>{{ $profile->user->email }}</span>
                                    <span>&bull;</span>
                                    <span>{{ $profile->user->phone ?? '-' }}</span>
                                    <span>&bull;</span>
                                    <span>{{ __('admin.inspector_queue.experience_years', ['years' => $profile->experience_years]) }}</span>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 pt-1">
                                    <span class="text-xs text-muted">{{ __('admin.inspector_queue.specialties') }}:</span>
                                    @foreach ($profile->vehicle_types as $type)
                                        <x-ui.status-chip
                                            :label="__('inspector.vehicle_types.' . $type)"
                                            variant="primary"
                                        />
                                    @endforeach
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <x-ui.button
                                    type="button"
                                    variant="secondary"
                                    wire:click="openDetail('{{ $profile->id }}')"
                                >
                                    <x-ui.icon name="mata" class="w-4 h-4 mr-1.5" />
                                    {{ __('admin.inspector_queue.view_detail') }}
                                </x-ui.button>

                                <x-ui.button
                                    type="button"
                                    variant="danger"
                                    wire:click="confirmReject('{{ $profile->id }}')"
                                >
                                    {{ __('admin.inspector_queue.reject') }}
                                </x-ui.button>

                                <x-ui.button
                                    type="button"
                                    variant="primary"
                                    wire:click="confirmApprove('{{ $profile->id }}')"
                                >
                                    {{ __('admin.inspector_queue.approve') }}
                                </x-ui.button>
                            </div>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Modal Konfirmasi Setujui --}}
    @if ($confirmingApproveId)
        <x-ui.modal
            name="modal-approve-inspector"
            :show="true"
            :title="__('admin.inspector_queue.approve_modal_title')"
            :description="__('admin.inspector_queue.approve_modal_description')"
        >
            <div class="p-2 text-sm text-text">
                <p>{{ __('admin.inspector_queue.approve_modal_description') }}</p>
            </div>

            <x-slot:footer>
                <x-ui.button
                    type="button"
                    variant="secondary"
                    wire:click="cancelApprove"
                >
                    {{ __('admin.inspector_queue.cancel') }}
                </x-ui.button>
                <x-ui.button
                    type="button"
                    variant="primary"
                    wire:click="approve"
                    wire:loading.attr="disabled"
                    wire:target="approve"
                >
                    <span wire:loading.remove wire:target="approve">{{ __('admin.inspector_queue.confirm_approve') }}</span>
                    <span wire:loading wire:target="approve">{{ __('admin.inspector_queue.processing') }}</span>
                </x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif

    {{-- Modal Tolak dengan Alasan Wajib --}}
    @if ($confirmingRejectId)
        <x-ui.modal
            name="modal-reject-inspector"
            :show="true"
            :title="__('admin.inspector_queue.reject_modal_title')"
            :description="__('admin.inspector_queue.reject_modal_description')"
        >
            <form wire:submit="reject" class="space-y-4">
                <x-ui.textarea
                    id="rejection_reason_input"
                    wire:model="rejectionReason"
                    :label="__('admin.inspector_queue.reason_label')"
                    :placeholder="__('admin.inspector_queue.reason_placeholder')"
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
                        {{ __('admin.inspector_queue.cancel') }}
                    </x-ui.button>
                    <x-ui.button
                        type="submit"
                        variant="danger"
                        wire:loading.attr="disabled"
                        wire:target="reject"
                    >
                        <span wire:loading.remove wire:target="reject">{{ __('admin.inspector_queue.confirm_reject') }}</span>
                        <span wire:loading wire:target="reject">{{ __('admin.inspector_queue.processing') }}</span>
                    </x-ui.button>
                </div>
            </form>
        </x-ui.modal>
    @endif
</div>
