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

    {{-- Kartu Statistik Gradien --}}
    <section class="grid grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-6">
        <div class="rounded-2xl bg-gradient-to-br from-ds-blue to-ds-indigo text-white p-5 shadow-lg shadow-ds-blue/20">
            <div class="flex items-center justify-between">
                <div class="w-11 h-11 rounded-xl bg-white/20 flex items-center justify-center text-lg">
                    <i aria-hidden="true" class="fa-solid fa-briefcase"></i>
                </div>
            </div>
            <p class="mt-4 text-3xl font-extrabold tracking-tight">{{ $availableJobs }}</p>
            <p class="text-xs font-semibold text-white/80 mt-1">{{ __('dashboard.inspector.available_jobs') }}</p>
        </div>

        <div class="rounded-2xl bg-gradient-to-br from-ds-yellow-deep to-ds-yellow text-ds-ink p-5 shadow-lg shadow-ds-yellow/20">
            <div class="flex items-center justify-between">
                <div class="w-11 h-11 rounded-xl bg-ds-ink/10 flex items-center justify-center text-lg">
                    <i aria-hidden="true" class="fa-solid fa-bolt"></i>
                </div>
            </div>
            <p class="mt-4 text-3xl font-extrabold tracking-tight">{{ $activeJobs }}</p>
            <p class="text-xs font-semibold text-ds-ink/70 mt-1">{{ __('dashboard.inspector.active_jobs') }}</p>
        </div>

        <div class="rounded-2xl bg-gradient-to-br from-ds-ink to-ds-muted text-white p-5 shadow-lg shadow-ds-ink/20">
            <div class="flex items-center justify-between">
                <div class="w-11 h-11 rounded-xl bg-white/20 flex items-center justify-center text-lg">
                    <i aria-hidden="true" class="fa-solid fa-clipboard-check"></i>
                </div>
            </div>
            <p class="mt-4 text-3xl font-extrabold tracking-tight">{{ $totalInspections }}</p>
            <p class="text-xs font-semibold text-white/80 mt-1">{{ __('dashboard.inspector.total_inspections') }}</p>
        </div>

        <div class="rounded-2xl bg-gradient-to-br from-ds-green to-ds-blue text-white p-5 shadow-lg shadow-ds-green/20">
            <div class="flex items-center justify-between">
                <div class="w-11 h-11 rounded-xl bg-white/20 flex items-center justify-center text-lg">
                    <i aria-hidden="true" class="fa-solid fa-wallet"></i>
                </div>
            </div>
            <p class="mt-4 text-xl sm:text-2xl font-extrabold tracking-tight">{{ \App\Support\Money::format($netEarnings) }}</p>
            <p class="text-xs font-semibold text-white/80 mt-1">{{ __('dashboard.inspector.net_earnings') }}</p>
        </div>
    </section>

    {{-- Tren + Rating --}}
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        <div class="lg:col-span-2 bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-base font-extrabold text-ds-ink">{{ __('dashboard.inspector.trend_title') }}</h3>
                    <p class="text-xs text-ds-muted mt-0.5">{{ __('dashboard.inspector.trend_subtitle') }}</p>
                </div>
            </div>

            <div class="flex items-end justify-between gap-2 h-36" role="img" aria-label="{{ __('dashboard.inspector.trend_title') }}">
                @foreach ($monthlySeries as $point)
                    <div class="flex-1 flex flex-col items-center gap-2 h-full justify-end">
                        <div class="w-full max-w-10 rounded-lg bg-gradient-to-t from-ds-blue to-ds-green/70 transition-all {{ $barClass($point['value'], $maxTrend) }}"></div>
                        <span class="text-[10px] font-bold text-ds-faint uppercase">{{ $point['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-2xl bg-gradient-to-br from-ds-yellow to-ds-yellow-deep text-ds-ink p-6 shadow-lg shadow-ds-yellow/20 flex flex-col justify-between">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-ds-ink/10 flex items-center justify-center text-lg">
                    <i aria-hidden="true" class="fa-solid fa-star"></i>
                </div>
                <p class="text-xs font-bold uppercase tracking-wider text-ds-ink/70">{{ __('dashboard.inspector.rating') }}</p>
            </div>
            <p class="mt-6 text-5xl font-extrabold tracking-tight">
                {{ $ratingCount > 0 ? number_format($rating, 1) : '-' }}
            </p>
            <p class="text-xs font-semibold text-ds-ink/70 mt-2">
                {{ $ratingCount > 0 ? __('rating.display.rating_count', ['count' => $ratingCount]) : __('rating.display.no_ratings') }}
            </p>
        </div>
    </section>

    {{-- Aktivitas + Tabel Pekerjaan --}}
    <section class="grid grid-cols-1 lg:grid-cols-5 gap-4 sm:gap-6">
        <div class="lg:col-span-2 bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
            <h3 class="text-base font-extrabold text-ds-ink mb-5">{{ __('dashboard.inspector.recent_activity_title') }}</h3>

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

        <div class="lg:col-span-3 bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6 overflow-hidden">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-base font-extrabold text-ds-ink">{{ __('dashboard.inspector.recent_jobs_title') }}</h3>
                    <p class="text-xs text-ds-muted mt-0.5">{{ __('dashboard.inspector.recent_jobs_subtitle') }}</p>
                </div>
                <a href="{{ route('jobs.inspector') }}" class="text-xs font-bold text-ds-blue hover:text-ds-blue-ink shrink-0 ml-4">
                    {{ __('dashboard.inspector.view_all_jobs') }}
                </a>
            </div>

            @if ($recentJobs->isEmpty())
                <p class="text-xs text-ds-muted leading-relaxed">{{ __('dashboard.inspector.no_activity') }}</p>
            @else
                <div class="overflow-x-auto -mx-6 px-6">
                    <table class="w-full text-sm min-w-[480px]">
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
                                <tr>
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
                <a href="{{ route('jobs.inspector') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md min-h-[44px]">
                    <span>{{ __('dashboard.inspector.empty_state.browse_jobs') }}</span>
                    <i aria-hidden="true" class="fa-solid fa-arrow-right text-xs"></i>
                </a>
            </div>
        </div>
    @endif
</div>
