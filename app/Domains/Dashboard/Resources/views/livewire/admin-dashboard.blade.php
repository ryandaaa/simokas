<div class="space-y-10">
    <section>
        <div class="mb-6 space-y-1">
            <span class="text-ds-blue text-xs font-extrabold uppercase tracking-wider">{{ __('dashboard.admin.title') }}</span>
            <h2 class="text-2xl font-extrabold text-ds-ink tracking-tight">{{ __('dashboard.admin.title') }}</h2>
        </div>

        {{-- Key Metrics Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Total Users Card --}}
            <div class="group bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-xl transition-all duration-300 p-5">
                <div class="w-14 h-14 rounded-2xl bg-ds-blue text-white flex items-center justify-center text-xl mb-4 shadow-lg shadow-ds-blue/20 group-hover:scale-110 transition-transform">
                    <i aria-hidden="true" class="fa-solid fa-users"></i>
                </div>
                <p class="text-3xl font-extrabold text-ds-ink">{{ $totalUsers }}</p>
                <p class="text-xs text-ds-muted mt-1 font-medium">{{ __('dashboard.admin.total_users') }}</p>
            </div>

            {{-- Total Inspectors Card --}}
            <div class="group bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-xl transition-all duration-300 p-5">
                <div class="w-14 h-14 rounded-2xl bg-ds-blue text-white flex items-center justify-center text-xl mb-4 shadow-lg shadow-ds-blue/20 group-hover:scale-110 transition-transform">
                    <i aria-hidden="true" class="fa-solid fa-user-gear"></i>
                </div>
                <p class="text-3xl font-extrabold text-ds-ink">{{ $totalInspectors }}</p>
                <p class="text-xs text-ds-muted mt-1 font-medium">{{ __('dashboard.admin.total_inspectors') }}</p>
            </div>

            {{-- Platform Earnings Card --}}
            <div class="group bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-xl transition-all duration-300 p-5">
                <div class="w-14 h-14 rounded-2xl bg-ds-blue text-white flex items-center justify-center text-xl mb-4 shadow-lg shadow-ds-blue/20 group-hover:scale-110 transition-transform">
                    <i aria-hidden="true" class="fa-solid fa-chart-line"></i>
                </div>
                <p class="text-2xl font-extrabold text-ds-ink">{{ \App\Support\Money::format($platformEarnings) }}</p>
                <p class="text-xs text-ds-muted mt-1 font-medium">{{ __('dashboard.admin.platform_earnings') }}</p>
            </div>
        </div>
    </section>

    {{-- Job Status Breakdown --}}
    <section class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
        <div class="mb-6 space-y-1">
            <span class="text-ds-blue text-xs font-extrabold uppercase tracking-wider">{{ __('dashboard.admin.jobs_by_status') }}</span>
            <h3 class="text-xl font-extrabold text-ds-ink tracking-tight">{{ __('dashboard.admin.jobs_by_status') }}</h3>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @foreach ($jobsByStatus as $status => $count)
                @php
                    $pill = match ($status) {
                        'completed' => 'bg-ds-green-soft text-ds-green-ink',
                        'cancelled' => 'bg-ds-red-soft text-ds-red-ink',
                        'requested' => 'bg-ds-amber-soft text-ds-amber-ink',
                        default => 'bg-ds-blue-soft text-ds-blue-ink',
                    };
                @endphp
                <div class="bg-ds-canvas rounded-2xl p-5 border border-ds-line hover:bg-white hover:shadow-xl transition-all duration-300">
                    <span class="text-[10px] px-2 py-0.5 rounded-md font-bold {{ $pill }}">{{ str_replace('_', ' ', $status) }}</span>
                    <p class="text-3xl font-extrabold text-ds-ink mt-3">{{ $count }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Action Queues --}}
    <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Listing Queue Card --}}
        <div class="group bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-xl transition-all duration-300 p-5 flex flex-col justify-between">
            <div>
                <div class="w-14 h-14 rounded-2xl bg-ds-blue text-white flex items-center justify-center text-xl mb-4 shadow-lg shadow-ds-blue/20 group-hover:scale-110 transition-transform">
                    <i aria-hidden="true" class="fa-solid fa-clipboard-list"></i>
                </div>
                <p class="text-3xl font-extrabold text-ds-ink">{{ $listingQueue }}</p>
                <p class="text-xs text-ds-muted mt-1 font-medium">{{ __('dashboard.admin.listing_queue') }}</p>
                <span class="inline-block mt-2 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider bg-ds-amber-soft text-ds-amber-ink">{{ __('dashboard.admin.pending_review') }}</span>
            </div>
            <a href="{{ route('admin.listings.index') }}" class="mt-5 w-full py-2 bg-white border border-ds-line hover:bg-ds-blue hover:border-ds-blue hover:text-white text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs text-center">
                {{ __('dashboard.admin.view_queue') }}
            </a>
        </div>

        {{-- Inspector Queue Card --}}
        <div class="group bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-xl transition-all duration-300 p-5 flex flex-col justify-between">
            <div>
                <div class="w-14 h-14 rounded-2xl bg-ds-blue text-white flex items-center justify-center text-xl mb-4 shadow-lg shadow-ds-blue/20 group-hover:scale-110 transition-transform">
                    <i aria-hidden="true" class="fa-solid fa-user-check"></i>
                </div>
                <p class="text-3xl font-extrabold text-ds-ink">{{ $inspectorQueue }}</p>
                <p class="text-xs text-ds-muted mt-1 font-medium">{{ __('dashboard.admin.inspector_queue') }}</p>
                <span class="inline-block mt-2 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider bg-ds-amber-soft text-ds-amber-ink">{{ __('dashboard.admin.pending_verification') }}</span>
            </div>
            <a href="{{ route('admin.inspectors.index') }}" class="mt-5 w-full py-2 bg-white border border-ds-line hover:bg-ds-blue hover:border-ds-blue hover:text-white text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs text-center">
                {{ __('dashboard.admin.view_queue') }}
            </a>
        </div>

        {{-- Items Needing Action Card --}}
        <div class="group bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-xl transition-all duration-300 p-5 flex flex-col justify-between">
            <div>
                <div class="w-14 h-14 rounded-2xl {{ $itemsNeedingAction > 0 ? 'bg-ds-danger text-white shadow-lg shadow-ds-danger/20' : 'bg-ds-green text-white shadow-lg shadow-ds-green/20' }} flex items-center justify-center text-xl mb-4 group-hover:scale-110 transition-transform">
                    <i aria-hidden="true" class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <p class="text-3xl font-extrabold text-ds-ink">{{ $itemsNeedingAction }}</p>
                <p class="text-xs text-ds-muted mt-1 font-medium">{{ __('dashboard.admin.items_needing_action') }}</p>
                @if ($itemsNeedingAction > 0)
                    <span class="inline-block mt-2 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider bg-ds-red-soft text-ds-red-ink">{{ __('dashboard.admin.attention_required') }}</span>
                @else
                    <span class="inline-block mt-2 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider bg-ds-green-soft text-ds-green-ink">{{ __('dashboard.admin.all_clear') }}</span>
                @endif
            </div>
            <a href="{{ route('admin.queue.index') }}" class="mt-5 w-full py-2 bg-white border border-ds-line hover:bg-ds-blue hover:border-ds-blue hover:text-white text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs text-center">
                {{ __('dashboard.admin.view_queue') }}
            </a>
        </div>
    </section>

    {{-- Admin Actions --}}
    <div class="flex flex-col sm:flex-row gap-3">
        <a href="{{ route('admin.settings') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md hover:shadow-ds-yellow/30">
            <span>{{ __('dashboard.admin.manage_settings') }}</span>
            <i aria-hidden="true" class="fa-solid fa-arrow-right text-xs"></i>
        </a>
    </div>
</div>
