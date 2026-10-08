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
