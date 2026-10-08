<div class="ds-sans max-w-7xl mx-auto py-6 space-y-8 pb-12">
    {{-- Header --}}
    <div class="space-y-2">
        <h1 class="text-3xl font-extrabold text-ds-ink tracking-tight">
            {{ __('admin.audit_logs.title') }}
        </h1>
        <p class="text-sm text-ds-muted">
            {{ __('admin.audit_logs.subtitle') }}
        </p>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="relative">
                <i aria-hidden="true" class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-ds-faint text-sm"></i>
                <input
                    id="search"
                    name="search"
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('admin.audit_logs.search_placeholder') }}"
                    aria-label="{{ __('admin.audit_logs.search_placeholder') }}"
                    class="w-full h-11 pl-11 pr-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors"
                />
            </div>
            <div>
                <select
                    id="filterAction"
                    wire:model.live="filterAction"
                    aria-label="{{ __('admin.audit_logs.table_action') }}"
                    class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors"
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
                    aria-label="{{ __('admin.audit_logs.table_entity') }}"
                    class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors"
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
                    aria-label="{{ __('admin.audit_logs.table_time') }}"
                    class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors"
                >
                    <option value="desc">{{ __('admin.audit_logs.sort_desc') }}</option>
                    <option value="asc">{{ __('admin.audit_logs.sort_asc') }}</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Data Display --}}
    @if ($logs->isEmpty())
        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-8 text-center max-w-2xl mx-auto">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-ds-soft text-ds-muted flex items-center justify-center text-xl mb-4">
                <i aria-hidden="true" class="fa-solid fa-file-lines"></i>
            </div>
            <h3 class="text-lg font-extrabold text-ds-ink">{{ __('admin.audit_logs.empty_title') }}</h3>
            <p class="text-xs text-ds-muted mt-2">{{ __('admin.audit_logs.empty_description') }}</p>
        </div>
    @else
        {{-- Desktop Table --}}
        <div class="hidden md:block">
            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs overflow-x-auto">
                <table class="min-w-full divide-y divide-ds-line/60 text-left text-sm">
                    <thead class="bg-ds-canvas/80 text-ds-muted">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-bold text-xs uppercase tracking-wider">{{ __('admin.audit_logs.table_time') }}</th>
                            <th scope="col" class="px-4 py-3 font-bold text-xs uppercase tracking-wider">{{ __('admin.audit_logs.table_actor') }}</th>
                            <th scope="col" class="px-4 py-3 font-bold text-xs uppercase tracking-wider">{{ __('admin.audit_logs.table_action') }}</th>
                            <th scope="col" class="px-4 py-3 font-bold text-xs uppercase tracking-wider">{{ __('admin.audit_logs.table_entity') }}</th>
                            <th scope="col" class="px-4 py-3 font-bold text-xs uppercase tracking-wider">{{ __('admin.audit_logs.table_id') }}</th>
                            <th scope="col" class="px-4 py-3 font-bold text-xs uppercase tracking-wider">{{ __('admin.audit_logs.table_meta') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ds-line/60 bg-white">
                        @foreach ($logs as $log)
                            <tr class="hover:bg-ds-canvas/60 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-ds-muted">
                                    {{ $log->created_at?->translatedFormat('d M Y H:i') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if ($log->actor)
                                        <div class="font-bold text-ds-ink">{{ $log->actor->name }}</div>
                                        <div class="text-xs text-ds-muted">{{ $log->actor->email }}</div>
                                    @else
                                        <span class="text-xs text-ds-muted italic">{{ __('admin.audit_logs.system_actor') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-ds-blue-soft text-ds-blue-ink">
                                        {{ __('admin.audit_logs.actions.' . $log->action) ?? $log->action }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-ds-ink font-bold">
                                    {{ __('admin.audit_logs.entities.' . $log->entity) ?? $log->entity }}
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-ds-muted truncate max-w-xs">
                                    {{ $log->entity_id ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-ds-ink max-w-sm break-words">
                                    @if (!empty($log->meta))
                                        @if (isset($log->meta['reason']))
                                            <span class="font-bold">{{ __('admin.audit_logs.reason_prefix') }}</span> {{ $log->meta['reason'] }}
                                        @else
                                            <code class="text-xs text-ds-muted">{{ json_encode($log->meta) }}</code>
                                        @endif
                                    @else
                                        <span class="text-ds-muted">{{ __('admin.audit_logs.no_meta') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile Cards --}}
        <div class="md:hidden space-y-4">
            @foreach ($logs as $log)
                <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-4 space-y-3">
                    <div class="flex items-center justify-between text-xs text-ds-muted">
                        <span>{{ $log->created_at?->translatedFormat('d M Y H:i') }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-ds-blue-soft text-ds-blue-ink">
                            {{ __('admin.audit_logs.actions.' . $log->action) ?? $log->action }}
                        </span>
                    </div>
                    <div>
                        <div class="text-xs text-ds-muted">{{ __('admin.audit_logs.actor_label') }}</div>
                        <div class="text-sm font-bold text-ds-ink">
                            {{ $log->actor ? $log->actor->name . ' (' . $log->actor->email . ')' : __('admin.audit_logs.system_actor') }}
                        </div>
                    </div>
                    <div class="text-xs">
                        <span class="text-ds-muted">{{ __('admin.audit_logs.target_label') }}</span>
                        <span class="font-bold text-ds-ink">{{ __('admin.audit_logs.entities.' . $log->entity) ?? $log->entity }}</span>
                        @if ($log->entity_id)
                            <span class="font-mono text-ds-muted block truncate mt-0.5">{{ $log->entity_id }}</span>
                        @endif
                    </div>
                    @if (!empty($log->meta))
                        <div class="text-xs rounded-2xl bg-ds-canvas/60 border border-ds-line/60 p-2 text-ds-ink">
                            @if (isset($log->meta['reason']))
                                <span class="font-bold text-ds-muted">{{ __('admin.audit_logs.reason_prefix') }}</span> {{ $log->meta['reason'] }}
                            @else
                                <code class="text-xs text-ds-muted">{{ json_encode($log->meta) }}</code>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="pt-4">
            {{ $logs->links() }}
        </div>
    @endif
</div>
