<div class="space-y-10">
    {{-- Buyer Section (kartu disembunyikan bila tidak ada aktivitas pembeli) --}}
    <section>
        <div class="mb-6 space-y-1">
            <span class="text-ds-blue text-xs font-extrabold uppercase tracking-wider">{{ __('dashboard.buyer.title') }}</span>
            <h2 class="text-2xl font-extrabold text-ds-ink tracking-tight">{{ __('dashboard.buyer.title') }}</h2>
        </div>
        @if ($runningInspections > 0 || $accessibleResults > 0 || $activeTransactions > 0)
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Running Inspections Card --}}
            <div class="group bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col justify-between">
                <div class="p-5">
                    <div class="w-14 h-14 rounded-2xl bg-ds-blue text-white flex items-center justify-center text-xl mb-4 shadow-lg shadow-ds-blue/20 group-hover:scale-110 transition-transform">
                        <i aria-hidden="true" class="fa-solid fa-clipboard-list"></i>
                    </div>
                    <p class="text-3xl font-extrabold text-ds-ink">{{ $runningInspections }}</p>
                    <p class="text-xs text-ds-muted mt-1 font-medium">{{ __('dashboard.buyer.running_inspections') }}</p>
                </div>
                <div class="px-5 py-3 bg-ds-canvas/80 border-t border-ds-line/80 flex items-center justify-between text-xs font-bold text-ds-blue">
                    <a href="{{ route('jobs.mine') }}" class="inline-flex items-center gap-1 hover:text-ds-blue-ink">
                        <span>{{ __('dashboard.view_history') }}</span>
                        <i aria-hidden="true" class="fa-solid fa-arrow-right text-[11px] group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
            </div>

            {{-- Accessible Results Card --}}
            <div class="group bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col justify-between">
                <div class="p-5">
                    <div class="w-14 h-14 rounded-2xl bg-ds-blue text-white flex items-center justify-center text-xl mb-4 shadow-lg shadow-ds-blue/20 group-hover:scale-110 transition-transform">
                        <i aria-hidden="true" class="fa-solid fa-circle-check"></i>
                    </div>
                    <p class="text-3xl font-extrabold text-ds-ink">{{ $accessibleResults }}</p>
                    <p class="text-xs text-ds-muted mt-1 font-medium">{{ __('dashboard.buyer.accessible_results') }}</p>
                </div>
                <div class="px-5 py-3 bg-ds-canvas/80 border-t border-ds-line/80 flex items-center justify-between text-xs font-bold text-ds-blue">
                    <a href="{{ route('dashboard.history') }}" class="inline-flex items-center gap-1 hover:text-ds-blue-ink">
                        <span>{{ __('dashboard.view_history') }}</span>
                        <i aria-hidden="true" class="fa-solid fa-arrow-right text-[11px] group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
            </div>

            {{-- Active Transactions Card --}}
            <div class="group bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col justify-between">
                <div class="p-5">
                    <div class="w-14 h-14 rounded-2xl bg-ds-blue text-white flex items-center justify-center text-xl mb-4 shadow-lg shadow-ds-blue/20 group-hover:scale-110 transition-transform">
                        <i aria-hidden="true" class="fa-solid fa-wallet"></i>
                    </div>
                    <p class="text-3xl font-extrabold text-ds-ink">{{ $activeTransactions }}</p>
                    <p class="text-xs text-ds-muted mt-1 font-medium">{{ __('dashboard.buyer.active_transactions') }}</p>
                </div>
                <div class="px-5 py-3 bg-ds-canvas/80 border-t border-ds-line/80 flex items-center justify-between text-xs font-bold text-ds-blue">
                    <a href="{{ route('transactions.index') }}" class="inline-flex items-center gap-1 hover:text-ds-blue-ink">
                        <span>{{ __('dashboard.view_history') }}</span>
                        <i aria-hidden="true" class="fa-solid fa-arrow-right text-[11px] group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
            </div>
        </div>
        @endif
    </section>

    {{-- Seller Section (kartu disembunyikan bila tidak ada aktivitas penjual) --}}
    <section>
        <div class="mb-6 space-y-1">
            <span class="text-ds-blue text-xs font-extrabold uppercase tracking-wider">{{ __('dashboard.seller.title') }}</span>
            <h2 class="text-2xl font-extrabold text-ds-ink tracking-tight">{{ __('dashboard.seller.title') }}</h2>
        </div>
        @if ($activeListings > 0 || $buyOffers > 0)
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Active Listings Card --}}
            <div class="group bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col justify-between">
                <div class="p-5">
                    <div class="w-14 h-14 rounded-2xl bg-ds-blue text-white flex items-center justify-center text-xl mb-4 shadow-lg shadow-ds-blue/20 group-hover:scale-110 transition-transform">
                        <i aria-hidden="true" class="fa-solid fa-car"></i>
                    </div>
                    <p class="text-3xl font-extrabold text-ds-ink">{{ $activeListings }}</p>
                    <p class="text-xs text-ds-muted mt-1 font-medium">{{ __('dashboard.seller.active_listings') }}</p>
                </div>
                <div class="px-5 py-3 bg-ds-canvas/80 border-t border-ds-line/80 flex items-center justify-between text-xs font-bold text-ds-blue">
                    <a href="{{ route('listings.mine') }}" class="inline-flex items-center gap-1 hover:text-ds-blue-ink">
                        <span>{{ __('dashboard.view_history') }}</span>
                        <i aria-hidden="true" class="fa-solid fa-arrow-right text-[11px] group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
            </div>

            {{-- Inspection Status Card --}}
            <div class="group bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col justify-between">
                <div class="p-5">
                    <div class="w-14 h-14 rounded-2xl bg-ds-blue text-white flex items-center justify-center text-xl mb-4 shadow-lg shadow-ds-blue/20 group-hover:scale-110 transition-transform">
                        <i aria-hidden="true" class="fa-solid fa-list-check"></i>
                    </div>
                    <p class="text-3xl font-extrabold text-ds-ink">{{ $runningInspections }}</p>
                    <p class="text-xs text-ds-muted mt-1 font-medium">{{ __('dashboard.seller.inspection_status') }}</p>
                </div>
                <div class="px-5 py-3 bg-ds-canvas/80 border-t border-ds-line/80 flex items-center justify-between text-xs font-bold text-ds-blue">
                    <a href="{{ route('jobs.mine') }}" class="inline-flex items-center gap-1 hover:text-ds-blue-ink">
                        <span>{{ __('dashboard.view_history') }}</span>
                        <i aria-hidden="true" class="fa-solid fa-arrow-right text-[11px] group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
            </div>

            {{-- Buy Offers Card --}}
            <div class="group bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col justify-between">
                <div class="p-5">
                    <div class="w-14 h-14 rounded-2xl bg-ds-blue text-white flex items-center justify-center text-xl mb-4 shadow-lg shadow-ds-blue/20 group-hover:scale-110 transition-transform">
                        <i aria-hidden="true" class="fa-solid fa-tag"></i>
                    </div>
                    <p class="text-3xl font-extrabold text-ds-ink">{{ $buyOffers }}</p>
                    <p class="text-xs text-ds-muted mt-1 font-medium">{{ __('dashboard.seller.buy_offers') }}</p>
                </div>
                <div class="px-5 py-3 bg-ds-canvas/80 border-t border-ds-line/80 flex items-center justify-between text-xs font-bold text-ds-blue">
                    <a href="{{ route('transactions.index') }}" class="inline-flex items-center gap-1 hover:text-ds-blue-ink">
                        <span>{{ __('dashboard.view_history') }}</span>
                        <i aria-hidden="true" class="fa-solid fa-arrow-right text-[11px] group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
            </div>
        </div>
        @endif
    </section>

    {{-- Empty State --}}
    @if ($runningInspections === 0 && $accessibleResults === 0 && $activeListings === 0 && $activeTransactions === 0 && $buyOffers === 0)
        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-8 text-center max-w-2xl mx-auto">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-ds-soft text-ds-muted flex items-center justify-center text-xl mb-4">
                <i aria-hidden="true" class="fa-solid fa-inbox"></i>
            </div>
            <h3 class="text-lg font-extrabold text-ds-ink">{{ __('dashboard.empty_state.title') }}</h3>
            <p class="text-xs text-ds-muted mt-2 leading-relaxed">{{ __('dashboard.empty_state.message') }}</p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center mt-6">
                <a href="{{ route('catalog.index') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md hover:shadow-ds-yellow/30">
                    <span>{{ __('dashboard.empty_state.search_vehicles') }}</span>
                    <i aria-hidden="true" class="fa-solid fa-arrow-right text-xs"></i>
                </a>
                <a href="{{ route('listings.create') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-sm rounded-xl transition-all shadow-xs hover:shadow-md">
                    {{ __('dashboard.empty_state.add_listing') }}
                </a>
            </div>
        </div>
    @endif
</div>
