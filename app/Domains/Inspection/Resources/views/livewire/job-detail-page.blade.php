<div class="ds-sans space-y-8 pb-12">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div class="space-y-2">
            <a href="{{ route('jobs.inspector') }}" class="min-h-[44px] inline-flex items-center gap-1 text-xs font-bold text-ds-blue hover:text-ds-blue-ink">
                <i aria-hidden="true" class="fa-solid fa-chevron-left"></i>
                {{ __('inspection.detail.back_button') }}
            </a>
            <h1 class="text-3xl font-extrabold text-ds-ink tracking-tight">{{ __('inspection.detail.title') }}</h1>
            <p class="text-sm text-ds-muted">{{ __('inspection.detail.subtitle') }}</p>
        </div>
        <div class="flex items-center gap-3">
            @php
                $statusPill = match ($job->status->color()) {
                    'success' => 'bg-ds-green-soft text-ds-green-ink',
                    'warning' => 'bg-ds-amber-soft text-ds-amber-ink',
                    'danger' => 'bg-ds-red-soft text-ds-red-ink',
                    'primary' => 'bg-ds-blue-soft text-ds-blue-ink',
                    default => 'bg-ds-soft text-ds-muted',
                };
            @endphp
            <span class="text-[10px] px-2.5 py-1 rounded-full font-bold uppercase tracking-wider {{ $statusPill }}">
                {{ $job->status->label() }}
            </span>
        </div>
    </div>

    @if (session('success'))
        <div role="status" class="p-4 rounded-2xl border border-ds-green-soft bg-ds-green-soft/40 text-xs font-semibold text-ds-ink">
            {{ session('success') }}
        </div>
    @endif

    @if ($actionSuccess)
        <div role="status" class="p-4 rounded-2xl border border-ds-green-soft bg-ds-green-soft/40 text-xs font-semibold text-ds-ink">
            {{ $actionSuccess }}
        </div>
    @endif

    @if ($actionError)
        <div role="alert" class="p-4 rounded-2xl border border-ds-red-soft bg-ds-red-soft/40 text-xs font-semibold text-ds-ink">
            {{ $actionError }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Kolom Kiri: Informasi Persis Lokasi & Kendaraan (2 Kolom) --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Lokasi Persis Inspeksi --}}
            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                <h2 class="text-sm font-extrabold text-ds-ink">{{ __('inspection.detail.exact_location') }}</h2>
                <div class="space-y-4 text-sm mt-4">
                    <div>
                        <span class="text-xs text-ds-muted block">{{ __('inspection.detail.address_label') }}</span>
                        <p class="font-bold text-ds-ink mt-0.5" id="exact-address">
                            {{ $listing?->address_full ?? '-' }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-ds-line/60">
                        <div>
                            <span class="text-xs text-ds-muted block">{{ __('inspection.detail.inspection_place_label') }}</span>
                            <p class="font-bold text-ds-ink mt-0.5" id="inspection-place">
                                {{ $listing?->inspection_place ?? '-' }}
                            </p>
                        </div>
                        <div>
                            <span class="text-xs text-ds-muted block">{{ __('inspection.detail.coordinates_label') }}</span>
                            <p class="font-mono text-xs text-ds-ink mt-0.5" id="exact-coordinates">
                                {{ $listing?->lat }}, {{ $listing?->lng }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Informasi Kendaraan --}}
            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                <h2 class="text-sm font-extrabold text-ds-ink">{{ __('inspection.detail.vehicle_info') }}</h2>
                <div class="space-y-4 text-sm mt-4">
                    <div class="flex items-baseline justify-between">
                        <div>
                            <h3 class="text-xl font-extrabold text-ds-ink">
                                {{ $listing?->brand }} {{ $listing?->model }}
                            </h3>
                            <p class="text-sm text-ds-muted">
                                {{ $listing?->year }} · {{ ucfirst((string) ($listing?->vehicle_type?->value ?? $listing?->vehicle_type)) }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-ds-line/60">
                        <div>
                            <span class="text-xs text-ds-muted block">{{ __('inspection.detail.chassis_label') }}</span>
                            <p class="font-mono text-sm font-bold text-ds-ink mt-0.5" id="chassis-number">
                                {{ $listing?->chassis_number ?? '-' }}
                            </p>
                        </div>
                        <div>
                            <span class="text-xs text-ds-muted block">{{ __('inspection.open_jobs.filter_area') }}</span>
                            <p class="font-bold text-ds-ink mt-0.5">
                                {{ $listing?->area_name ?? '-' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kolom Kanan: Ringkasan Job & Aksi Jadwal/Inspeksi (1 Kolom) --}}
        <div class="space-y-6">
            {{-- Ringkasan Job --}}
            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                <h2 class="text-sm font-extrabold text-ds-ink">{{ __('inspection.detail.job_info') }}</h2>
                <div class="space-y-4 text-sm mt-4">
                    <div>
                        <span class="text-xs text-ds-muted block">{{ __('inspection.detail.fee_label') }}</span>
                        <p class="text-2xl font-extrabold text-ds-ink mt-1">
                            {{ \App\Support\Money::format($job->fee_amount) }}
                        </p>
                    </div>

                    @if ($job->taken_at)
                        <div class="pt-3 border-t border-ds-line/60">
                            <span class="text-xs text-ds-muted block">{{ __('inspection.detail.taken_at_label') }}</span>
                            <p class="text-xs font-bold text-ds-ink mt-0.5">
                                {{ $job->taken_at?->translatedFormat('d M Y H:i') }}
                            </p>
                        </div>
                    @endif

                    @if ($job->scheduled_at)
                        <div class="pt-3 border-t border-ds-line/60">
                            <span class="text-xs text-ds-muted block">{{ __('inspection.detail.scheduled_at_label') }}</span>
                            <p class="text-xs font-bold text-ds-ink mt-0.5" id="scheduled-display">
                                {{ \App\Support\Clock::formatWib($job->scheduled_at) }}
                            </p>
                        </div>
                    @endif

                    @if ($job->started_at)
                        <div class="pt-3 border-t border-ds-line/60">
                            <span class="text-xs text-ds-muted block">{{ __('inspection.detail.started_at_label') }}</span>
                            <p class="text-xs font-bold text-ds-ink mt-0.5">
                                {{ \App\Support\Clock::formatWib($job->started_at) }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Form Jadwal & Aksi Inspeksi --}}
            @if ($job->status === \App\Domains\Inspection\Enums\JobStatus::Taken)
                <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                    <h2 class="text-sm font-extrabold text-ds-ink">{{ __('inspection.detail.schedule_title') }}</h2>
                    <p class="text-xs text-ds-muted mt-1 mb-4">{{ __('inspection.detail.schedule_desc') }}</p>

                    <form wire:submit="saveSchedule" class="space-y-4">
                        <div class="w-full">
                            <label for="scheduled_date" class="block text-xs font-bold text-ds-ink mb-1.5">
                                {{ __('inspection.detail.scheduled_date_label') }}
                                <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                                <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                            </label>
                            <input type="date" id="scheduled_date" name="scheduled_date" wire:model="scheduled_date" required
                                class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                            @error('scheduled_date')
                                <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="w-full">
                            <label for="scheduled_time" class="block text-xs font-bold text-ds-ink mb-1.5">
                                {{ __('inspection.detail.scheduled_time_label') }}
                                <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                                <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                            </label>
                            <input type="time" id="scheduled_time" name="scheduled_time" wire:model="scheduled_time" required
                                class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                            @error('scheduled_time')
                                <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="pt-2">
                            <button
                                type="submit"
                                wire:loading.attr="disabled"
                                class="w-full inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md disabled:opacity-60"
                            >
                                <span wire:loading.remove wire:target="saveSchedule">
                                    {{ __('inspection.detail.save_schedule_button') }}
                                </span>
                                <span wire:loading wire:target="saveSchedule">
                                    {{ __('inspection.detail.saving_schedule') }}
                                </span>
                            </button>
                        </div>
                    </form>

                    <div class="pt-4 mt-4 border-t border-ds-line/60 space-y-3">
                        <button
                            type="button"
                            disabled
                            class="w-full inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-soft text-ds-muted font-bold text-sm rounded-xl opacity-60 cursor-not-allowed"
                        >
                            {{ __('inspection.detail.start_button') }}
                        </button>
                        <p class="text-xs text-ds-muted text-center">{{ __('inspection.detail.start_hint') }}</p>

                        <button
                            type="button"
                            @click="$dispatch('open-modal', 'inspector-cancel-modal')"
                            class="w-full inline-flex items-center justify-center gap-2 px-7 py-3 bg-white border border-ds-red-soft text-ds-danger hover:bg-ds-red-soft/30 font-bold text-sm rounded-xl transition-all shadow-xs"
                        >
                            {{ __('inspection.detail.cancel_job_button') }}
                        </button>
                    </div>
                </div>
            @elseif ($job->status === \App\Domains\Inspection\Enums\JobStatus::Scheduled)
                <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                    <h2 class="text-sm font-extrabold text-ds-ink">{{ __('inspection.detail.title') }}</h2>
                    <div class="space-y-3 mt-4">
                        <button
                            type="button"
                            wire:click="startInspection"
                            wire:loading.attr="disabled"
                            class="w-full inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="startInspection">
                                {{ __('inspection.detail.start_button') }}
                            </span>
                            <span wire:loading wire:target="startInspection">
                                {{ __('inspection.detail.starting') }}
                            </span>
                        </button>

                        <button
                            type="button"
                            @click="$dispatch('open-modal', 'inspector-cancel-modal')"
                            class="w-full inline-flex items-center justify-center gap-2 px-7 py-3 bg-white border border-ds-red-soft text-ds-danger hover:bg-ds-red-soft/30 font-bold text-sm rounded-xl transition-all shadow-xs"
                        >
                            {{ __('inspection.detail.cancel_job_button') }}
                        </button>
                    </div>
                </div>
            @elseif ($job->status === \App\Domains\Inspection\Enums\JobStatus::InProgress && (string) auth()->id() === (string) $job->inspector_id)
                <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                    <h2 class="text-sm font-extrabold text-ds-ink">{{ __('inspection.detail.fill_button') }}</h2>
                    <div class="space-y-3 mt-4">
                        <a href="{{ route('result.fill', ['job' => $job->id]) }}"
                            class="w-full inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md">
                            {{ __('inspection.detail.fill_button') }}
                        </a>
                        <p class="text-xs text-ds-muted text-center">{{ __('inspection.detail.fill_hint') }}</p>
                    </div>
                </div>
            @elseif (in_array($job->status, [\App\Domains\Inspection\Enums\JobStatus::ResultSubmitted, \App\Domains\Inspection\Enums\JobStatus::Completed], true) && \Illuminate\Support\Facades\Route::has('results.show'))
                <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                    <h2 class="text-sm font-extrabold text-ds-ink">{{ __('inspection.detail.view_result_button') }}</h2>
                    <a href="{{ route('results.show', ['listing' => $job->listing_id]) }}"
                        class="mt-4 w-full inline-flex items-center justify-center gap-2 px-7 py-3 bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-sm rounded-xl transition-all shadow-xs">
                        {{ __('inspection.detail.view_result_button') }}
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- Modal Pembatalan oleh Inspektor --}}
    <x-ui.modal
        name="inspector-cancel-modal"
        :title="__('inspection.detail.inspector_cancel_modal_title')"
        :description="__('inspection.detail.inspector_cancel_modal_desc')"
        maxWidth="max-w-lg"
    >
        <div class="space-y-4 py-2">
            <div class="w-full">
                <label for="cancel_reason" class="block text-xs font-bold text-ds-ink mb-1.5">
                    {{ __('inspection.detail.inspector_cancel_reason_label') }}
                </label>
                <textarea id="cancel_reason" name="cancel_reason" wire:model="cancel_reason" rows="3" required
                    class="w-full px-4 py-3 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors"></textarea>
                @error('cancel_reason')
                    <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <x-slot:footer>
            <x-ui.button
                type="button"
                variant="secondary"
                @click="open = false"
            >
                {{ __('ui.actions.cancel') }}
            </x-ui.button>

            <x-ui.button
                type="button"
                variant="danger"
                wire:click="cancelJob"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="cancelJob">
                    {{ __('inspection.detail.inspector_cancel_confirm') }}
                </span>
                <span wire:loading wire:target="cancelJob">
                    {{ __('inspection.detail.cancelling') }}
                </span>
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
