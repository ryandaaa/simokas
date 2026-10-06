<div class="space-y-6">
    {{-- Buyer Section --}}
    <div>
        <h2 class="text-lg font-bold text-text mb-4">{{ __('dashboard.buyer.title') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Running Inspections Card --}}
            <x-ui.card class="p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-muted">{{ __('dashboard.buyer.running_inspections') }}</p>
                        <p class="text-3xl font-bold text-text mt-2">{{ $runningInspections }}</p>
                    </div>
                    <div class="p-3 bg-primary/10 rounded-lg">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
            </x-ui.card>

            {{-- Accessible Results Card --}}
            <x-ui.card class="p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-muted">{{ __('dashboard.buyer.accessible_results') }}</p>
                        <p class="text-3xl font-bold text-text mt-2">{{ $accessibleResults }}</p>
                    </div>
                    <div class="p-3 bg-success/10 rounded-lg">
                        <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </x-ui.card>

            {{-- Active Transactions Card (P1 - Hidden) --}}
            <x-ui.card class="p-6 opacity-50">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-muted">{{ __('dashboard.buyer.active_transactions') }}</p>
                        <p class="text-3xl font-bold text-text mt-2 blur-sm">-</p>
                        <p class="text-xs text-warning mt-1">{{ __('dashboard.coming_soon') }}</p>
                    </div>
                    <div class="p-3 bg-warning/10 rounded-lg">
                        <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </x-ui.card>
        </div>
    </div>

    {{-- Seller Section --}}
    <div>
        <h2 class="text-lg font-bold text-text mb-4">{{ __('dashboard.seller.title') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Active Listings Card --}}
            <x-ui.card class="p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-muted">{{ __('dashboard.seller.active_listings') }}</p>
                        <p class="text-3xl font-bold text-text mt-2">{{ $activeListings }}</p>
                    </div>
                    <div class="p-3 bg-primary/10 rounded-lg">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5.5m0 0h-5m5 0v-5.5"></path>
                        </svg>
                    </div>
                </div>
            </x-ui.card>

            {{-- Listing Status Card --}}
            <x-ui.card class="p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-muted">{{ __('dashboard.seller.inspection_status') }}</p>
                        <p class="text-3xl font-bold text-text mt-2">{{ $runningInspections }}</p>
                    </div>
                    <div class="p-3 bg-info/10 rounded-lg">
                        <svg class="w-6 h-6 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m7-1a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </x-ui.card>

            {{-- Buy Offers Card (P1 - Hidden) --}}
            <x-ui.card class="p-6 opacity-50">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-muted">{{ __('dashboard.seller.buy_offers') }}</p>
                        <p class="text-3xl font-bold text-text mt-2 blur-sm">-</p>
                        <p class="text-xs text-warning mt-1">{{ __('dashboard.coming_soon') }}</p>
                    </div>
                    <div class="p-3 bg-accent/10 rounded-lg">
                        <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.646 7.23a2 2 0 01-1.788 1.106H2.4a2 2 0 01-2-2V4a2 2 0 012-2h2.4"></path>
                        </svg>
                    </div>
                </div>
            </x-ui.card>
        </div>
    </div>

    {{-- Empty State --}}
    @if ($runningInspections === 0 && $accessibleResults === 0 && $activeListings === 0)
        <x-ui.empty-state
            icon="inbox"
            :title="__('dashboard.empty_state.title')"
            :message="__('dashboard.empty_state.message')"
        >
            <div class="flex gap-3 justify-center mt-6">
                <x-ui.button :href="route('catalog.index')" variant="primary">
                    {{ __('dashboard.empty_state.search_vehicles') }}
                </x-ui.button>
                <x-ui.button :href="route('listings.create')" variant="secondary">
                    {{ __('dashboard.empty_state.add_listing') }}
                </x-ui.button>
            </div>
        </x-ui.empty-state>
    @endif
</div>
