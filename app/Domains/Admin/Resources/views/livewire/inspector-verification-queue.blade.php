<div class="ds-sans max-w-6xl mx-auto py-6 space-y-8 pb-12">
    {{-- Page Header --}}
    <div class="space-y-2">
        <span class="text-ds-blue text-xs font-extrabold uppercase tracking-wider">{{ __('admin.inspector_queue.title') }}</span>
        <h1 class="text-3xl font-extrabold text-ds-ink tracking-tight">
            {{ __('admin.inspector_queue.title') }}
        </h1>
        <p class="text-sm text-ds-muted">
            {{ __('admin.inspector_queue.subtitle') }}
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

    {{-- Detail Inspector View --}}
    @if ($selectedProfile)
        <div class="bg-white rounded-2xl border-2 border-ds-blue/30 shadow-xs p-6">
            <h2 class="text-lg font-extrabold text-ds-ink">{{ __('admin.inspector_queue.detail_title', ['name' => $selectedProfile->user->name]) }}</h2>
            <div class="space-y-6 mt-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 p-4 rounded-2xl bg-ds-canvas/60 border border-ds-line/60">
                    <div>
                        <span class="text-xs text-ds-muted block">{{ __('admin.inspector_queue.email') }}</span>
                        <span class="text-sm font-bold text-ds-ink">{{ $selectedProfile->user->email }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-ds-muted block">{{ __('admin.inspector_queue.phone') }}</span>
                        <span class="text-sm font-bold text-ds-ink">{{ $selectedProfile->user->phone ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-ds-muted block">{{ __('admin.inspector_queue.experience') }}</span>
                        <span class="text-sm font-bold text-ds-ink">
                            {{ __('admin.inspector_queue.experience_years', ['years' => $selectedProfile->experience_years]) }}
                        </span>
                    </div>
                    <div>
                        <span class="text-xs text-ds-muted block">{{ __('admin.inspector_queue.specialties') }}</span>
                        <div class="flex flex-wrap gap-1 mt-1">
                            @foreach ($selectedProfile->vehicle_types as $type)
                                <span class="text-[10px] px-2 py-0.5 rounded-md font-bold bg-ds-blue-soft text-ds-blue-ink">
                                    {{ __('inspector.vehicle_types.' . $type) }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Document List --}}
                <div>
                    <h3 class="text-base font-extrabold text-ds-ink mb-3">
                        {{ __('admin.inspector_queue.documents') }} ({{ $selectedProfile->documents->count() }})
                    </h3>
                    <div class="divide-y divide-ds-line/60 border border-ds-line/80 rounded-2xl overflow-hidden bg-white">
                        @forelse ($selectedProfile->documents as $doc)
                            <div class="p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <i aria-hidden="true" class="fa-solid fa-file-lines text-ds-blue"></i>
                                        <span class="font-bold text-sm text-ds-ink">{{ $doc->type->label() }}</span>
                                    </div>
                                    <p class="text-xs text-ds-muted">
                                        {{ __('admin.inspector_queue.uploaded_at') }}:
                                        {{ $doc->created_at?->translatedFormat('d M Y H:i') }}
                                    </p>
                                </div>
                                <div>
                                    @if ($doc->isUrl())
                                        <a
                                            href="{{ $doc->getSafeUrl() }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-ds-blue border border-ds-blue/20 hover:bg-ds-blue-soft/40 transition-colors"
                                        >
                                            <i aria-hidden="true" class="fa-solid fa-link text-xs"></i>
                                            {{ __('inspector.documents.open_url') }}
                                        </a>
                                    @elseif ($doc->isStoredFile())
                                        <a
                                            href="{{ $doc->getTemporarySignedUrl() }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-ds-blue border border-ds-blue/20 hover:bg-ds-blue-soft/40 transition-colors"
                                        >
                                            <i aria-hidden="true" class="fa-solid fa-eye text-xs"></i>
                                            {{ __('inspector.documents.view') }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="p-4 text-center text-sm text-ds-muted">
                                {{ __('inspector.documents.not_found') }}
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Action Bar --}}
                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 pt-4 border-t border-ds-line/60">
                    <button
                        type="button"
                        wire:click="closeDetail"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs"
                    >
                        {{ __('admin.inspector_queue.close_detail') }}
                    </button>

                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            wire:click="confirmReject('{{ $selectedProfile->id }}')"
                            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-ds-red-soft text-ds-danger hover:bg-ds-red-soft/30 font-bold text-xs rounded-xl transition-all shadow-xs"
                        >
                            <i aria-hidden="true" class="fa-solid fa-xmark text-xs mr-1.5"></i>
                            {{ __('admin.inspector_queue.reject') }}
                        </button>

                        <button
                            type="button"
                            wire:click="confirmApprove('{{ $selectedProfile->id }}')"
                            class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md"
                        >
                            <i aria-hidden="true" class="fa-solid fa-check text-xs mr-1.5"></i>
                            {{ __('admin.inspector_queue.approve') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Verification Queue List --}}
    @if ($profiles->isEmpty())
        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-8 text-center max-w-2xl mx-auto">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-ds-green-soft text-ds-green-ink flex items-center justify-center text-xl mb-4">
                <i aria-hidden="true" class="fa-solid fa-check"></i>
            </div>
            <h3 class="text-lg font-extrabold text-ds-ink">{{ __('admin.inspector_queue.empty_title') }}</h3>
            <p class="text-xs text-ds-muted mt-2">{{ __('admin.inspector_queue.empty_description') }}</p>
        </div>
    @else
        <div class="space-y-4">
            <h2 class="text-xl font-extrabold text-ds-ink">
                {{ __('admin.inspector_queue.title') }} ({{ $profiles->count() }})
            </h2>

            <div class="grid grid-cols-1 gap-4">
                @foreach ($profiles as $profile)
                    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-md transition-all p-4 sm:p-5">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="space-y-2">
                                <div class="flex items-center gap-3">
                                    <h3 class="text-base font-extrabold text-ds-ink">{{ $profile->user->name }}</h3>
                                    @php
                                        $vPill = match ($profile->verification_status->color()) {
                                            'success' => 'bg-ds-green-soft text-ds-green-ink',
                                            'warning' => 'bg-ds-amber-soft text-ds-amber-ink',
                                            'danger' => 'bg-ds-red-soft text-ds-red-ink',
                                            'primary' => 'bg-ds-blue-soft text-ds-blue-ink',
                                            default => 'bg-ds-soft text-ds-muted',
                                        };
                                    @endphp
                                    <span class="text-[10px] px-2 py-0.5 rounded-md font-bold {{ $vPill }}">
                                        {{ $profile->verification_status->label() }}
                                    </span>
                                </div>
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-ds-muted">
                                    <span>{{ $profile->user->email }}</span>
                                    <span>&bull;</span>
                                    <span>{{ $profile->user->phone ?? '-' }}</span>
                                    <span>&bull;</span>
                                    <span>{{ __('admin.inspector_queue.experience_years', ['years' => $profile->experience_years]) }}</span>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 pt-1">
                                    <span class="text-xs text-ds-muted">{{ __('admin.inspector_queue.specialties') }}:</span>
                                    @foreach ($profile->vehicle_types as $type)
                                        <span class="text-[10px] px-2 py-0.5 rounded-md font-bold bg-ds-blue-soft text-ds-blue-ink">
                                            {{ __('inspector.vehicle_types.' . $type) }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    wire:click="openDetail('{{ $profile->id }}')"
                                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2 min-h-[44px] bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs"
                                >
                                    <i aria-hidden="true" class="fa-solid fa-eye text-xs mr-1.5"></i>
                                    {{ __('admin.inspector_queue.view_detail') }}
                                </button>

                                <button
                                    type="button"
                                    wire:click="confirmReject('{{ $profile->id }}')"
                                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2 min-h-[44px] bg-white border border-ds-red-soft text-ds-danger hover:bg-ds-red-soft/30 font-bold text-xs rounded-xl transition-all shadow-xs"
                                >
                                    {{ __('admin.inspector_queue.reject') }}
                                </button>

                                <button
                                    type="button"
                                    wire:click="confirmApprove('{{ $profile->id }}')"
                                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2 min-h-[44px] bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-xs rounded-xl transition-all shadow-md"
                                >
                                    {{ __('admin.inspector_queue.approve') }}
                                </button>
                            </div>
                        </div>
                    </div>
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
                <div class="w-full">
                    <label for="rejection_reason_input" class="block text-xs font-bold text-ds-ink mb-1.5">
                        {{ __('admin.inspector_queue.reason_label') }}
                        <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                        <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                    </label>
                    <textarea id="rejection_reason_input" wire:model="rejectionReason" rows="4" required
                        placeholder="{{ __('admin.inspector_queue.reason_placeholder') }}"
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
                        {{ __('admin.inspector_queue.cancel') }}
                    </button>
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="reject"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-ds-red-soft text-ds-danger hover:bg-ds-red-soft/30 font-bold text-xs rounded-xl transition-all shadow-xs disabled:opacity-60"
                    >
                        <span wire:loading.remove wire:target="reject">{{ __('admin.inspector_queue.confirm_reject') }}</span>
                        <span wire:loading wire:target="reject">{{ __('admin.inspector_queue.processing') }}</span>
                    </button>
                </div>
            </form>
        </x-ui.modal>
    @endif
</div>
