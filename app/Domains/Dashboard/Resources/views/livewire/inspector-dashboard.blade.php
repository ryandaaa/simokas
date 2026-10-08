<div class="space-y-10">
    <section>
        <div class="mb-6 space-y-1">
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
        $donutTotal = max(1, $availableJobs + $activeJobs + $totalInspections);
        $donutCirc = 2 * pi() * 54;
        $donutSegs = [
            ['label' => __('dashboard.inspector.available_jobs'), 'value' => $availableJobs, 'class' => 'text-ds-blue'],
            ['label' => __('dashboard.inspector.active_jobs'), 'value' => $activeJobs, 'class' => 'text-ds-yellow-deep'],
            ['label' => __('dashboard.inspector.total_inspections'), 'value' => $totalInspections, 'class' => 'text-ds-green'],
        ];
        $donutOffset = 0;
    @endphp

    {{-- Tren + Komposisi --}}
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
            <h3 class="text-lg font-extrabold text-ds-ink">{{ __('dashboard.inspector.trend_title') }}</h3>
            <p class="text-xs text-ds-muted mt-1 mb-6">{{ __('dashboard.inspector.trend_subtitle') }}</p>

            <div class="flex items-start justify-between gap-2 h-36" role="img" aria-label="{{ __('dashboard.inspector.trend_title') }}">
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
            <h3 class="text-lg font-extrabold text-ds-ink">{{ __('dashboard.inspector.composition_title') }}</h3>
            <p class="text-xs text-ds-muted mt-1 mb-4">{{ __('dashboard.inspector.composition_subtitle') }}</p>

            <div class="flex justify-center">
                <svg viewBox="0 0 140 140" class="w-32 h-32" role="img" aria-label="{{ __('dashboard.inspector.composition_title') }}">
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

    {{-- Aktivitas + Pekerjaan Terbaru --}}
    <section class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        <div class="lg:col-span-2 bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
            <h3 class="text-lg font-extrabold text-ds-ink mb-5">{{ __('dashboard.inspector.recent_activity_title') }}</h3>

            @if ($recentJobs->isEmpty())
                <p class="text-xs text-ds-muted leading-relaxed">{{ __('dashboard.inspector.no_activity') }}</p>
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
                    <h3 class="text-lg font-extrabold text-ds-ink">{{ __('dashboard.inspector.recent_jobs_title') }}</h3>
                    <p class="text-xs text-ds-muted mt-1">{{ __('dashboard.inspector.recent_jobs_subtitle') }}</p>
                </div>
                <a href="{{ route('jobs.inspector') }}" class="text-xs font-bold text-ds-blue hover:text-ds-blue-ink shrink-0 ml-4">
                    {{ __('dashboard.inspector.view_all_jobs') }}
                </a>
            </div>

            @if ($recentJobs->isEmpty())
                <p class="text-xs text-ds-muted leading-relaxed">{{ __('dashboard.inspector.no_activity') }}</p>
            @else
                <div class="overflow-x-auto -mx-6 px-6">
                    <table class="w-full text-sm min-w-[520px]">
                        <thead>
                            <tr class="text-left text-[10px] font-bold uppercase tracking-wider text-ds-faint border-b border-ds-line/60">
                                <th scope="col" class="py-2 pr-4">{{ __('dashboard.inspector.table_listing') }}</th>
                                <th scope="col" class="py-2 pr-4">{{ __('dashboard.inspector.table_requester') }}</th>
                                <th scope="col" class="py-2 pr-4">{{ __('dashboard.inspector.table_updated') }}</th>
                                <th scope="col" class="py-2 text-right">{{ __('dashboard.inspector.table_status') }}</th>
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
                    {{ __('dashboard.inspector.showing_recent', ['count' => $recentJobs->count()]) }}
                </p>
            @endif
        </div>
    </section>

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
