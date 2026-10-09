<div class="space-y-6">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Kolom Kiri: Kartu 1, 2, 3 --}}
        <div class="space-y-6">
            {{-- Kartu 1: Kartu Utama --}}
            <div class="bg-forest text-paper rounded-2xl p-5 sm:p-6 space-y-6">
                {{-- Baris atas: Avatar + Halo, {nama} + Pemilih Bulan --}}
                <div class="flex items-center justify-between gap-3 flex-wrap sm:flex-nowrap">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-11 h-11 rounded-full bg-paper/15 text-paper border border-paper/20 flex items-center justify-center font-bold text-base shrink-0 select-none">
                            {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-lg font-bold text-paper truncate">
                                {{ __('dashboard.inspector.greeting', ['name' => $user->name]) }}
                            </h2>
                        </div>
                    </div>

                    {{-- Pemilih Bulan --}}
                    <div class="flex items-center gap-1 bg-paper/10 rounded-full p-1 border border-paper/15">
                        <button
                            type="button"
                            wire:click="previousMonth"
                            class="w-11 h-11 flex items-center justify-center rounded-full text-paper hover:bg-paper/20 transition-colors cursor-pointer"
                            aria-label="{{ __('dashboard.inspector.prev_month') }}"
                        >
                            <x-ui.icon name="chevron-left" class="w-4 h-4" />
                        </button>
                        <span class="px-2 text-xs font-bold text-paper whitespace-nowrap min-w-[72px] text-center">
                            {{ $selectedMonth->locale('id')->shortMonthName }} {{ $selectedMonth->year }}
                        </span>
                        <button
                            type="button"
                            wire:click="nextMonth"
                            @disabled(! $canGoNext)
                            class="w-11 h-11 flex items-center justify-center rounded-full text-paper transition-colors {{ $canGoNext ? 'hover:bg-paper/20 cursor-pointer' : 'opacity-30 cursor-not-allowed pointer-events-none' }}"
                            aria-label="{{ __('dashboard.inspector.next_month') }}"
                        >
                            <x-ui.icon name="chevron-right" class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                {{-- Angka Besar Penghasilan + Label + Chip Perubahan --}}
                <div class="space-y-1">
                    <div class="flex items-baseline gap-2 flex-wrap">
                        <p class="text-xs font-medium text-paper/80 uppercase tracking-wider">
                            {{ __('dashboard.inspector.month_earnings') }}
                        </p>
                        @if ($summary->changePercent !== null)
                            @php
                                $isUp = $summary->changePercent >= 0;
                            @endphp
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold {{ $isUp ? 'bg-lime/20 text-lime border border-lime/30' : 'bg-alarm/20 text-alarm border border-alarm/30' }}">
                                <span>{{ ($isUp ? '+' : '') . $summary->changePercent }}%</span>
                            </span>
                        @endif
                    </div>
                    <div class="pt-1">
                        <x-ui.money :amount="$summary->netEarnings" class="!text-paper text-3xl sm:text-4xl font-extrabold tracking-tight" />
                    </div>
                </div>

                {{-- Baris Rating --}}
                <div class="pt-3 border-t border-paper/15 flex items-center gap-2 text-xs">
                    @if ($summary->ratingCount > 0)
                        <div class="flex items-center gap-1.5 text-lime">
                            <svg class="w-4 h-4 fill-lime" viewBox="0 0 20 20" aria-hidden="true">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <span class="font-extrabold text-paper">{{ number_format($summary->ratingAvg, 2) }}</span>
                        </div>
                        <span class="text-paper/60">·</span>
                        <span class="text-paper/80 font-medium">
                            {{ __('dashboard.inspector.review_count', ['count' => $summary->ratingCount]) }}
                        </span>
                    @else
                        <span class="text-paper/70 font-medium">
                            {{ __('dashboard.inspector.no_reviews') }}
                        </span>
                    @endif
                </div>
            </div>

            {{-- Kartu 2: Kartu Angka (Scroll horizontal di mobile) --}}
            <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-none snap-x snap-mandatory -mx-4 px-4 sm:mx-0 sm:px-0">
                <x-ui.stat-card
                    :value="$summary->availableJobs"
                    :label="__('dashboard.inspector.stats_available')"
                    :href="route('jobs.open')"
                    class="snap-start shrink-0 flex-1 min-w-[130px]"
                />
                <x-ui.stat-card
                    :value="$summary->activeJobs"
                    :label="__('dashboard.inspector.stats_active')"
                    :href="route('jobs.inspector')"
                    class="snap-start shrink-0 flex-1 min-w-[130px]"
                />
                <x-ui.stat-card
                    :value="$summary->completedInMonth"
                    :label="__('dashboard.inspector.stats_completed_month')"
                    class="snap-start shrink-0 flex-1 min-w-[130px]"
                />
                <x-ui.stat-card
                    :value="$summary->totalCompleted"
                    :label="__('dashboard.inspector.stats_total_completed')"
                    class="snap-start shrink-0 flex-1 min-w-[130px]"
                />
            </div>

            {{-- Kartu 3: Perlu Kamu Kerjakan --}}
            <x-ui.dash-card :title="__('dashboard.inspector.tasks_title')">
                @if (empty($tasks))
                    <x-ui.empty-state
                        icon="dokumen"
                        :title="__('dashboard.inspector.tasks_empty_title')"
                        :description="__('dashboard.inspector.tasks_empty_desc')"
                        :action-label="__('dashboard.inspector.tasks_empty_action')"
                        :action-url="route('jobs.open')"
                    />
                @else
                    <ul class="divide-y divide-fog -my-2">
                        @foreach ($tasks as $task)
                            @php
                                $isFill = $task->actionRoute === 'result.fill';
                                $actionLabel = $isFill
                                    ? __('dashboard.inspector.action_fill_result')
                                    : __('dashboard.inspector.action_view_job');
                            @endphp
                            <li class="py-4 first:pt-2 last:pb-2 space-y-3">
                                <div class="flex items-start justify-between gap-3 flex-wrap sm:flex-nowrap">
                                    <div class="space-y-1 min-w-0">
                                        <h4 class="text-sm font-bold text-obs truncate">
                                            {{ $task->listingTitle !== '' ? $task->listingTitle : '—' }}
                                        </h4>
                                        <div class="flex items-center gap-2 text-xs text-slate flex-wrap">
                                            @if ($task->city)
                                                <span>{{ $task->city }}</span>
                                                <span>·</span>
                                            @endif
                                            <span>
                                                {{ $task->scheduledAt !== null ? $task->scheduledAt->locale('id')->translatedFormat('j M Y, H.i') . ' WIB' : __('dashboard.inspector.not_scheduled') }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="shrink-0">
                                        <x-ui.status-chip
                                            :label="$task->status->label()"
                                            :variant="$task->status->color()"
                                            :icon="$task->status->icon()"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <x-ui.button
                                        :href="route($task->actionRoute, $task->actionParams)"
                                        :variant="$isFill ? 'primary' : 'secondary'"
                                        class="w-full"
                                    >
                                        {{ $actionLabel }}
                                    </x-ui.button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.dash-card>
        </div>

        {{-- Kolom Kanan: Kartu 4, 5 --}}
        <div class="space-y-6">
            {{-- Kartu 4: Tren Selesai --}}
            @php
                $maxTrend = max(1, max(array_column($trendSeries, 'completed')));
                $padLeft = 24;
                $usableW = 272; // 320 - 48
                $chartTop = 22;
                $chartH = 80;
                $chartBottom = $chartTop + $chartH; // 102

                $points = [];
                $pointsStr = '';

                $count = count($trendSeries);
                foreach ($trendSeries as $idx => $point) {
                    $x = $count > 1 ? $padLeft + ($idx * ($usableW / ($count - 1))) : $padLeft;
                    $ratio = $point['completed'] / $maxTrend;
                    $y = round($chartBottom - ($ratio * $chartH), 1);
                    $points[] = [
                        'x' => $x,
                        'y' => $y,
                        'val' => $point['completed'],
                        'label' => $point['label'],
                        'ym' => $point['ym'],
                        'isSelected' => ($point['ym'] === $selectedMonth->format('Y-m')),
                    ];
                    $pointsStr .= ($idx === 0 ? '' : ' ') . "{$x},{$y}";
                }

                $firstX = $points[0]['x'] ?? $padLeft;
                $lastX = $points[count($points) - 1]['x'] ?? ($padLeft + $usableW);
                $areaPoints = "{$firstX},{$chartBottom} " . $pointsStr . " {$lastX},{$chartBottom}";

                $ariaTrend = __('dashboard.inspector.trend_aria', [
                    'start' => $trendSeries[0]['label'] ?? '',
                    'end' => $trendSeries[count($trendSeries) - 1]['label'] ?? '',
                ]);
            @endphp

            <x-ui.dash-card
                :title="__('dashboard.inspector.trend_title')"
                :subtitle="__('dashboard.inspector.trend_subtitle')"
            >
                <div class="h-44 sm:h-48 w-full flex items-center justify-center">
                    <svg
                        viewBox="0 0 320 130"
                        class="w-full h-full overflow-visible"
                        role="img"
                        aria-label="{{ $ariaTrend }}"
                    >
                        {{-- Garis panduan horizontal --}}
                        <line x1="20" y1="{{ $chartBottom }}" x2="300" y2="{{ $chartBottom }}" stroke="currentColor" class="text-fog" stroke-width="1" />
                        <line x1="20" y1="{{ $chartTop + ($chartH / 2) }}" x2="300" y2="{{ $chartTop + ($chartH / 2) }}" stroke="currentColor" class="text-fog" stroke-dasharray="3 3" stroke-width="1" />
                        <line x1="20" y1="{{ $chartTop }}" x2="300" y2="{{ $chartTop }}" stroke="currentColor" class="text-fog" stroke-dasharray="3 3" stroke-width="1" />

                        {{-- Area di bawah kurva --}}
                        <polygon points="{{ $areaPoints }}" class="fill-linen/50 text-linen" />

                        {{-- Garis tren --}}
                        <polyline
                            fill="none"
                            stroke="currentColor"
                            class="text-forest"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            points="{{ $pointsStr }}"
                        />

                        {{-- Titik data & Label --}}
                        @foreach ($points as $p)
                            {{-- Angka di atas titik --}}
                            <text x="{{ $p['x'] }}" y="{{ max(14, $p['y'] - 8) }}" text-anchor="middle" class="text-[10px] font-bold fill-forest">
                                {{ $p['val'] }}
                            </text>

                            {{-- Titik --}}
                            @if ($p['isSelected'])
                                <circle cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="6" class="fill-lime text-lime" />
                                <circle cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="3" class="fill-forest text-forest" />
                            @else
                                <circle cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="3" class="fill-forest text-forest" />
                            @endif

                            {{-- Label bulan di bawah --}}
                            <text x="{{ $p['x'] }}" y="122" text-anchor="middle" class="text-[11px] font-medium fill-slate">
                                {{ $p['label'] }}
                            </text>
                        @endforeach
                    </svg>
                </div>
            </x-ui.dash-card>

            {{-- Kartu 5: Sumber Penghasilan --}}
            @php
                $donutCirc = 2 * pi() * 50;
                $donutOffset = 0;
                $totalBreakdownAmount = 0;
                foreach ($earningsBreakdown as $slice) {
                    $totalBreakdownAmount += $slice->amount;
                }
            @endphp

            <x-ui.dash-card
                :title="__('dashboard.inspector.earnings_sources_title')"
                :subtitle="__('dashboard.inspector.earnings_sources_subtitle')"
            >
                @if (empty($earningsBreakdown) || $totalBreakdownAmount <= 0)
                    <div class="py-8 text-center">
                        <p class="text-sm font-medium text-slate">
                            {{ __('dashboard.inspector.no_earnings_this_month') }}
                        </p>
                    </div>
                @else
                    {{-- Donat SVG --}}
                    <div class="relative w-36 h-36 mx-auto flex items-center justify-center">
                        <svg viewBox="0 0 140 140" class="w-full h-full -rotate-90" role="img" aria-label="{{ __('dashboard.inspector.earnings_sources_title') }}">
                            <circle cx="70" cy="70" r="50" fill="none" class="text-fog" stroke="currentColor" stroke-width="14" />
                            @foreach ($earningsBreakdown as $slice)
                                @php
                                    $frac = $slice->percent / 100;
                                    $dash = round($frac * $donutCirc, 1) . ' ' . round($donutCirc - ($frac * $donutCirc), 1);
                                    $off = round(-$donutOffset * $donutCirc, 1);
                                    $donutOffset += $frac;

                                    $sliceColor = match ($slice->type) {
                                        \App\Domains\Payment\Enums\LedgerEntryType::InspectionFee => 'text-forest',
                                        \App\Domains\Payment\Enums\LedgerEntryType::AccessFee => 'text-lime',
                                        \App\Domains\Payment\Enums\LedgerEntryType::SaleCommission => 'text-signal',
                                        default => 'text-pebble',
                                    };
                                @endphp
                                @if ($slice->percent > 0)
                                    <circle
                                        cx="70"
                                        cy="70"
                                        r="50"
                                        fill="none"
                                        class="{{ $sliceColor }}"
                                        stroke="currentColor"
                                        stroke-width="14"
                                        stroke-dasharray="{{ $dash }}"
                                        stroke-dashoffset="{{ $off }}"
                                        stroke-linecap="butt"
                                    />
                                @endif
                            @endforeach
                        </svg>
                        {{-- Total di tengah --}}
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center pointer-events-none px-2">
                            <span class="text-[10px] font-medium text-slate uppercase tracking-wider">Total</span>
                            <x-ui.money :amount="$totalBreakdownAmount" class="text-xs sm:text-sm font-bold text-obs tracking-tight" />
                        </div>
                    </div>

                    {{-- Daftar Berbar --}}
                    <ul class="mt-6 space-y-4">
                        @foreach ($earningsBreakdown as $slice)
                            @php
                                $sliceLabel = match ($slice->type) {
                                    \App\Domains\Payment\Enums\LedgerEntryType::InspectionFee => __('dashboard.inspector.earnings_sources.inspection_fee'),
                                    \App\Domains\Payment\Enums\LedgerEntryType::AccessFee => __('dashboard.inspector.earnings_sources.access_fee'),
                                    \App\Domains\Payment\Enums\LedgerEntryType::SaleCommission => __('dashboard.inspector.earnings_sources.sale_commission'),
                                    default => $slice->type->label(),
                                };
                                $sliceBarClass = match ($slice->type) {
                                    \App\Domains\Payment\Enums\LedgerEntryType::InspectionFee => 'fill-forest text-forest',
                                    \App\Domains\Payment\Enums\LedgerEntryType::AccessFee => 'fill-lime text-lime',
                                    \App\Domains\Payment\Enums\LedgerEntryType::SaleCommission => 'fill-signal text-signal',
                                    default => 'fill-pebble text-pebble',
                                };
                                $dotClass = match ($slice->type) {
                                    \App\Domains\Payment\Enums\LedgerEntryType::InspectionFee => 'bg-forest',
                                    \App\Domains\Payment\Enums\LedgerEntryType::AccessFee => 'bg-lime',
                                    \App\Domains\Payment\Enums\LedgerEntryType::SaleCommission => 'bg-signal',
                                    default => 'bg-pebble',
                                };
                            @endphp
                            <li class="space-y-1.5">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="flex items-center gap-2 font-medium text-obs">
                                        <span class="w-2.5 h-2.5 rounded-full {{ $dotClass }} shrink-0"></span>
                                        {{ $sliceLabel }}
                                    </span>
                                    <span class="font-bold text-obs whitespace-nowrap">
                                        {{ round($slice->percent) }}% · <x-ui.money :amount="$slice->amount" class="text-xs font-bold text-obs" />
                                    </span>
                                </div>
                                <div class="h-2 w-full bg-fog rounded-full overflow-hidden">
                                    <svg class="w-full h-full" viewBox="0 0 100 8" preserveAspectRatio="none">
                                        <rect x="0" y="0" width="{{ round($slice->percent) }}" height="8" rx="4" class="{{ $sliceBarClass }}" />
                                    </svg>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.dash-card>
        </div>
    </div>

    {{-- Kartu 6: Pekerjaan Terbaru (Selebar penuh di bawah) --}}
    <x-ui.dash-card
        :title="__('dashboard.inspector.recent_jobs_title')"
        :subtitle="__('dashboard.inspector.recent_jobs_subtitle')"
    >
        <x-slot:action>
            <a
                href="{{ route('jobs.inspector') }}"
                class="text-xs font-bold text-forest hover:text-spruce transition-colors"
            >
                {{ __('dashboard.inspector.view_all_jobs') }}
            </a>
        </x-slot:action>

        @if ($recentJobs->isEmpty())
            <div class="py-8 text-center">
                <p class="text-sm font-medium text-slate">
                    {{ __('dashboard.inspector.no_activity') }}
                </p>
            </div>
        @else
            <ul class="divide-y divide-fog -my-2">
                @foreach ($recentJobs as $job)
                    <li class="py-3.5 first:pt-2 last:pb-2">
                        <a
                            href="{{ route('jobs.show', $job) }}"
                            class="flex items-center justify-between gap-4 p-2 -m-2 rounded-[10px] hover:bg-fog/30 transition-colors group cursor-pointer"
                        >
                            <div class="min-w-0 space-y-1">
                                <p class="text-sm font-bold text-obs truncate group-hover:text-forest transition-colors">
                                    {{ $job->listing?->title ?? '—' }}
                                </p>
                                <p class="text-xs text-slate">
                                    {{ $job->requester?->name ?? '—' }} · {{ $job->updated_at->locale('id')->diffForHumans() }}
                                </p>
                            </div>
                            <div class="flex items-center gap-3 shrink-0">
                                <x-ui.status-chip
                                    :label="$job->status->label()"
                                    :variant="$job->status->color()"
                                    :icon="$job->status->icon()"
                                />
                                <x-ui.icon name="chevron-right" class="w-4 h-4 text-pebble group-hover:text-forest transition-colors" />
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.dash-card>
</div>
