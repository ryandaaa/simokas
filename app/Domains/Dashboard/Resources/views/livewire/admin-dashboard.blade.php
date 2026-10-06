<div class="space-y-6">
    {{-- Key Metrics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {{-- Total Users Card --}}
        <x-ui.card class="p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-muted">{{ __('dashboard.admin.total_users') }}</p>
                    <p class="text-3xl font-bold text-text mt-2">{{ $totalUsers }}</p>
                </div>
                <div class="p-3 bg-primary/10 rounded-lg">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.856-1.487M15 6a3 3 0 11-6 0 3 3 0 016 0zM16 16a5 5 0 11-10 0 5 5 0 0110 0z"></path>
                    </svg>
                </div>
            </div>
        </x-ui.card>

        {{-- Total Inspectors Card --}}
        <x-ui.card class="p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-muted">{{ __('dashboard.admin.total_inspectors') }}</p>
                    <p class="text-3xl font-bold text-text mt-2">{{ $totalInspectors }}</p>
                </div>
                <div class="p-3 bg-info/10 rounded-lg">
                    <svg class="w-6 h-6 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>
        </x-ui.card>

        {{-- Platform Earnings Card --}}
        <x-ui.card class="p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-muted">{{ __('dashboard.admin.platform_earnings') }}</p>
                    <p class="text-2xl font-bold text-text mt-2">
                        <x-ui.money :amount="$platformEarnings" />
                    </p>
                </div>
                <div class="p-3 bg-success/10 rounded-lg">
                    <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </x-ui.card>
    </div>

    {{-- Job Status Breakdown --}}
    <x-ui.card class="p-6">
        <h3 class="text-lg font-bold text-text mb-4">{{ __('dashboard.admin.jobs_by_status') }}</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach ($jobsByStatus as $status => $count)
                <div class="p-4 rounded-lg bg-surface border border-border">
                    <p class="text-sm text-muted capitalize">{{ str_replace('_', ' ', $status) }}</p>
                    <p class="text-2xl font-bold text-text mt-2">{{ $count }}</p>
                </div>
            @endforeach
        </div>
    </x-ui.card>

    {{-- Action Queues --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- Listing Queue Card --}}
        <x-ui.card class="p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-muted">{{ __('dashboard.admin.listing_queue') }}</p>
                    <p class="text-3xl font-bold text-text mt-2">{{ $listingQueue }}</p>
                    <p class="text-xs text-warning mt-1">{{ __('dashboard.admin.pending_review') }}</p>
                </div>
                <div class="p-3 bg-warning/10 rounded-lg">
                    <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                </div>
            </div>
            <x-ui.button :href="route('admin.listings.index')" variant="ghost" class="mt-4 w-full">
                {{ __('dashboard.admin.view_queue') }}
            </x-ui.button>
        </x-ui.card>

        {{-- Inspector Queue Card --}}
        <x-ui.card class="p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-muted">{{ __('dashboard.admin.inspector_queue') }}</p>
                    <p class="text-3xl font-bold text-text mt-2">{{ $inspectorQueue }}</p>
                    <p class="text-xs text-warning mt-1">{{ __('dashboard.admin.pending_verification') }}</p>
                </div>
                <div class="p-3 bg-warning/10 rounded-lg">
                    <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                </div>
            </div>
            <x-ui.button :href="route('admin.inspectors.index')" variant="ghost" class="mt-4 w-full">
                {{ __('dashboard.admin.view_queue') }}
            </x-ui.button>
        </x-ui.card>

        {{-- Items Needing Action Card --}}
        <x-ui.card class="p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-muted">{{ __('dashboard.admin.items_needing_action') }}</p>
                    <p class="text-3xl font-bold text-text mt-2 @if ($itemsNeedingAction > 0) text-danger @endif">
                        {{ $itemsNeedingAction }}
                    </p>
                    @if ($itemsNeedingAction > 0)
                        <p class="text-xs text-danger mt-1">{{ __('dashboard.admin.attention_required') }}</p>
                    @else
                        <p class="text-xs text-success mt-1">{{ __('dashboard.admin.all_clear') }}</p>
                    @endif
                </div>
                <div class="p-3 @if ($itemsNeedingAction > 0) bg-danger/10 @else bg-success/10 @endif rounded-lg">
                    <svg class="w-6 h-6 @if ($itemsNeedingAction > 0) text-danger @else text-success @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </x-ui.card>
    </div>

    {{-- Admin Actions --}}
    <div class="flex gap-3 flex-wrap">
        <x-ui.button :href="route('admin.settings')" variant="primary">
            {{ __('dashboard.admin.manage_settings') }}
        </x-ui.button>
        <x-ui.button :href="route('admin.users')" variant="secondary">
            {{ __('dashboard.admin.manage_users') }}
        </x-ui.button>
    </div>
</div>
