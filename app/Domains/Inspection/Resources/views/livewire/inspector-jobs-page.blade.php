<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-text">{{ __('inspection.inspector_jobs.title') }}</h1>
            <p class="text-sm text-muted mt-1">{{ __('inspection.inspector_jobs.subtitle') }}</p>
        </div>
        <x-ui.button :href="route('jobs.index')" variant="primary" icon="kaca-pembesar">
            {{ __('inspection.inspector_jobs.open_jobs_button') }}
        </x-ui.button>
    </div>

    @if ($errorMessage)
        <x-ui.alert variant="danger" :message="$errorMessage" :retry-action="'retry'" />
    @endif

    <div wire:loading class="w-full text-center py-4 text-sm text-slate">
        <x-ui.icon name="jam" class="w-5 h-5 animate-spin inline-block mr-2" />
        {{ __('ui.actions.loading') }}
    </div>

    @if ($jobs === null || $jobs->isEmpty())
        <x-ui.empty-state
            icon="dokumen"
            :title="__('inspection.inspector_jobs.empty_title')"
            :description="__('inspection.inspector_jobs.empty_desc')"
            :action-label="__('inspection.inspector_jobs.open_jobs_button')"
            :action-url="route('jobs.index')"
        />
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($jobs as $job)
                <x-ui.card class="flex flex-col justify-between">
                    <div class="space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h3 class="font-bold text-text text-lg">
                                    {{ $job->listing?->brand }} {{ $job->listing?->model }}
                                </h3>
                                <p class="text-xs text-muted mt-0.5">
                                    {{ $job->listing?->year }} · {{ ucfirst((string) ($job->listing?->vehicle_type?->value ?? $job->listing?->vehicle_type)) }}
                                    @if ($job->listing?->area_name)
                                        · {{ $job->listing->area_name }}
                                    @endif
                                </p>
                            </div>
                            <x-ui.status-chip
                                :label="$job->status->label()"
                                :variant="$job->status->color()"
                                :icon="$job->status->icon()"
                            />
                        </div>

                        <div class="pt-3 border-t border-muted/15 flex items-baseline justify-between">
                            <span class="text-xs text-muted">{{ __('inspection.open_jobs.fee_label') }}</span>
                            <span class="text-lg font-bold text-primary">
                                <x-ui.money :amount="$job->fee_amount" />
                            </span>
                        </div>
                    </div>

                    <div class="mt-5 pt-3 border-t border-muted/15">
                        <x-ui.button
                            :href="route('jobs.show', ['job' => $job->id])"
                            variant="secondary"
                            class="w-full"
                        >
                            {{ __('inspection.inspector_jobs.view_detail') }}
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
