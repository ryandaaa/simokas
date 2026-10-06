<div class="max-w-7xl mx-auto py-6 space-y-6">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-text">
            {{ __('admin.audit_logs.title') }}
        </h1>
        <p class="text-sm text-muted mt-1">
            {{ __('admin.audit_logs.subtitle') }}
        </p>
    </div>

    {{-- Filter Bar --}}
    <x-ui.card class="p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <x-ui.input
                    id="search"
                    name="search"
                    wire:model.live.debounce.300ms="search"
                    :placeholder="__('admin.audit_logs.search_placeholder')"
                />
            </div>
            <div>
                <select
                    id="filterAction"
                    wire:model.live="filterAction"
                    class="w-full rounded-lg border border-muted/30 bg-surface px-3 py-2 text-sm text-text focus:outline-none focus:ring-2 focus:ring-accent"
                >
                    <option value="">{{ __('admin.audit_logs.all_actions') }}</option>
                    <option value="cancel_job">{{ __('admin.audit_logs.actions.cancel_job') }}</option>
                    <option value="complete_job">{{ __('admin.audit_logs.actions.complete_job') }}</option>
                    <option value="reopen_result">{{ __('admin.audit_logs.actions.reopen_result') }}</option>
                    <option value="update_settings">{{ __('admin.audit_logs.actions.update_settings') }}</option>
                    <option value="approve_inspector">{{ __('admin.audit_logs.actions.approve_inspector') }}</option>
                    <option value="reject_inspector">{{ __('admin.audit_logs.actions.reject_inspector') }}</option>
                    <option value="approve_listing">{{ __('admin.audit_logs.actions.approve_listing') }}</option>
                    <option value="reject_listing">{{ __('admin.audit_logs.actions.reject_listing') }}</option>
                </select>
            </div>
            <div>
                <select
                    id="filterEntity"
                    wire:model.live="filterEntity"
                    class="w-full rounded-lg border border-muted/30 bg-surface px-3 py-2 text-sm text-text focus:outline-none focus:ring-2 focus:ring-accent"
                >
                    <option value="">{{ __('admin.audit_logs.all_entities') }}</option>
                    <option value="job">{{ __('admin.audit_logs.entities.job') }}</option>
                    <option value="inspection_result">{{ __('admin.audit_logs.entities.inspection_result') }}</option>
                    <option value="app_settings">{{ __('admin.audit_logs.entities.app_settings') }}</option>
                    <option value="inspector_profile">{{ __('admin.audit_logs.entities.inspector_profile') }}</option>
                    <option value="listing">{{ __('admin.audit_logs.entities.listing') }}</option>
                </select>
            </div>
            <div>
                <select
                    id="sort"
                    wire:model.live="sort"
                    class="w-full rounded-lg border border-muted/30 bg-surface px-3 py-2 text-sm text-text focus:outline-none focus:ring-2 focus:ring-accent"
                >
                    <option value="desc">{{ __('admin.audit_logs.sort_desc') }}</option>
                    <option value="asc">{{ __('admin.audit_logs.sort_asc') }}</option>
                </select>
            </div>
        </div>
    </x-ui.card>

    {{-- Data Display --}}
    @if ($logs->isEmpty())
        <x-ui.card class="p-8">
            <x-ui.empty-state
                icon="dokumen"
                :title="__('admin.audit_logs.empty_title')"
                :description="__('admin.audit_logs.empty_description')"
            />
        </x-ui.card>
    @else
        {{-- Desktop Table --}}
        <div class="hidden md:block">
            <x-ui.card class="overflow-hidden">
                <table class="min-w-full divide-y divide-muted/20 text-left text-sm">
                    <thead class="bg-bg text-muted">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('admin.audit_logs.table_time') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('admin.audit_logs.table_actor') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('admin.audit_logs.table_action') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('admin.audit_logs.table_entity') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('admin.audit_logs.table_id') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('admin.audit_logs.table_meta') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-muted/20 bg-surface">
                        @foreach ($logs as $log)
                            <tr class="hover:bg-bg/50 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-muted">
                                    <x-ui.datetime :value="$log->created_at" />
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if ($log->actor)
                                        <div class="font-medium text-text">{{ $log->actor->name }}</div>
                                        <div class="text-xs text-muted">{{ $log->actor->email }}</div>
                                    @else
                                        <span class="text-xs text-muted italic">Sistem</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary/10 text-primary">
                                        {{ __('admin.audit_logs.actions.' . $log->action) ?? $log->action }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-text">
                                    {{ __('admin.audit_logs.entities.' . $log->entity) ?? $log->entity }}
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-muted truncate max-w-xs">
                                    {{ $log->entity_id ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-text max-w-sm break-words">
                                    @if (!empty($log->meta))
                                        @if (isset($log->meta['reason']))
                                            <span class="font-medium">Alasan:</span> {{ $log->meta['reason'] }}
                                        @else
                                            <code class="text-xs text-muted">{{ json_encode($log->meta) }}</code>
                                        @endif
                                    @else
                                        <span class="text-muted">{{ __('admin.audit_logs.no_meta') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-ui.card>
        </div>

        {{-- Mobile Cards --}}
        <div class="md:hidden space-y-4">
            @foreach ($logs as $log)
                <x-ui.card class="p-4 space-y-3">
                    <div class="flex items-center justify-between text-xs text-muted">
                        <x-ui.datetime :value="$log->created_at" />
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary/10 text-primary">
                            {{ __('admin.audit_logs.actions.' . $log->action) ?? $log->action }}
                        </span>
                    </div>
                    <div>
                        <div class="text-xs text-muted">Aktor:</div>
                        <div class="text-sm font-medium text-text">
                            {{ $log->actor ? $log->actor->name . ' (' . $log->actor->email . ')' : 'Sistem' }}
                        </div>
                    </div>
                    <div class="text-xs">
                        <span class="text-muted">Target:</span>
                        <span class="font-medium text-text">{{ __('admin.audit_logs.entities.' . $log->entity) ?? $log->entity }}</span>
                        @if ($log->entity_id)
                            <span class="font-mono text-muted block truncate mt-0.5">{{ $log->entity_id }}</span>
                        @endif
                    </div>
                    @if (!empty($log->meta))
                        <div class="text-xs rounded bg-bg p-2 text-text">
                            @if (isset($log->meta['reason']))
                                <span class="font-medium text-muted">Alasan:</span> {{ $log->meta['reason'] }}
                            @else
                                <code class="text-xs text-muted">{{ json_encode($log->meta) }}</code>
                            @endif
                        </div>
                    @endif
                </x-ui.card>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="pt-4">
            {{ $logs->links() }}
        </div>
    @endif
</div>
