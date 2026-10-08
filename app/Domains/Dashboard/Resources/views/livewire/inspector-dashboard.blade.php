<div class="space-y-10">
    <section>
        <div class="mb-6 space-y-1">
            <span class="text-ds-blue text-xs font-extrabold uppercase tracking-wider">{{ __('dashboard.inspector.title') }}</span>
            <h2 class="text-2xl font-extrabold text-ds-ink tracking-tight">{{ __('dashboard.inspector.title') }}</h2>
        </div>

        {{-- Key Metrics Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
            {{-- Available Jobs Card --}}
            <div class="group bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-xl transition-all duration-300 p-5">
                <div class="w-14 h-14 rounded-2xl bg-ds-blue text-white flex items-center justify-center text-xl mb-4 shadow-lg shadow-ds-blue/20 group-hover:scale-110 transition-transform">
                    <i aria-hidden="true" class="fa-solid fa-briefcase"></i>
                </div>
                <p class="text-3xl font-extrabold text-ds-ink">{{ $availableJobs }}</p>
                <p class="text-xs text-ds-muted mt-1 font-medium">{{ __('dashboard.inspector.available_jobs') }}</p>
            </div>

            {{-- Active Jobs Card --}}
            <div class="group bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-xl transition-all duration-300 p-5">
                <div class="w-14 h-14 rounded-2xl bg-ds-blue text-white flex items-center justify-center text-xl mb-4 shadow-lg shadow-ds-blue/20 group-hover:scale-110 transition-transform">
                    <i aria-hidden="true" class="fa-solid fa-bolt"></i>
                </div>
                <p class="text-3xl font-extrabold text-ds-ink">{{ $activeJobs }}</p>
                <p class="text-xs text-ds-muted mt-1 font-medium">{{ __('dashboard.inspector.active_jobs') }}</p>
            </div>

            {{-- Net Earnings Card --}}
            <div class="group bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-xl transition-all duration-300 p-5">
                <div class="w-14 h-14 rounded-2xl bg-ds-blue text-white flex items-center justify-center text-xl mb-4 shadow-lg shadow-ds-blue/20 group-hover:scale-110 transition-transform">
                    <i aria-hidden="true" class="fa-solid fa-wallet"></i>
                </div>
                <p class="text-2xl font-extrabold text-ds-ink">{{ \App\Support\Money::format($netEarnings) }}</p>
                <p class="text-xs text-ds-muted mt-1 font-medium">{{ __('dashboard.inspector.net_earnings') }}</p>
            </div>

            {{-- Total Inspections Card --}}
            <div class="group bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-xl transition-all duration-300 p-5">
                <div class="w-14 h-14 rounded-2xl bg-ds-blue text-white flex items-center justify-center text-xl mb-4 shadow-lg shadow-ds-blue/20 group-hover:scale-110 transition-transform">
                    <i aria-hidden="true" class="fa-solid fa-clipboard-check"></i>
                </div>
                <p class="text-3xl font-extrabold text-ds-ink">{{ $totalInspections }}</p>
                <p class="text-xs text-ds-muted mt-1 font-medium">{{ __('dashboard.inspector.total_inspections') }}</p>
            </div>

            {{-- Rating Card --}}
            <div class="group bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-xl transition-all duration-300 p-5">
                <div class="w-14 h-14 rounded-2xl bg-ds-blue text-white flex items-center justify-center text-xl mb-4 shadow-lg shadow-ds-blue/20 group-hover:scale-110 transition-transform">
                    <i aria-hidden="true" class="fa-solid fa-star"></i>
                </div>
                <p class="text-3xl font-extrabold text-ds-ink">
                    {{ $ratingCount > 0 ? number_format($rating, 2) : '-' }}
                </p>
                <p class="text-xs text-ds-muted mt-1 font-medium">{{ __('dashboard.inspector.rating') }}</p>
                <p class="text-[11px] text-ds-faint mt-1 font-semibold">
                    {{ $ratingCount > 0 ? __('rating.display.rating_count', ['count' => $ratingCount]) : __('rating.display.no_ratings') }}
                </p>
            </div>
        </div>
    </section>

    {{-- Action Buttons --}}
    <div class="flex flex-col sm:flex-row gap-3">
        <a href="{{ route('jobs.inspector') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md hover:shadow-ds-yellow/30">
            <span>{{ __('dashboard.inspector.view_available_jobs') }}</span>
            <i aria-hidden="true" class="fa-solid fa-arrow-right text-xs"></i>
        </a>
        <a href="{{ route('inspector.earnings') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-sm rounded-xl transition-all shadow-xs hover:shadow-md">
            {{ __('dashboard.inspector.view_earnings') }}
        </a>
    </div>

    {{-- Empty State --}}
    @if ($availableJobs === 0 && $activeJobs === 0 && $totalInspections === 0)
        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-8 text-center max-w-2xl mx-auto">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-ds-soft text-ds-muted flex items-center justify-center text-xl mb-4">
                <i aria-hidden="true" class="fa-solid fa-briefcase"></i>
            </div>
            <h3 class="text-lg font-extrabold text-ds-ink">{{ __('dashboard.inspector.empty_state.title') }}</h3>
            <p class="text-xs text-ds-muted mt-2 leading-relaxed">{{ __('dashboard.inspector.empty_state.message') }}</p>
            <div class="flex justify-center mt-6">
                <a href="{{ route('jobs.inspector') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md hover:shadow-ds-yellow/30">
                    <span>{{ __('dashboard.inspector.empty_state.browse_jobs') }}</span>
                    <i aria-hidden="true" class="fa-solid fa-arrow-right text-xs"></i>
                </a>
            </div>
        </div>
    @endif
</div>
