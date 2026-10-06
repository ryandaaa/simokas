<div class="max-w-7xl mx-auto py-6 space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-text">
                {{ __('admin.jobs.title') }}
            </h1>
            <p class="text-sm text-muted mt-1">
                {{ __('admin.jobs.subtitle') }}
            </p>
        </div>
    </div>

    {{-- Filter Bar --}}
    <x-ui.card class="p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-center">
            <div class="lg:col-span-2">
                <x-ui.input
                    id="search"
                    name="search"
                    wire:model.live.debounce.300ms="search"
                    :placeholder="__('admin.jobs.search_placeholder')"
                />
            </div>
            <div>
                <select
                    id="filterStatus"
                    wire:model.live="filterStatus"
                    class="w-full rounded-lg border border-muted/30 bg-surface px-3 py-2 text-sm text-text focus:outline-none focus:ring-2 focus:ring-accent"
                >
                    <option value="">{{ __('admin.jobs.filter_all_status') }}</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center">
                <label class="inline-flex items-center gap-2 cursor-pointer text-sm font-medium text-text">
                    <input
                        type="checkbox"
                        wire:model.live="filterAttentionOnly"
                        class="rounded border-muted/30 text-primary focus:ring-accent w-4 h-4"
                    />
                    <span>{{ __('admin.jobs.filter_attention_only') }}</span>
                </label>
            </div>
        </div>
    </x-ui.card>

    {{-- Job List --}}
    @if ($jobs->isEmpty())
        <x-ui.card class="p-8">
            <x-ui.empty-state
                icon="kaca-pembesar"
                :title="__('admin.jobs.empty_title')"
                :description="__('admin.jobs.empty_description')"
            />
        </x-ui.card>
    @else
        {{-- Desktop Table --}}
        <div class="hidden md:block">
            <x-ui.card class="overflow-hidden">
                <table class="min-w-full divide-y divide-muted/20 text-left text-sm">
                    <thead class="bg-bg text-muted">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('admin.jobs.table_vehicle') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('admin.jobs.table_requester') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('admin.jobs.table_inspector') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('admin.jobs.table_status') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('admin.jobs.table_created') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium text-right">{{ __('admin.jobs.action_detail') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-muted/20 bg-surface">
                        @foreach ($jobs as $job)
                            <tr class="hover:bg-bg/50 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-text">{{ $job->listing?->title ?? '-' }}</div>
                                    <div class="font-mono text-xs text-muted truncate max-w-xs">{{ $job->id }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-text">{{ $job->requester?->name ?? '-' }}</div>
                                    <div class="text-xs text-muted">{{ $job->requester?->email ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if ($job->inspector)
                                        <div class="text-text">{{ $job->inspector->name }}</div>
                                        <div class="text-xs text-muted">{{ $job->inspector->email }}</div>
                                    @else
                                        <span class="text-xs text-muted italic">{{ __('admin.jobs.no_inspector') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <x-ui.status-chip
                                            :label="$job->status->label()"
                                            :variant="$job->status->color()"
                                            :icon="$job->status->icon()"
                                        />
                                        @if ($job->needs_attention_at && !$job->isFinal())
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-danger/10 text-danger" title="Perlu Tindakan">
                                                !
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-muted">
                                    <x-ui.datetime :value="$job->created_at" />
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <x-ui.button
                                        :href="route('admin.jobs.show', $job->id)"
                                        variant="secondary"
                                        class="text-xs py-1.5 px-3 min-h-[36px]"
                                    >
                                        {{ __('admin.jobs.action_detail') }}
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
            @foreach ($jobs as $job)
                <x-ui.card class="p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <x-ui.status-chip
                            :label="$job->status->label()"
                            :variant="$job->status->color()"
                            :icon="$job->status->icon()"
                        />
                        @if ($job->needs_attention_at && !$job->isFinal())
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-danger/10 text-danger">
                                Perlu Tindakan
                            </span>
                        @endif
                    </div>

                    <div>
                        <div class="text-sm font-semibold text-text">{{ $job->listing?->title ?? '-' }}</div>
                        <div class="font-mono text-xs text-muted truncate mt-0.5">{{ $job->id }}</div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs pt-2 border-t border-muted/10">
                        <div>
                            <span class="text-muted block">Pemohon:</span>
                            <span class="text-text font-medium">{{ $job->requester?->name ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-muted block">Inspektor:</span>
                            <span class="text-text font-medium">{{ $job->inspector?->name ?? __('admin.jobs.no_inspector') }}</span>
                        </div>
                    </div>

                    <div class="pt-2">
                        <x-ui.button
                            :href="route('admin.jobs.show', $job->id)"
                            variant="secondary"
                            class="w-full text-xs justify-center"
                        >
                            {{ __('admin.jobs.action_detail') }}
                        </x-ui.button>
                    </div>
                </x-ui.card>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="pt-4">
            {{ $jobs->links() }}
        </div>
    @endif
</div>
