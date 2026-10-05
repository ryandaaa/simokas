<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-text">{{ __('inspection.open_jobs.title') }}</h1>
            <p class="text-sm text-muted mt-1">{{ __('inspection.open_jobs.subtitle') }}</p>
        </div>
        <x-ui.button :href="route('jobs.inspector')" variant="secondary" icon="dokumen">
            {{ __('inspection.inspector_jobs.title') }}
        </x-ui.button>
    </div>

    @if ($errorMessage)
        <x-ui.alert variant="danger" :message="$errorMessage" dismissible />
    @endif

    @if (session('success'))
        <x-ui.alert variant="success" :message="session('success')" dismissible />
    @endif

    {{-- Filter & Urutan --}}
    <x-ui.card>
        <form wire:submit.prevent="applyFilter" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <x-ui.select
                id="vehicle-type"
                name="vehicleType"
                wire:model.defer="vehicleType"
                :label="__('inspection.open_jobs.filter_type')"
                :options="[
                    '' => __('inspection.open_jobs.all_types'),
                    'car' => __('listing.vehicle_types.car'),
                    'motorcycle' => __('listing.vehicle_types.motorcycle'),
                ]"
            />

            <x-ui.input
                id="filter-area"
                name="area"
                wire:model.defer="area"
                :label="__('inspection.open_jobs.filter_area')"
                :placeholder="__('inspection.open_jobs.area_placeholder')"
            />

            <x-ui.input
                id="filter-fee-min"
                name="feeMin"
                type="number"
                wire:model.defer="feeMin"
                :label="__('inspection.open_jobs.filter_fee_min')"
                placeholder="50000"
            />

            <x-ui.select
                id="filter-sort"
                name="sort"
                wire:model.defer="sort"
                :label="__('inspection.open_jobs.sort_label')"
                :options="[
                    'latest' => __('inspection.open_jobs.sort_latest'),
                    'highest_fee' => __('inspection.open_jobs.sort_highest_fee'),
                ]"
            />

            <div class="flex items-end gap-2">
                <x-ui.button type="submit" variant="primary" class="w-full">
                    {{ __('inspection.open_jobs.filter_button') }}
                </x-ui.button>
                <x-ui.button type="button" variant="ghost" wire:click="resetFilter" title="{{ __('inspection.open_jobs.reset_filter') }}">
                    <x-ui.icon name="silang" class="w-4 h-4" />
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>

    {{-- Loading state --}}
    <div wire:loading.delay wire:target="applyFilter, resetFilter, take" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @for ($i = 0; $i < 6; $i++)
            <x-ui.skeleton variant="card" />
        @endfor
    </div>

    {{-- Daftar Job Terbuka --}}
    <div wire:loading.remove wire:target="applyFilter, resetFilter, take">
        @if ($jobs === null || $jobs->isEmpty())
            <x-ui.empty-state
                icon="dokumen"
                :title="__('inspection.open_jobs.empty_title')"
                :description="__('inspection.open_jobs.empty_desc')"
            />
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($jobs as $job)
                    <x-ui.card class="flex flex-col justify-between">
                        <div class="space-y-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h3 class="font-bold text-text text-lg">
                                        {{ $job->brand }} {{ $job->model }}
                                    </h3>
                                    <p class="text-xs text-muted mt-0.5">
                                        {{ $job->year }} · {{ ucfirst($job->vehicleType) }}
                                        @if ($job->areaName)
                                            · {{ $job->areaName }}
                                        @endif
                                    </p>
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-accent/15 text-accent">
                                    {{ ucfirst($job->vehicleType) }}
                                </span>
                            </div>

                            <div class="pt-3 border-t border-muted/15 flex items-baseline justify-between">
                                <span class="text-xs text-muted">{{ __('inspection.open_jobs.fee_label') }}</span>
                                <span class="text-lg font-bold text-primary">
                                    <x-ui.money :amount="$job->feeAmount" />
                                </span>
                            </div>
                        </div>

                        <div class="mt-5 pt-3 border-t border-muted/15">
                            <x-ui.button
                                type="button"
                                variant="primary"
                                class="w-full"
                                wire:click="confirmTake('{{ $job->id }}')"
                                wire:loading.attr="disabled"
                            >
                                {{ __('inspection.open_jobs.take_button') }}
                            </x-ui.button>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>

            <div class="mt-6">
                <x-ui.pagination :paginator="$jobs" />
            </div>
        @endif
    </div>

    {{-- Modal Konfirmasi Ambil Job --}}
    @if ($confirmingJobId !== null)
        <x-ui.modal
            name="confirm-take-modal"
            :show="true"
            :title="__('inspection.take.modal_title')"
            :description="__('inspection.take.modal_description')"
        >
            <x-slot:footer>
                <x-ui.button
                    type="button"
                    variant="secondary"
                    wire:click="cancelTake"
                >
                    {{ __('inspection.take.cancel_button') }}
                </x-ui.button>
                <x-ui.button
                    type="button"
                    variant="primary"
                    wire:click="take"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="take">{{ __('inspection.take.confirm_button') }}</span>
                    <span wire:loading wire:target="take">{{ __('inspection.take.taking') }}</span>
                </x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>
