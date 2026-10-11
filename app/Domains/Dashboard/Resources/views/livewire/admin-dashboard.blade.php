<div class="space-y-10">
    @if (session('success'))
        <div role="status" class="p-4 rounded-2xl border border-ds-green-soft bg-ds-green-soft/40 text-xs font-semibold text-ds-ink">
            {{ session('success') }}
        </div>
    @endif

    <section>
        <div class="mb-6 space-y-1">
            <h2 class="text-2xl font-extrabold text-ds-ink tracking-tight">{{ __('dashboard.admin.title') }}</h2>
        </div>

        {{-- Key Metrics Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mx-3 md:mx-0">
            {{-- Total Users Card --}}
            <div class="group bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-xl transition-all duration-300 p-4 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-ds-blue text-white flex items-center justify-center text-lg shrink-0 shadow-lg shadow-ds-blue/20 group-hover:scale-110 transition-transform">
                    <i aria-hidden="true" class="fa-solid fa-users"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-2xl font-extrabold text-ds-ink leading-tight">{{ $totalUsers }}</p>
                    <p class="text-xs text-ds-muted font-medium truncate">{{ __('dashboard.admin.total_users') }}</p>
                </div>
            </div>

            {{-- Total Inspectors Card --}}
            <div class="group bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-xl transition-all duration-300 p-4 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-ds-blue text-white flex items-center justify-center text-lg shrink-0 shadow-lg shadow-ds-blue/20 group-hover:scale-110 transition-transform">
                    <i aria-hidden="true" class="fa-solid fa-user-gear"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-2xl font-extrabold text-ds-ink leading-tight">{{ $totalInspectors }}</p>
                    <p class="text-xs text-ds-muted font-medium truncate">{{ __('dashboard.admin.total_inspectors') }}</p>
                </div>
            </div>

            {{-- Platform Earnings Card --}}
            <div class="group bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-xl transition-all duration-300 p-4 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-ds-blue text-white flex items-center justify-center text-lg shrink-0 shadow-lg shadow-ds-blue/20 group-hover:scale-110 transition-transform">
                    <i aria-hidden="true" class="fa-solid fa-chart-line"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xl font-extrabold text-ds-ink leading-tight">{{ \App\Support\Money::format($platformEarnings) }}</p>
                    <p class="text-xs text-ds-muted font-medium truncate">{{ __('dashboard.admin.platform_earnings') }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Action Queues --}}
    <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Listing Queue Card --}}
        <div class="bg-ds-canvas rounded-2xl p-6 border border-ds-line/60 relative overflow-hidden group hover:bg-white hover:shadow-xl transition-all duration-300 flex flex-col">
            <span class="text-4xl font-extrabold text-ds-green">{{ $listingQueue }}</span>
            <h3 class="text-lg font-bold text-ds-ink mt-3">{{ __('dashboard.admin.listing_queue') }}</h3>
            <i aria-hidden="true" class="fa-solid fa-clipboard-list absolute top-6 right-6 text-6xl leading-none text-ds-yellow-deep/25 pointer-events-none select-none"></i>
            <a href="{{ route('admin.listings.index') }}" class="relative mt-5 w-full py-2 bg-white border border-ds-line hover:bg-ds-blue hover:border-ds-blue hover:text-white text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs text-center">
                {{ __('dashboard.admin.view_queue') }}
            </a>
        </div>

        {{-- Inspector Queue Card --}}
        <div class="bg-ds-canvas rounded-2xl p-6 border border-ds-line/60 relative overflow-hidden group hover:bg-white hover:shadow-xl transition-all duration-300 flex flex-col">
            <span class="text-4xl font-extrabold text-ds-green">{{ $inspectorQueue }}</span>
            <h3 class="text-lg font-bold text-ds-ink mt-3">{{ __('dashboard.admin.inspector_queue') }}</h3>
            <i aria-hidden="true" class="fa-solid fa-user-check absolute top-6 right-6 text-6xl leading-none text-ds-yellow-deep/25 pointer-events-none select-none"></i>
            <a href="{{ route('admin.inspectors.index') }}" class="relative mt-5 w-full py-2 bg-white border border-ds-line hover:bg-ds-blue hover:border-ds-blue hover:text-white text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs text-center">
                {{ __('dashboard.admin.view_queue') }}
            </a>
        </div>

        {{-- Items Needing Action Card --}}
        <div class="bg-ds-canvas rounded-2xl p-6 border border-ds-line/60 relative overflow-hidden group hover:bg-white hover:shadow-xl transition-all duration-300 flex flex-col">
            <span class="text-4xl font-extrabold text-ds-green">{{ $itemsNeedingAction }}</span>
            <h3 class="text-lg font-bold text-ds-ink mt-3">{{ __('dashboard.admin.items_needing_action') }}</h3>
            <i aria-hidden="true" class="fa-solid fa-triangle-exclamation absolute top-6 right-6 text-6xl leading-none {{ $itemsNeedingAction > 0 ? 'text-ds-danger/20' : 'text-ds-green/20' }} pointer-events-none select-none"></i>
            <a href="{{ route('admin.queue.index') }}" class="relative mt-5 w-full py-2 bg-white border border-ds-line hover:bg-ds-blue hover:border-ds-blue hover:text-white text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs text-center">
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

    {{-- Job Status Breakdown --}}
    <section class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
        <div class="mb-6 space-y-1">
            <h3 class="text-xl font-extrabold text-ds-ink tracking-tight">{{ __('dashboard.admin.jobs_by_status') }}</h3>
        </div>
        <div class="overflow-x-auto -mx-6 px-6">
            <table class="w-full text-sm min-w-[320px]">
                <thead>
                    <tr class="text-left text-[10px] font-bold uppercase tracking-wider text-ds-faint border-b border-ds-line/60">
                        <th scope="col" class="py-2 pr-4">{{ __('dashboard.admin.table_status') }}</th>
                        <th scope="col" class="py-2 text-right">{{ __('dashboard.admin.table_count') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ds-line/60">
                    @foreach ($jobsByStatus as $status => $count)
                        <tr class="transition-colors hover:bg-ds-canvas/60">
                            <td class="py-3 pr-4 text-xs font-bold uppercase tracking-wider text-ds-ink">{{ str_replace('_', ' ', $status) }}</td>
                            <td class="py-3 text-right text-xl font-extrabold text-ds-ink">{{ $count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    @php
        $maxTrend = max(1, max(array_column($monthlySeries, 'value')));
        $barClass = function ($value, $max) {
            if ($value <= 0) {
                return 'h-1 opacity-40';
            }
            $pct = $value / max(1, $max) * 100;

            return match (true) {
                $pct <= 15 => 'h-4',
                $pct <= 30 => 'h-8',
                $pct <= 45 => 'h-12',
                $pct <= 60 => 'h-16',
                $pct <= 75 => 'h-20',
                $pct <= 87 => 'h-24',
                default => 'h-28',
            };
        };
        $statusPill = fn ($color) => match ($color) {
            'success' => 'bg-ds-green-soft text-ds-green-ink',
            'warning' => 'bg-ds-amber-soft text-ds-amber-ink',
            'danger' => 'bg-ds-red-soft text-ds-red-ink',
            'primary' => 'bg-ds-blue-soft text-ds-blue-ink',
            default => 'bg-ds-soft text-ds-muted',
        };
        $statusDot = fn ($color) => match ($color) {
            'success' => 'bg-ds-green',
            'warning' => 'bg-ds-yellow-deep',
            'danger' => 'bg-ds-danger',
            'primary' => 'bg-ds-blue',
            default => 'bg-ds-faint',
        };
        $waiting = ($jobsByStatus['requested'] ?? 0) + ($jobsByStatus['paid'] ?? 0);
        $running = ($jobsByStatus['taken'] ?? 0) + ($jobsByStatus['scheduled'] ?? 0) + ($jobsByStatus['in_progress'] ?? 0) + ($jobsByStatus['result_submitted'] ?? 0);
        $done = $jobsByStatus['completed'] ?? 0;
        $cancelled = $jobsByStatus['cancelled'] ?? 0;
        $donutTotal = max(1, $waiting + $running + $done + $cancelled);
        $donutCirc = 2 * pi() * 54;
        $donutSegs = [
            ['label' => __('dashboard.admin.waiting_label'), 'value' => $waiting, 'class' => 'text-ds-yellow-deep'],
            ['label' => __('dashboard.admin.running_label'), 'value' => $running, 'class' => 'text-ds-blue'],
            ['label' => __('dashboard.admin.completed_label'), 'value' => $done, 'class' => 'text-ds-green'],
            ['label' => __('dashboard.admin.cancelled_label'), 'value' => $cancelled, 'class' => 'text-ds-danger'],
        ];
        $donutOffset = 0;
    @endphp

    {{-- Tren + Komposisi --}}
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
            <h3 class="text-lg font-extrabold text-ds-ink">{{ __('dashboard.admin.trend_title') }}</h3>
            <p class="text-xs text-ds-muted mt-1 mb-6">{{ __('dashboard.admin.trend_subtitle') }}</p>

            <div class="flex items-start justify-between gap-2 h-36" role="img" aria-label="{{ __('dashboard.admin.trend_title') }}">
                @foreach ($monthlySeries as $point)
                    <div class="flex-1 flex flex-col items-center gap-2 h-full">
                        <span class="text-xs font-extrabold text-ds-ink">{{ $point['value'] }}</span>
                        <div class="flex-1 w-full max-w-10 flex items-end">
                            <div class="w-full rounded-lg bg-ds-blue transition-all {{ $barClass($point['value'], $maxTrend) }}"></div>
                        </div>
                        <span class="text-[10px] font-bold text-ds-faint uppercase">{{ $point['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
            <h3 class="text-lg font-extrabold text-ds-ink">{{ __('dashboard.admin.composition_title') }}</h3>
            <p class="text-xs text-ds-muted mt-1 mb-4">{{ __('dashboard.admin.composition_subtitle') }}</p>

            <div class="flex justify-center">
                <svg viewBox="0 0 140 140" class="w-32 h-32" role="img" aria-label="{{ __('dashboard.admin.composition_title') }}">
                    <circle cx="70" cy="70" r="54" fill="none" class="text-ds-soft" stroke="currentColor" stroke-width="18" />
                    @foreach ($donutSegs as $seg)
                        @php
                            $frac = $seg['value'] / $donutTotal;
                            $dash = round($frac * $donutCirc, 1).' '.round($donutCirc - $frac * $donutCirc, 1);
                            $off = round(-$donutOffset * $donutCirc, 1);
                            $donutOffset += $frac;
                        @endphp
                        @if ($seg['value'] > 0)
                            <circle cx="70" cy="70" r="54" fill="none" class="{{ $seg['class'] }}" stroke="currentColor"
                                    stroke-width="18" stroke-dasharray="{{ $dash }}" stroke-dashoffset="{{ $off }}"
                                    transform="rotate(-90 70 70)" stroke-linecap="butt" />
                        @endif
                    @endforeach
                </svg>
            </div>

            <ul class="mt-5 space-y-3">
                @foreach ($donutSegs as $seg)
                    <li class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2 font-semibold text-ds-muted">
                            <span class="w-2.5 h-2.5 rounded-full {{ $seg['class'] }} bg-current"></span>
                            {{ $seg['label'] }}
                        </span>
                        <span class="font-extrabold text-ds-ink">{{ round($seg['value'] / $donutTotal * 100) }}%</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- Aktivitas + Daftar Job --}}
    <section class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        <div class="lg:col-span-2 bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
            <h3 class="text-lg font-extrabold text-ds-ink">{{ __('dashboard.admin.recent_activity_title') }}</h3>
            <p class="text-xs text-ds-muted mt-1 mb-5">{{ __('dashboard.admin.recent_activity_subtitle') }}</p>

            @if ($recentJobs->isEmpty())
                <p class="text-xs text-ds-muted leading-relaxed">{{ __('dashboard.admin.no_activity') }}</p>
            @else
                <ul class="space-y-5">
                    @foreach ($recentJobs as $job)
                        <li class="flex items-start gap-3">
                                <span class="mt-1.5 w-2.5 h-2.5 rounded-full shrink-0 {{ $statusDot($job->status->color()) }}"></span>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-ds-ink truncate">{{ $job->listing?->title ?? '—' }}</p>
                                <p class="text-[11px] text-ds-faint mt-0.5">
                                    {{ $job->status->label() }} · {{ $job->updated_at->locale('id')->diffForHumans() }}
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="lg:col-span-3 bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-lg font-extrabold text-ds-ink">{{ __('dashboard.admin.recent_jobs_title') }}</h3>
                    <p class="text-xs text-ds-muted mt-1">{{ __('dashboard.admin.recent_jobs_subtitle') }}</p>
                </div>
                <a href="{{ route('admin.queue.index') }}" class="text-xs font-bold text-ds-blue hover:text-ds-blue-ink shrink-0 ml-4">
                    {{ __('dashboard.admin.view_all_jobs') }}
                </a>
            </div>

            @if ($recentJobs->isEmpty())
                <p class="text-xs text-ds-muted leading-relaxed">{{ __('dashboard.admin.no_activity') }}</p>
            @else
                <div class="overflow-x-auto -mx-6 px-6">
                    <table class="w-full text-sm min-w-[520px]">
                        <thead>
                            <tr class="text-left text-[10px] font-bold uppercase tracking-wider text-ds-faint border-b border-ds-line/60">
                                <th scope="col" class="py-2 pr-4">{{ __('dashboard.admin.table_listing') }}</th>
                                <th scope="col" class="py-2 pr-4">{{ __('dashboard.admin.table_requester') }}</th>
                                <th scope="col" class="py-2 pr-4">{{ __('dashboard.admin.table_updated') }}</th>
                                <th scope="col" class="py-2 text-right">{{ __('dashboard.admin.table_status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ds-line/60">
                            @foreach ($recentJobs as $job)
                                <tr class="transition-colors hover:bg-ds-canvas/60">
                                    <td class="py-3 pr-4 font-bold text-ds-ink max-w-44 truncate">{{ $job->listing?->title ?? '—' }}</td>
                                    <td class="py-3 pr-4 text-ds-muted text-xs">{{ $job->requester?->name ?? '—' }}</td>
                                    <td class="py-3 pr-4 text-ds-faint text-xs whitespace-nowrap">{{ $job->updated_at->locale('id')->diffForHumans() }}</td>
                                    <td class="py-3 text-right">
                                        <span class="inline-block text-[10px] px-2.5 py-1 rounded-full font-bold uppercase tracking-wider whitespace-nowrap {{ $statusPill($job->status->color()) }}">
                                            {{ $job->status->label() }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="mt-4 text-[11px] text-ds-faint">
                    {{ __('dashboard.admin.showing_recent', ['count' => $recentJobs->count()]) }}
                </p>
            @endif
        </div>
    </section>
</div>
