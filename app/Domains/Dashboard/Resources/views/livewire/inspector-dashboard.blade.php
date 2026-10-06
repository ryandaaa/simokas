<div class="space-y-6">
    <div>
        <h2 class="text-lg font-bold text-text mb-4">{{ __('dashboard.inspector.title') }}</h2>
    </div>

    {{-- Key Metrics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        {{-- Available Jobs Card --}}
        <x-ui.card class="p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-muted">{{ __('dashboard.inspector.available_jobs') }}</p>
                    <p class="text-3xl font-bold text-text mt-2">{{ $availableJobs }}</p>
                </div>
                <div class="p-3 bg-primary/10 rounded-lg">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
            </div>
        </x-ui.card>

        {{-- Active Jobs Card --}}
        <x-ui.card class="p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-muted">{{ __('dashboard.inspector.active_jobs') }}</p>
                    <p class="text-3xl font-bold text-text mt-2">{{ $activeJobs }}</p>
                </div>
                <div class="p-3 bg-warning/10 rounded-lg">
                    <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
            </div>
        </x-ui.card>

        {{-- Net Earnings Card --}}
        <x-ui.card class="p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-muted">{{ __('dashboard.inspector.net_earnings') }}</p>
                    <p class="text-2xl font-bold text-text mt-2">
                        <x-ui.money :amount="$netEarnings" />
                    </p>
                </div>
                <div class="p-3 bg-success/10 rounded-lg">
                    <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </x-ui.card>

        {{-- Total Inspections Card --}}
        <x-ui.card class="p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-muted">{{ __('dashboard.inspector.total_inspections') }}</p>
                    <p class="text-3xl font-bold text-text mt-2">{{ $totalInspections }}</p>
                </div>
                <div class="p-3 bg-primary/10 rounded-lg">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </x-ui.card>

        {{-- Rating Card --}}
        <x-ui.card class="p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-muted">{{ __('dashboard.inspector.rating') }}</p>
                    <p class="text-3xl font-bold text-text mt-2">
                        {{ $ratingCount > 0 ? number_format($rating, 2) : '-' }}
                    </p>
                    <p class="text-xs text-muted mt-1">
                        {{ $ratingCount > 0 ? __('rating.display.rating_count', ['count' => $ratingCount]) : __('rating.display.no_ratings') }}
                    </p>
                </div>
                <div class="p-3 bg-accent/10 rounded-lg">
                    <svg class="w-6 h-6 text-accent fill-current" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                    </svg>
                </div>
            </div>
        </x-ui.card>
    </div>

    {{-- Action Buttons --}}
    <div class="flex gap-3 flex-wrap">
        <x-ui.button :href="route('jobs.inspector')" variant="primary">
            {{ __('dashboard.inspector.view_available_jobs') }}
        </x-ui.button>
        <x-ui.button :href="route('inspector.earnings')" variant="secondary">
            {{ __('dashboard.inspector.view_earnings') }}
        </x-ui.button>
    </div>

    {{-- Empty State --}}
    @if ($availableJobs === 0 && $activeJobs === 0 && $totalInspections === 0)
        <x-ui.empty-state
            icon="briefcase"
            :title="__('dashboard.inspector.empty_state.title')"
            :message="__('dashboard.inspector.empty_state.message')"
        >
            <div class="flex gap-3 justify-center mt-6">
                <x-ui.button :href="route('jobs.inspector')" variant="primary">
                    {{ __('dashboard.inspector.empty_state.browse_jobs') }}
                </x-ui.button>
            </div>
        </x-ui.empty-state>
    @endif
</div>
