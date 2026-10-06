<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <x-ui.button :href="route('jobs.inspector')" variant="ghost" icon="panah-kiri" class="mb-2 -ml-2">
                {{ __('inspection.detail.back_button') }}
            </x-ui.button>
            <h1 class="text-2xl font-bold text-text">{{ __('inspection.detail.title') }}</h1>
            <p class="text-sm text-muted mt-0.5">{{ __('inspection.detail.subtitle') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <x-ui.status-chip
                :label="$job->status->label()"
                :variant="$job->status->color()"
                :icon="$job->status->icon()"
            />
        </div>
    </div>

    @if (session('success'))
        <x-ui.alert variant="success" :message="session('success')" dismissible />
    @endif

    @if ($actionSuccess)
        <x-ui.alert variant="success" :message="$actionSuccess" dismissible />
    @endif

    @if ($actionError)
        <x-ui.alert variant="danger" :message="$actionError" dismissible />
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Kolom Kiri: Informasi Persis Lokasi & Kendaraan (2 Kolom) --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Lokasi Persis Inspeksi --}}
            <x-ui.card :title="__('inspection.detail.exact_location')">
                <div class="space-y-4 text-sm">
                    <div>
                        <span class="text-xs text-muted block">{{ __('inspection.detail.address_label') }}</span>
                        <p class="font-medium text-text mt-0.5" id="exact-address">
                            {{ $listing?->address_full ?? '-' }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-muted/15">
                        <div>
                            <span class="text-xs text-muted block">{{ __('inspection.detail.inspection_place_label') }}</span>
                            <p class="font-medium text-text mt-0.5" id="inspection-place">
                                {{ $listing?->inspection_place ?? '-' }}
                            </p>
                        </div>
                        <div>
                            <span class="text-xs text-muted block">{{ __('inspection.detail.coordinates_label') }}</span>
                            <p class="font-mono text-xs text-text mt-0.5" id="exact-coordinates">
                                {{ $listing?->lat }}, {{ $listing?->lng }}
                            </p>
                        </div>
                    </div>
                </div>
            </x-ui.card>

            {{-- Informasi Kendaraan --}}
            <x-ui.card :title="__('inspection.detail.vehicle_info')">
                <div class="space-y-4 text-sm">
                    <div class="flex items-baseline justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-text">
                                {{ $listing?->brand }} {{ $listing?->model }}
                            </h2>
                            <p class="text-sm text-muted">
                                {{ $listing?->year }} · {{ ucfirst((string) ($listing?->vehicle_type?->value ?? $listing?->vehicle_type)) }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-muted/15">
                        <div>
                            <span class="text-xs text-muted block">{{ __('inspection.detail.chassis_label') }}</span>
                            <p class="font-mono text-sm font-semibold text-text mt-0.5" id="chassis-number">
                                {{ $listing?->chassis_number ?? '-' }}
                            </p>
                        </div>
                        <div>
                            <span class="text-xs text-muted block">{{ __('inspection.open_jobs.filter_area') }}</span>
                            <p class="font-medium text-text mt-0.5">
                                {{ $listing?->area_name ?? '-' }}
                            </p>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>

        {{-- Kolom Kanan: Ringkasan Job & Aksi Jadwal/Inspeksi (1 Kolom) --}}
        <div class="space-y-6">
            {{-- Ringkasan Job --}}
            <x-ui.card :title="__('inspection.detail.job_info')">
                <div class="space-y-4 text-sm">
                    <div>
                        <span class="text-xs text-muted block">{{ __('inspection.detail.fee_label') }}</span>
                        <p class="text-2xl font-bold text-primary mt-1">
                            <x-ui.money :amount="$job->fee_amount" />
                        </p>
                    </div>

                    @if ($job->taken_at)
                        <div class="pt-3 border-t border-muted/15">
                            <span class="text-xs text-muted block">{{ __('inspection.detail.taken_at_label') }}</span>
                            <p class="text-xs font-medium text-text mt-0.5">
                                <x-ui.datetime :value="$job->taken_at" />
                            </p>
                        </div>
                    @endif

                    @if ($job->scheduled_at)
                        <div class="pt-3 border-t border-muted/15">
                            <span class="text-xs text-muted block">{{ __('inspection.detail.scheduled_at_label') }}</span>
                            <p class="text-xs font-medium text-text mt-0.5" id="scheduled-display">
                                {{ \App\Support\Clock::formatWib($job->scheduled_at) }}
                            </p>
                        </div>
                    @endif

                    @if ($job->started_at)
                        <div class="pt-3 border-t border-muted/15">
                            <span class="text-xs text-muted block">{{ __('inspection.detail.started_at_label') }}</span>
                            <p class="text-xs font-medium text-text mt-0.5">
                                {{ \App\Support\Clock::formatWib($job->started_at) }}
                            </p>
                        </div>
                    @endif
                </div>
            </x-ui.card>

            {{-- Form Jadwal & Aksi Inspeksi --}}
            @if ($job->status === \App\Domains\Inspection\Enums\JobStatus::Taken)
                <x-ui.card :title="__('inspection.detail.schedule_title')">
                    <p class="text-xs text-muted mb-4">{{ __('inspection.detail.schedule_desc') }}</p>

                    <form wire:submit="saveSchedule" class="space-y-4">
                        <div>
                            <x-ui.input
                                id="scheduled_date"
                                name="scheduled_date"
                                type="date"
                                :label="__('inspection.detail.scheduled_date_label')"
                                wire:model="scheduled_date"
                                :error="$errors->first('scheduled_date')"
                                required
                            />
                        </div>

                        <div>
                            <x-ui.input
                                id="scheduled_time"
                                name="scheduled_time"
                                type="time"
                                :label="__('inspection.detail.scheduled_time_label')"
                                wire:model="scheduled_time"
                                :error="$errors->first('scheduled_time')"
                                required
                            />
                        </div>

                        <div class="pt-2">
                            <x-ui.button
                                type="submit"
                                variant="primary"
                                wire:loading.attr="disabled"
                                class="w-full justify-center"
                            >
                                <span wire:loading.remove wire:target="saveSchedule">
                                    {{ __('inspection.detail.save_schedule_button') }}
                                </span>
                                <span wire:loading wire:target="saveSchedule">
                                    {{ __('inspection.detail.saving_schedule') }}
                                </span>
                            </x-ui.button>
                        </div>
                    </form>

                    <div class="pt-4 mt-4 border-t border-muted/15 space-y-3">
                        <x-ui.button
                            type="button"
                            variant="secondary"
                            disabled
                            class="w-full justify-center opacity-60 cursor-not-allowed"
                        >
                            {{ __('inspection.detail.start_button') }}
                        </x-ui.button>
                        <p class="text-xs text-muted text-center">{{ __('inspection.detail.start_hint') }}</p>

                        <x-ui.button
                            type="button"
                            variant="danger"
                            @click="$dispatch('open-modal', 'inspector-cancel-modal')"
                            class="w-full justify-center"
                        >
                            {{ __('inspection.detail.cancel_job_button') }}
                        </x-ui.button>
                    </div>
                </x-ui.card>
            @elseif ($job->status === \App\Domains\Inspection\Enums\JobStatus::Scheduled)
                <x-ui.card :title="__('inspection.detail.title')">
                    <div class="space-y-3">
                        <x-ui.button
                            type="button"
                            variant="primary"
                            wire:click="startInspection"
                            wire:loading.attr="disabled"
                            class="w-full justify-center"
                        >
                            <span wire:loading.remove wire:target="startInspection">
                                {{ __('inspection.detail.start_button') }}
                            </span>
                            <span wire:loading wire:target="startInspection">
                                {{ __('inspection.detail.starting') }}
                            </span>
                        </x-ui.button>

                        <x-ui.button
                            type="button"
                            variant="danger"
                            @click="$dispatch('open-modal', 'inspector-cancel-modal')"
                            class="w-full justify-center"
                        >
                            {{ __('inspection.detail.cancel_job_button') }}
                        </x-ui.button>
                    </div>
                </x-ui.card>
            @elseif ($job->status === \App\Domains\Inspection\Enums\JobStatus::InProgress && (string) auth()->id() === (string) $job->inspector_id)
                <x-ui.card :title="__('inspection.detail.fill_button')">
                    <div class="space-y-3">
                        <x-ui.button
                            :href="route('result.fill', ['job' => $job->id])"
                            variant="primary"
                            class="w-full justify-center"
                        >
                            {{ __('inspection.detail.fill_button') }}
                        </x-ui.button>
                        <p class="text-xs text-muted text-center">{{ __('inspection.detail.fill_hint') }}</p>
                    </div>
                </x-ui.card>
            @elseif (in_array($job->status, [\App\Domains\Inspection\Enums\JobStatus::ResultSubmitted, \App\Domains\Inspection\Enums\JobStatus::Completed], true) && \Illuminate\Support\Facades\Route::has('results.show'))
                <x-ui.card :title="__('inspection.detail.view_result_button')">
                    <x-ui.button
                        :href="route('results.show', ['listing' => $job->listing_id])"
                        variant="secondary"
                        class="w-full justify-center"
                    >
                        {{ __('inspection.detail.view_result_button') }}
                    </x-ui.button>
                </x-ui.card>
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
            <div>
                <x-ui.textarea
                    id="cancel_reason"
                    name="cancel_reason"
                    :label="__('inspection.detail.inspector_cancel_reason_label')"
                    :placeholder="__('inspection.detail.inspector_cancel_reason_placeholder')"
                    wire:model="cancel_reason"
                    :error="$errors->first('cancel_reason')"
                    rows="3"
                    required
                />
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
