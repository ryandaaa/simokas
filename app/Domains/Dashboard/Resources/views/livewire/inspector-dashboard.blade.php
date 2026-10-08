<div class="space-y-6">
    @php
        $maxTrend = max(1, max(array_column($monthlySeries, 'value')));
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

        // SVG area chart dari data tren bulanan
        $chartW = 600;
        $chartH = 170;
        $chartPad = 10;
        $chartPts = [];
        foreach (array_values($monthlySeries) as $i => $point) {
            $x = $chartPad + $i * (($chartW - 2 * $chartPad) / max(1, count($monthlySeries) - 1));
            $y = $chartH - $chartPad - ($point['value'] / $maxTrend) * ($chartH - 2 * $chartPad);
            $chartPts[] = [round($x, 1), round($y, 1)];
        }
        $linePath = 'M '.implode(' L ', array_map(fn ($p) => $p[0].' '.$p[1], $chartPts));
        $areaPath = $linePath.' L '.$chartPts[count($chartPts) - 1][0].' '.$chartH.' L '.$chartPts[0][0].' '.$chartH.' Z';

        // Donut komposisi job
        $donutTotal = max(1, $availableJobs + $activeJobs + $totalInspections);
        $donutCirc = 2 * pi() * 54;
        $donutSegs = [
            ['label' => __('dashboard.inspector.available_jobs'), 'value' => $availableJobs, 'class' => 'text-ds-blue'],
            ['label' => __('dashboard.inspector.active_jobs'), 'value' => $activeJobs, 'class' => 'text-ds-yellow-deep'],
            ['label' => __('dashboard.inspector.total_inspections'), 'value' => $totalInspections, 'class' => 'text-ds-green'],
        ];
        $donutOffset = 0;
    @endphp

    {{-- Header --}}
    <section class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div class="space-y-1">
            <span class="text-ds-blue text-xs font-extrabold uppercase tracking-wider">{{ __('dashboard.inspector.title') }}</span>
            <h2 class="text-2xl font-extrabold text-ds-ink tracking-tight">{{ __('dashboard.inspector.title') }}</h2>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
            <a href="{{ route('jobs.inspector') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md min-h-[44px]">
                <span>{{ __('dashboard.inspector.view_available_jobs') }}</span>
                <i aria-hidden="true" class="fa-solid fa-arrow-right text-xs"></i>
            </a>
            <a href="{{ route('inspector.earnings') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-sm rounded-xl transition-all shadow-xs min-h-[44px]">
                {{ __('dashboard.inspector.view_earnings') }}
            </a>
        </div>
    </section>

    {{-- Ringkasan + Donut --}}
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        <div class="lg:col-span-2 bg-white rounded-xl border border-ds-line shadow-sm p-6">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-medium text-ds-faint">{{ __('dashboard.inspector.earnings_this_month') }}</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-ds-ink tracking-tight">{{ \App\Support\Money::format($earningsThisMonth) }}</p>
                </div>
                <div class="text-right">
                    <p class="text-2xl sm:text-3xl font-extrabold text-ds-ink tracking-tight">{{ $completedThisMonth }}</p>
                    <p class="text-xs font-medium text-ds-faint">{{ __('dashboard.inspector.completed_this_month') }}</p>
                </div>
            </div>

            <div class="mt-4">
                <svg viewBox="0 0 {{ $chartW }} {{ $chartH }}" class="w-full h-40 text-ds-blue" role="img" aria-label="{{ __('dashboard.inspector.trend_title') }}">
                    <path d="{{ $areaPath }}" fill="currentColor" opacity="0.12" />
                    <path d="{{ $linePath }}" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                    @foreach ($chartPts as $i => $p)
                        <circle cx="{{ $p[0] }}" cy="{{ $p[1] }}" r="4.5" fill="currentColor" stroke="#ffffff" stroke-width="2" />
                    @endforeach
                </svg>
                <div class="flex justify-between mt-1">
                    @foreach ($monthlySeries as $point)
                        <span class="text-[10px] font-bold text-ds-faint uppercase">{{ $point['label'] }}</span>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6 pt-5 border-t border-ds-line/60">
                <div class="flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-full bg-ds-blue-soft text-ds-blue-ink flex items-center justify-center text-sm shrink-0">
                        <i aria-hidden="true" class="fa-solid fa-briefcase"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-extrabold text-ds-ink">{{ $availableJobs }}</p>
                        <p class="text-[11px] font-medium text-ds-faint truncate">{{ __('dashboard.inspector.available_jobs') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-full bg-ds-yellow-soft text-ds-amber-ink flex items-center justify-center text-sm shrink-0">
                        <i aria-hidden="true" class="fa-solid fa-bolt"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-extrabold text-ds-ink">{{ $activeJobs }}</p>
                        <p class="text-[11px] font-medium text-ds-faint truncate">{{ __('dashboard.inspector.active_jobs') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-full bg-ds-green-soft text-ds-green-ink flex items-center justify-center text-sm shrink-0">
                        <i aria-hidden="true" class="fa-solid fa-clipboard-check"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-extrabold text-ds-ink">{{ $totalInspections }}</p>
                        <p class="text-[11px] font-medium text-ds-faint truncate">{{ __('dashboard.inspector.total_inspections') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-full bg-ds-amber-soft text-ds-amber-ink flex items-center justify-center text-sm shrink-0">
                        <i aria-hidden="true" class="fa-solid fa-star"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-extrabold text-ds-ink">{{ $ratingCount > 0 ? number_format($rating, 1) : '-' }}</p>
                        <p class="text-[11px] font-medium text-ds-faint truncate">{{ __('dashboard.inspector.rating') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-ds-line shadow-sm p-6 flex flex-col">
            <h3 class="text-base font-extrabold text-ds-ink">{{ __('dashboard.inspector.composition_title') }}</h3>
            <p class="text-xs text-ds-muted mt-0.5 mb-4">{{ __('dashboard.inspector.composition_subtitle') }}</p>

            <div class="flex justify-center">
                <svg viewBox="0 0 140 140" class="w-44 h-44" role="img" aria-label="{{ __('dashboard.inspector.composition_title') }}">
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

    {{-- Kartu Banner Gradien --}}
    <section class="grid grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-6">
        <div class="rounded-xl bg-gradient-to-br from-ds-blue to-ds-indigo text-white p-5 shadow-sm">
            <div class="w-11 h-11 rounded-xl bg-white/20 flex items-center justify-center text-lg">
                <i aria-hidden="true" class="fa-solid fa-briefcase"></i>
            </div>
            <p class="mt-4 text-3xl font-extrabold tracking-tight">{{ $availableJobs }}</p>
            <p class="text-xs font-semibold text-white/80 mt-1">{{ __('dashboard.inspector.available_jobs') }}</p>
        </div>

        <div class="rounded-xl bg-gradient-to-br from-ds-yellow-deep to-ds-yellow text-ds-ink p-5 shadow-sm">
            <div class="w-11 h-11 rounded-xl bg-ds-ink/10 flex items-center justify-center text-lg">
                <i aria-hidden="true" class="fa-solid fa-bolt"></i>
            </div>
            <p class="mt-4 text-3xl font-extrabold tracking-tight">{{ $activeJobs }}</p>
            <p class="text-xs font-semibold text-ds-ink/70 mt-1">{{ __('dashboard.inspector.active_jobs') }}</p>
        </div>

        <div class="rounded-xl bg-gradient-to-br from-ds-ink to-ds-muted text-white p-5 shadow-sm">
            <div class="w-11 h-11 rounded-xl bg-white/20 flex items-center justify-center text-lg">
                <i aria-hidden="true" class="fa-solid fa-clipboard-check"></i>
            </div>
            <p class="mt-4 text-3xl font-extrabold tracking-tight">{{ $totalInspections }}</p>
            <p class="text-xs font-semibold text-white/80 mt-1">{{ __('dashboard.inspector.total_inspections') }}</p>
        </div>

        <div class="rounded-xl bg-gradient-to-br from-ds-green to-ds-blue text-white p-5 shadow-sm">
            <div class="w-11 h-11 rounded-xl bg-white/20 flex items-center justify-center text-lg">
                <i aria-hidden="true" class="fa-solid fa-wallet"></i>
            </div>
            <p class="mt-4 text-xl sm:text-2xl font-extrabold tracking-tight">{{ \App\Support\Money::format($netEarnings) }}</p>
            <p class="text-xs font-semibold text-white/80 mt-1">{{ __('dashboard.inspector.net_earnings') }}</p>
        </div>
    </section>

    {{-- Aktivitas + Tabel Pekerjaan --}}
    <section class="grid grid-cols-1 lg:grid-cols-5 gap-4 sm:gap-6">
        <div class="lg:col-span-2 bg-white rounded-xl border border-ds-line shadow-sm p-6">
            <h3 class="text-base font-extrabold text-ds-ink mb-5">{{ __('dashboard.inspector.recent_activity_title') }}</h3>

            @if ($recentJobs->isEmpty())
                <p class="text-xs text-ds-muted leading-relaxed">{{ __('dashboard.inspector.no_activity') }}</p>
            @else
                <ul class="relative space-y-6 before:absolute before:left-[15px] before:top-2 before:bottom-2 before:w-px before:bg-ds-line">
                    @foreach ($recentJobs as $job)
                        <li class="relative flex items-start gap-3 pl-0">
                            <span class="relative z-10 w-8 h-8 rounded-full {{ $statusPill($job->status->color()) }} flex items-center justify-center text-xs shrink-0 shadow-sm">
                                <i aria-hidden="true" class="fa-solid fa-circle text-[6px]"></i>
                            </span>
                            <div class="min-w-0 pt-0.5">
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

        <div class="lg:col-span-3 bg-white rounded-xl border border-ds-line shadow-sm overflow-hidden">
            <div class="flex items-center justify-between p-6 pb-4">
                <div>
                    <h3 class="text-base font-extrabold text-ds-ink">{{ __('dashboard.inspector.recent_jobs_title') }}</h3>
                    <p class="text-xs text-ds-muted mt-0.5">{{ __('dashboard.inspector.recent_jobs_subtitle') }}</p>
                </div>
                <a href="{{ route('jobs.inspector') }}" class="text-xs font-bold text-ds-blue hover:text-ds-blue-ink shrink-0 ml-4">
                    {{ __('dashboard.inspector.view_all_jobs') }}
                </a>
            </div>

            @if ($recentJobs->isEmpty())
                <p class="text-xs text-ds-muted leading-relaxed px-6 pb-6">{{ __('dashboard.inspector.no_activity') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-xs min-w-[520px]">
                        <thead>
                            <tr class="bg-ds-ink text-white text-[10px] font-bold uppercase tracking-wider text-left">
                                <th scope="col" class="py-3 pl-6 pr-4">{{ __('dashboard.inspector.table_listing') }}</th>
                                <th scope="col" class="py-3 pr-4">{{ __('dashboard.inspector.table_requester') }}</th>
                                <th scope="col" class="py-3 pr-4">{{ __('dashboard.inspector.table_updated') }}</th>
                                <th scope="col" class="py-3 pr-6 text-right">{{ __('dashboard.inspector.table_status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ds-line/60">
                            @foreach ($recentJobs as $job)
                                <tr class="transition-colors hover:bg-ds-canvas/60">
                                    <td class="py-3 pl-6 pr-4 font-bold text-ds-ink max-w-44 truncate">{{ $job->listing?->title ?? '—' }}</td>
                                    <td class="py-3 pr-4 font-semibold text-ds-muted">{{ $job->requester?->name ?? '—' }}</td>
                                    <td class="py-3 pr-4 text-ds-faint whitespace-nowrap">{{ $job->updated_at->locale('id')->diffForHumans() }}</td>
                                    <td class="py-3 pr-6 text-right">
                                        <span class="inline-block text-[10px] px-2.5 py-1 rounded-full font-bold uppercase tracking-wider whitespace-nowrap {{ $statusPill($job->status->color()) }}">
                                            {{ $job->status->label() }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="px-6 py-3 text-[11px] text-ds-faint border-t border-ds-line/60">
                    {{ __('dashboard.inspector.showing_recent', ['count' => $recentJobs->count()]) }}
                </p>
            @endif
        </div>
    </section>

    {{-- Empty State --}}
    @if ($availableJobs === 0 && $activeJobs === 0 && $totalInspections === 0)
        <div class="bg-white rounded-xl border border-ds-line shadow-sm p-8 text-center max-w-2xl mx-auto">
            <div class="w-14 h-14 mx-auto rounded-xl bg-ds-soft text-ds-muted flex items-center justify-center text-xl mb-4">
                <i aria-hidden="true" class="fa-solid fa-briefcase"></i>
            </div>
            <h3 class="text-lg font-extrabold text-ds-ink">{{ __('dashboard.inspector.empty_state.title') }}</h3>
            <p class="text-xs text-ds-muted mt-2 leading-relaxed">{{ __('dashboard.inspector.empty_state.message') }}</p>
            <div class="flex justify-center mt-6">
                <a href="{{ route('jobs.inspector') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md min-h-[44px]">
                    <span>{{ __('dashboard.inspector.empty_state.browse_jobs') }}</span>
                    <i aria-hidden="true" class="fa-solid fa-arrow-right text-xs"></i>
                </a>
            </div>
        </div>
    @endif
</div>
