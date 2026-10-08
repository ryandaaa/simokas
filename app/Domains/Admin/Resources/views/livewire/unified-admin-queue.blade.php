<div class="ds-sans max-w-7xl mx-auto py-6 space-y-8 pb-12">
    {{-- Header --}}
    <div class="space-y-2">
        <span class="text-ds-blue text-xs font-extrabold uppercase tracking-wider">{{ __('admin.unified_queue.title') }}</span>
        <h1 class="text-3xl font-extrabold text-ds-ink tracking-tight">
            {{ __('admin.unified_queue.title') }}
        </h1>
        <p class="text-sm text-ds-muted">
            {{ __('admin.unified_queue.subtitle') }}
        </p>
    </div>

    {{-- Filter Tabs / Badges --}}
    <div class="flex flex-wrap gap-2">
        @foreach ([
            'all' => ['label' => __('admin.unified_queue.filter_all'), 'count' => $totalCount],
            'listing' => ['label' => __('admin.unified_queue.filter_listing'), 'count' => $listingCount],
            'inspector' => ['label' => __('admin.unified_queue.filter_inspector'), 'count' => $inspectorCount],
            'job' => ['label' => __('admin.unified_queue.filter_job'), 'count' => $jobCount],
        ] as $key => $tab)
            <button
                type="button"
                wire:click="$set('filterType', '{{ $key }}')"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-bold transition-colors focus:outline-none focus:ring-2 focus:ring-ds-blue min-h-[44px] {{ $filterType === $key ? 'bg-ds-ink text-white shadow-md' : 'bg-white border border-ds-line text-ds-muted hover:border-ds-blue hover:text-ds-blue shadow-xs' }}"
            >
                <span>{{ $tab['label'] }}</span>
                <span class="px-2 py-0.5 rounded-full text-[11px] font-extrabold {{ $filterType === $key ? 'bg-white/20 text-white' : 'bg-ds-canvas text-ds-muted' }}">
                    {{ $tab['count'] }}
                </span>
            </button>
        @endforeach
    </div>

    {{-- Alerts --}}
    @if (session('error'))
        <div role="alert" class="p-4 rounded-2xl border border-ds-red-soft bg-ds-red-soft/40 text-xs font-semibold text-ds-ink">
            {{ session('error') }}
        </div>
    @endif
    @if (session('success'))
        <div role="status" class="p-4 rounded-2xl border border-ds-green-soft bg-ds-green-soft/40 text-xs font-semibold text-ds-ink">
            {{ session('success') }}
        </div>
    @endif

    {{-- Loading indicator --}}
    <div wire:loading class="w-full">
        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6 flex items-center justify-center gap-3 text-sm text-ds-muted">
            <i aria-hidden="true" class="fa-solid fa-circle-notch fa-spin"></i>
            {{ __('ui.actions.loading') }}
        </div>
    </div>

    @php
        $typePill = function (string $type): string {
            return match ($type) {
                'listing' => 'bg-ds-blue-soft text-ds-blue-ink',
                'inspector' => 'bg-ds-amber-soft text-ds-amber-ink',
                default => 'bg-ds-red-soft text-ds-red-ink',
            };
        };
        $chip = function (?string $variant): string {
            return match ($variant) {
                'success' => 'bg-ds-green-soft text-ds-green-ink',
                'warning' => 'bg-ds-amber-soft text-ds-amber-ink',
                'danger' => 'bg-ds-red-soft text-ds-red-ink',
                'primary' => 'bg-ds-blue-soft text-ds-blue-ink',
                default => 'bg-ds-soft text-ds-muted',
            };
        };
    @endphp

    {{-- Queue Items List --}}
    <div wire:loading.remove class="space-y-6">
    @if ($queueItems->isEmpty())
        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-8 text-center max-w-2xl mx-auto">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-ds-green-soft text-ds-green-ink flex items-center justify-center text-xl mb-4">
                <i aria-hidden="true" class="fa-solid fa-check"></i>
            </div>
            <h3 class="text-lg font-extrabold text-ds-ink">{{ __('admin.unified_queue.empty_title') }}</h3>
            <p class="text-xs text-ds-muted mt-2">{{ __('admin.unified_queue.empty_description') }}</p>
        </div>
    @else
        {{-- Desktop Table --}}
        <div class="hidden md:block">
            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs overflow-hidden">
                <table class="min-w-full divide-y divide-ds-line/60 text-left text-sm">
                    <thead class="bg-ds-canvas/80 text-ds-muted">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-bold text-xs uppercase tracking-wider">{{ __('admin.unified_queue.table_type') }}</th>
                            <th scope="col" class="px-4 py-3 font-bold text-xs uppercase tracking-wider">{{ __('admin.unified_queue.table_item') }}</th>
                            <th scope="col" class="px-4 py-3 font-bold text-xs uppercase tracking-wider">{{ __('admin.unified_queue.table_status') }}</th>
                            <th scope="col" class="px-4 py-3 font-bold text-xs uppercase tracking-wider">{{ __('admin.unified_queue.table_waiting') }}</th>
                            <th scope="col" class="px-4 py-3 font-bold text-xs uppercase tracking-wider text-right">{{ __('admin.unified_queue.table_action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ds-line/60 bg-white">
                        @foreach ($queueItems as $item)
                            <tr class="hover:bg-ds-canvas/60 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if ($item['type'] === 'listing')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-ds-blue-soft text-ds-blue-ink">
                                            {{ __('admin.unified_queue.type_listing') }}
                                        </span>
                                    @elseif ($item['type'] === 'inspector')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-ds-amber-soft text-ds-amber-ink">
                                            {{ __('admin.unified_queue.type_inspector') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-ds-red-soft text-ds-red-ink">
                                            {{ __('admin.unified_queue.type_job') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-bold text-ds-ink">{{ $item['title'] }}</div>
                                    <div class="text-xs text-ds-muted">{{ $item['subtitle'] }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-[10px] px-2 py-0.5 rounded-md font-bold {{ $chip($item['status_variant']) }}">
                                        {{ $item['status_label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-ds-muted">
                                    {{ $item['time']?->translatedFormat('d M Y H:i') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <a href="{{ $item['url'] }}"
                                        class="inline-flex items-center justify-center px-4 py-1.5 min-h-[36px] text-xs font-bold bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink rounded-xl transition-all shadow-xs">
                                        @if ($item['type'] === 'listing')
                                            {{ __('admin.unified_queue.action_review_listing') }}
                                        @elseif ($item['type'] === 'inspector')
                                            {{ __('admin.unified_queue.action_review_inspector') }}
                                        @else
                                            {{ __('admin.unified_queue.action_review_job') }}
                                        @endif
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile Cards --}}
        <div class="md:hidden space-y-4">
            @foreach ($queueItems as $item)
                <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        @if ($item['type'] === 'listing')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-ds-blue-soft text-ds-blue-ink">
                                {{ __('admin.unified_queue.type_listing') }}
                            </span>
                        @elseif ($item['type'] === 'inspector')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-ds-amber-soft text-ds-amber-ink">
                                {{ __('admin.unified_queue.type_inspector') }}
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-ds-red-soft text-ds-red-ink">
                                {{ __('admin.unified_queue.type_job') }}
                            </span>
                        @endif

                        <span class="text-[10px] px-2 py-0.5 rounded-md font-bold {{ $chip($item['status_variant']) }}">
                            {{ $item['status_label'] }}
                        </span>
                    </div>

                    <div>
                        <div class="text-sm font-extrabold text-ds-ink">{{ $item['title'] }}</div>
                        <div class="text-xs text-ds-muted mt-0.5">{{ $item['subtitle'] }}</div>
                    </div>

                    <div class="flex items-center justify-between text-xs text-ds-muted pt-2 border-t border-ds-line/60">
                        <span>{{ __('admin.jobs.waiting_since_label') }}</span>
                        <span>{{ $item['time']?->translatedFormat('d M Y H:i') }}</span>
                    </div>

                    <div class="pt-2">
                        <a href="{{ $item['url'] }}"
                            class="w-full inline-flex items-center justify-center px-4 py-2.5 text-xs font-bold bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink rounded-xl transition-all shadow-xs">
                            @if ($item['type'] === 'listing')
                                {{ __('admin.unified_queue.action_review_listing') }}
                            @elseif ($item['type'] === 'inspector')
                                {{ __('admin.unified_queue.action_review_inspector') }}
                            @else
                                {{ __('admin.unified_queue.action_review_job') }}
                            @endif
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="pt-4">
            {{ $queueItems->links() }}
        </div>
    @endif
    </div>
</div>
