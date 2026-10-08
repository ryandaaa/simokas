<div class="ds-sans space-y-8 pb-12">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div class="space-y-2">
            <h1 class="text-3xl font-extrabold text-ds-ink tracking-tight">{{ __('inspection.open_jobs.title') }}</h1>
            <p class="text-sm text-ds-muted">{{ __('inspection.open_jobs.subtitle') }}</p>
        </div>
        <a href="{{ route('jobs.inspector') }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 min-h-[44px] bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs">
            <i aria-hidden="true" class="fa-solid fa-briefcase text-xs"></i>
            {{ __('inspection.inspector_jobs.title') }}
        </a>
    </div>

    @if ($errorMessage)
        <div role="alert" class="p-4 rounded-2xl border border-ds-red-soft bg-ds-red-soft/40 text-xs font-semibold text-ds-ink">
            {{ $errorMessage }}
        </div>
    @endif

    @if (session('success'))
        <div role="status" class="p-4 rounded-2xl border border-ds-green-soft bg-ds-green-soft/40 text-xs font-semibold text-ds-ink">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filter & Urutan --}}
    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-5 sm:p-6">
        <form wire:submit.prevent="applyFilter" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="w-full">
                <label for="vehicle-type" class="block text-xs font-bold text-ds-ink mb-1.5">{{ __('inspection.open_jobs.filter_type') }}</label>
                <select id="vehicle-type" name="vehicleType" wire:model.defer="vehicleType"
                    class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                    <option value="">{{ __('inspection.open_jobs.all_types') }}</option>
                    <option value="car">{{ __('listing.vehicle_types.car') }}</option>
                    <option value="motorcycle">{{ __('listing.vehicle_types.motorcycle') }}</option>
                </select>
            </div>

            <div class="w-full">
                <label for="filter-area" class="block text-xs font-bold text-ds-ink mb-1.5">{{ __('inspection.open_jobs.filter_area') }}</label>
                <input type="text" id="filter-area" name="area" wire:model.defer="area" placeholder="{{ __('inspection.open_jobs.area_placeholder') }}"
                    class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
            </div>

            <div class="w-full">
                <label for="filter-fee-min" class="block text-xs font-bold text-ds-ink mb-1.5">{{ __('inspection.open_jobs.filter_fee_min') }}</label>
                <input type="number" id="filter-fee-min" name="feeMin" wire:model.defer="feeMin" placeholder="50000"
                    class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
            </div>

            <div class="w-full">
                <label for="filter-sort" class="block text-xs font-bold text-ds-ink mb-1.5">{{ __('inspection.open_jobs.sort_label') }}</label>
                <select id="filter-sort" name="sort" wire:model.defer="sort"
                    class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                    <option value="latest">{{ __('inspection.open_jobs.sort_latest') }}</option>
                    <option value="highest_fee">{{ __('inspection.open_jobs.sort_highest_fee') }}</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit"
                    class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-2.5 min-h-[44px] bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-xs rounded-xl transition-all shadow-md">
                    {{ __('inspection.open_jobs.filter_button') }}
                </button>
                <button type="button" wire:click="resetFilter" title="{{ __('inspection.open_jobs.reset_filter') }}"
                    class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] rounded-xl bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink transition-all shadow-xs">
                    <i aria-hidden="true" class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </form>
    </div>

    {{-- Loading state --}}
    <div wire:loading.delay wire:target="applyFilter, resetFilter, take" role="status" aria-label="Memuat" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @for ($i = 0; $i < 6; $i++)
            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-5 animate-pulse">
                <div class="h-5 w-2/3 rounded bg-ds-soft"></div>
                <div class="h-4 w-1/3 rounded bg-ds-soft mt-2"></div>
            </div>
        @endfor
    </div>

    {{-- Daftar Job Terbuka --}}
    <div wire:loading.remove wire:target="applyFilter, resetFilter, take">
        @if ($jobs === null || $jobs->isEmpty())
            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-8 text-center max-w-2xl mx-auto">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-ds-soft text-ds-muted flex items-center justify-center text-xl mb-4">
                    <i aria-hidden="true" class="fa-solid fa-file-lines"></i>
                </div>
                <h3 class="text-lg font-extrabold text-ds-ink">{{ __('inspection.open_jobs.empty_title') }}</h3>
                <p class="text-xs text-ds-muted mt-2">{{ __('inspection.open_jobs.empty_desc') }}</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($jobs as $job)
                    <div class="group bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col justify-between">
                        <div class="p-5 space-y-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h3 class="font-extrabold text-ds-ink text-lg group-hover:text-ds-blue transition-colors">
                                        {{ $job->brand }} {{ $job->model }}
                                    </h3>
                                    <p class="text-xs text-ds-muted mt-0.5">
                                        {{ $job->year }} · {{ ucfirst($job->vehicleType) }}
                                        @if ($job->areaName)
                                            · {{ $job->areaName }}
                                        @endif
                                    </p>
                                </div>
                                <span class="text-[10px] px-2 py-0.5 rounded-md font-bold bg-ds-blue-soft text-ds-blue-ink shrink-0">
                                    {{ ucfirst($job->vehicleType) }}
                                </span>
                            </div>

                            <div class="pt-3 border-t border-ds-line/60 flex items-baseline justify-between">
                                <span class="text-xs text-ds-muted">{{ __('inspection.open_jobs.fee_label') }}</span>
                                <span class="text-lg font-extrabold text-ds-ink">
                                    {{ \App\Support\Money::format($job->feeAmount) }}
                                </span>
                            </div>
                        </div>

                        <div class="px-5 py-3 bg-ds-canvas/80 border-t border-ds-line/60">
                            <button
                                type="button"
                                wire:click="confirmTake('{{ $job->id }}')"
                                wire:loading.attr="disabled"
                                class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 min-h-[44px] bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-xs rounded-xl transition-all shadow-md disabled:opacity-60"
                            >
                                {{ __('inspection.open_jobs.take_button') }}
                            </button>
                        </div>
                    </div>
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
