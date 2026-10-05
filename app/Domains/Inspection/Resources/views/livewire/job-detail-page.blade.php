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

        {{-- Kolom Kanan: Ringkasan Job (1 Kolom) --}}
        <div class="space-y-6">
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
                </div>
            </x-ui.card>
        </div>
    </div>
</div>
