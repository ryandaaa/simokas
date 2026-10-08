<div class="ds-sans max-w-7xl mx-auto py-6 space-y-8 pb-12">
    {{-- Header --}}
    <div class="space-y-2">
        <span class="text-ds-blue text-xs font-extrabold uppercase tracking-wider">{{ __('admin.jobs.title') }}</span>
        <h1 class="text-3xl font-extrabold text-ds-ink tracking-tight">
            {{ __('admin.jobs.title') }}
        </h1>
        <p class="text-sm text-ds-muted">
            {{ __('admin.jobs.subtitle') }}
        </p>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-center">
            <div class="lg:col-span-2">
                <div class="relative">
                    <i aria-hidden="true" class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-ds-faint text-sm"></i>
                    <input
                        id="search"
                        name="search"
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('admin.jobs.search_placeholder') }}"
                        aria-label="{{ __('admin.jobs.search_placeholder') }}"
                        class="w-full h-11 pl-11 pr-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors"
                    />
                </div>
            </div>
            <div>
                <select
                    id="filterStatus"
                    wire:model.live="filterStatus"
                    aria-label="{{ __('admin.jobs.filter_all_status') }}"
                    class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors"
                >
                    <option value="">{{ __('admin.jobs.filter_all_status') }}</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center">
                <label class="inline-flex items-center gap-2 cursor-pointer text-sm font-bold text-ds-ink">
                    <input
                        type="checkbox"
                        wire:model.live="filterAttentionOnly"
                        class="rounded border-ds-line text-ds-blue focus:ring-ds-blue/30 w-4 h-4"
                    />
                    <span>{{ __('admin.jobs.filter_attention_only') }}</span>
                </label>
            </div>
        </div>
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

    {{-- Job List --}}
    <div wire:loading.remove class="space-y-6">
    @if ($jobs->isEmpty())
        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-8 text-center max-w-2xl mx-auto">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-ds-soft text-ds-muted flex items-center justify-center text-xl mb-4">
                <i aria-hidden="true" class="fa-solid fa-magnifying-glass"></i>
            </div>
            <h3 class="text-lg font-extrabold text-ds-ink">{{ __('admin.jobs.empty_title') }}</h3>
            <p class="text-xs text-ds-muted mt-2">{{ __('admin.jobs.empty_description') }}</p>
        </div>
    @else
        {{-- Desktop Table --}}
        <div class="hidden md:block">
            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs overflow-hidden">
                <table class="min-w-full divide-y divide-ds-line/60 text-left text-sm">
                    <thead class="bg-ds-canvas/80 text-ds-muted">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-bold text-xs uppercase tracking-wider">{{ __('admin.jobs.table_vehicle') }}</th>
                            <th scope="col" class="px-4 py-3 font-bold text-xs uppercase tracking-wider">{{ __('admin.jobs.table_requester') }}</th>
                            <th scope="col" class="px-4 py-3 font-bold text-xs uppercase tracking-wider">{{ __('admin.jobs.table_inspector') }}</th>
                            <th scope="col" class="px-4 py-3 font-bold text-xs uppercase tracking-wider">{{ __('admin.jobs.table_status') }}</th>
                            <th scope="col" class="px-4 py-3 font-bold text-xs uppercase tracking-wider">{{ __('admin.jobs.table_created') }}</th>
                            <th scope="col" class="px-4 py-3 font-bold text-xs uppercase tracking-wider text-right">{{ __('admin.jobs.action_detail') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ds-line/60 bg-white">
                        @foreach ($jobs as $job)
                            <tr class="hover:bg-ds-canvas/60 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="font-bold text-ds-ink">{{ $job->listing?->title ?? '-' }}</div>
                                    <div class="font-mono text-xs text-ds-faint truncate max-w-xs">{{ $job->id }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-ds-ink font-bold">{{ $job->requester?->name ?? '-' }}</div>
                                    <div class="text-xs text-ds-muted">{{ $job->requester?->email ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if ($job->inspector)
                                        <div class="text-ds-ink font-bold">{{ $job->inspector->name }}</div>
                                        <div class="text-xs text-ds-muted">{{ $job->inspector->email }}</div>
                                    @else
                                        <span class="text-xs text-ds-muted italic">{{ __('admin.jobs.no_inspector') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] px-2 py-0.5 rounded-md font-bold {{ $chip($job->status->color()) }}">
                                            {{ $job->status->label() }}
                                        </span>
                                        @if ($job->needs_attention_at && !$job->isFinal())
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-extrabold bg-ds-red-soft text-ds-red-ink" title="{{ __('admin.jobs.needs_action') }}">
                                                !
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-ds-muted">
                                    {{ $job->created_at?->translatedFormat('d M Y H:i') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <a href="{{ route('admin.jobs.show', $job->id) }}"
                                        class="inline-flex items-center justify-center px-3 py-1.5 min-h-[36px] text-xs font-bold bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink rounded-xl transition-all shadow-xs">
                                        {{ __('admin.jobs.action_detail') }}
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
            @foreach ($jobs as $job)
                <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] px-2 py-0.5 rounded-md font-bold {{ $chip($job->status->color()) }}">
                            {{ $job->status->label() }}
                        </span>
                        @if ($job->needs_attention_at && !$job->isFinal())
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold bg-ds-red-soft text-ds-red-ink">
                                {{ __('admin.jobs.needs_action') }}
                            </span>
                        @endif
                    </div>

                    <div>
                        <div class="text-sm font-extrabold text-ds-ink">{{ $job->listing?->title ?? '-' }}</div>
                        <div class="font-mono text-xs text-ds-faint truncate mt-0.5">{{ $job->id }}</div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs pt-2 border-t border-ds-line/60">
                        <div>
                            <span class="text-ds-muted block">{{ __('admin.jobs.mobile_requester') }}</span>
                            <span class="text-ds-ink font-bold">{{ $job->requester?->name ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-ds-muted block">{{ __('admin.jobs.mobile_inspector') }}</span>
                            <span class="text-ds-ink font-bold">{{ $job->inspector?->name ?? __('admin.jobs.no_inspector') }}</span>
                        </div>
                    </div>

                    <div class="pt-2">
                        <a href="{{ route('admin.jobs.show', $job->id) }}"
                            class="w-full inline-flex items-center justify-center px-4 py-2.5 text-xs font-bold bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink rounded-xl transition-all shadow-xs">
                            {{ __('admin.jobs.action_detail') }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="pt-4">
            {{ $jobs->links() }}
        </div>
    @endif
    </div>
</div>
