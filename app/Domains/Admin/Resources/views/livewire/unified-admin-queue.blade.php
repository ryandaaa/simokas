<div class="max-w-7xl mx-auto py-6 space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-text">
                {{ __('admin.unified_queue.title') }}
            </h1>
            <p class="text-sm text-muted mt-1">
                {{ __('admin.unified_queue.subtitle') }}
            </p>
        </div>
    </div>

    {{-- Filter Tabs / Badges --}}
    <div class="flex flex-wrap gap-2">
        <button
            type="button"
            wire:click="$set('filterType', 'all')"
            @class([
                'inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-accent min-h-[44px]',
                'bg-primary text-surface shadow-sm' => $filterType === 'all',
                'bg-surface border border-muted/20 text-muted hover:text-text' => $filterType !== 'all',
            ])
        >
            <span>{{ __('admin.unified_queue.filter_all') }}</span>
            <span @class([
                'px-2 py-0.5 rounded-full text-xs font-bold',
                'bg-surface/20 text-surface' => $filterType === 'all',
                'bg-bg text-muted' => $filterType !== 'all',
            ])>
                {{ $totalCount }}
            </span>
        </button>

        <button
            type="button"
            wire:click="$set('filterType', 'listing')"
            @class([
                'inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-accent min-h-[44px]',
                'bg-primary text-surface shadow-sm' => $filterType === 'listing',
                'bg-surface border border-muted/20 text-muted hover:text-text' => $filterType !== 'listing',
            ])
        >
            <span>{{ __('admin.unified_queue.filter_listing') }}</span>
            <span @class([
                'px-2 py-0.5 rounded-full text-xs font-bold',
                'bg-surface/20 text-surface' => $filterType === 'listing',
                'bg-bg text-muted' => $filterType !== 'listing',
            ])>
                {{ $listingCount }}
            </span>
        </button>

        <button
            type="button"
            wire:click="$set('filterType', 'inspector')"
            @class([
                'inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-accent min-h-[44px]',
                'bg-primary text-surface shadow-sm' => $filterType === 'inspector',
                'bg-surface border border-muted/20 text-muted hover:text-text' => $filterType !== 'inspector',
            ])
        >
            <span>{{ __('admin.unified_queue.filter_inspector') }}</span>
            <span @class([
                'px-2 py-0.5 rounded-full text-xs font-bold',
                'bg-surface/20 text-surface' => $filterType === 'inspector',
                'bg-bg text-muted' => $filterType !== 'inspector',
            ])>
                {{ $inspectorCount }}
            </span>
        </button>

        <button
            type="button"
            wire:click="$set('filterType', 'job')"
            @class([
                'inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-accent min-h-[44px]',
                'bg-primary text-surface shadow-sm' => $filterType === 'job',
                'bg-surface border border-muted/20 text-muted hover:text-text' => $filterType !== 'job',
            ])
        >
            <span>{{ __('admin.unified_queue.filter_job') }}</span>
            <span @class([
                'px-2 py-0.5 rounded-full text-xs font-bold',
                'bg-surface/20 text-surface' => $filterType === 'job',
                'bg-bg text-muted' => $filterType !== 'job',
            ])>
                {{ $jobCount }}
            </span>
        </button>
    </div>

    {{-- Queue Items List --}}
    @if ($queueItems->isEmpty())
        <x-ui.card class="p-8">
            <x-ui.empty-state
                icon="centang"
                :title="__('admin.unified_queue.empty_title')"
                :description="__('admin.unified_queue.empty_description')"
            />
        </x-ui.card>
    @else
        {{-- Desktop Table --}}
        <div class="hidden md:block">
            <x-ui.card class="overflow-hidden">
                <table class="min-w-full divide-y divide-muted/20 text-left text-sm">
                    <thead class="bg-bg text-muted">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('admin.unified_queue.table_type') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('admin.unified_queue.table_item') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('admin.unified_queue.table_status') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('admin.unified_queue.table_waiting') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium text-right">{{ __('admin.unified_queue.table_action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-muted/20 bg-surface">
                        @foreach ($queueItems as $item)
                            <tr class="hover:bg-bg/50 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if ($item['type'] === 'listing')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary/10 text-primary">
                                            {{ __('admin.unified_queue.type_listing') }}
                                        </span>
                                    @elseif ($item['type'] === 'inspector')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-warning/10 text-warning">
                                            {{ __('admin.unified_queue.type_inspector') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-danger/10 text-danger">
                                            {{ __('admin.unified_queue.type_job') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-text">{{ $item['title'] }}</div>
                                    <div class="text-xs text-muted">{{ $item['subtitle'] }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <x-ui.status-chip
                                        :label="$item['status_label']"
                                        :variant="$item['status_variant']"
                                        :icon="$item['status_icon']"
                                    />
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-muted">
                                    <x-ui.datetime :value="$item['time']" />
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <x-ui.button
                                        :href="$item['url']"
                                        variant="secondary"
                                        class="text-xs py-1.5 px-3 min-h-[36px]"
                                    >
                                        @if ($item['type'] === 'listing')
                                            {{ __('admin.unified_queue.action_review_listing') }}
                                        @elseif ($item['type'] === 'inspector')
                                            {{ __('admin.unified_queue.action_review_inspector') }}
                                        @else
                                            {{ __('admin.unified_queue.action_review_job') }}
                                        @endif
                                    </x-ui.button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-ui.card>
        </div>

        {{-- Mobile Cards --}}
        <div class="md:hidden space-y-4">
            @foreach ($queueItems as $item)
                <x-ui.card class="p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        @if ($item['type'] === 'listing')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary/10 text-primary">
                                {{ __('admin.unified_queue.type_listing') }}
                            </span>
                        @elseif ($item['type'] === 'inspector')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-warning/10 text-warning">
                                {{ __('admin.unified_queue.type_inspector') }}
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-danger/10 text-danger">
                                {{ __('admin.unified_queue.type_job') }}
                            </span>
                        @endif

                        <x-ui.status-chip
                            :label="$item['status_label']"
                            :variant="$item['status_variant']"
                            :icon="$item['status_icon']"
                        />
                    </div>

                    <div>
                        <div class="text-sm font-semibold text-text">{{ $item['title'] }}</div>
                        <div class="text-xs text-muted mt-0.5">{{ $item['subtitle'] }}</div>
                    </div>

                    <div class="flex items-center justify-between text-xs text-muted pt-2 border-t border-muted/10">
                        <span>Menunggu sejak:</span>
                        <x-ui.datetime :value="$item['time']" />
                    </div>

                    <div class="pt-2">
                        <x-ui.button
                            :href="$item['url']"
                            variant="secondary"
                            class="w-full text-xs justify-center"
                        >
                            @if ($item['type'] === 'listing')
                                {{ __('admin.unified_queue.action_review_listing') }}
                            @elseif ($item['type'] === 'inspector')
                                {{ __('admin.unified_queue.action_review_inspector') }}
                            @else
                                {{ __('admin.unified_queue.action_review_job') }}
                            @endif
                        </x-ui.button>
                    </div>
                </x-ui.card>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="pt-4">
            {{ $queueItems->links() }}
        </div>
    @endif
</div>
