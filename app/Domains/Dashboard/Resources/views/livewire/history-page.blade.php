<div class="space-y-8">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-obs tracking-tight">
                {{ __('dashboard.history.title') }}
            </h1>
            <p class="text-sm text-slate mt-1">
                {{ __('dashboard.history.subtitle') }}
            </p>
        </div>

        {{-- Kembali ke dashboard --}}
        <div>
            <x-ui.button :href="route('dashboard')" variant="secondary" icon="chevron-left">
                {{ __('dashboard.title') }}
            </x-ui.button>
        </div>
    </div>

    {{-- Pesan Galat --}}
    @if (session('error'))
        <x-ui.alert variant="danger" :message="session('error')" />
    @endif
    @if (session('success'))
        <x-ui.alert variant="success" :message="session('success')" />
    @endif

    {{-- Filter Nav Tabs --}}
    <div class="flex items-center gap-1.5 overflow-x-auto pb-2 border-b border-fog" role="tablist">
        <x-ui.button
            wire:click="setTab('all')"
            :variant="$tab === 'all' ? 'primary' : 'ghost'"
            class="!text-sm !py-1.5 !px-4 shrink-0"
            role="tab"
            :aria-selected="$tab === 'all' ? 'true' : 'false'">
            {{ __('dashboard.history.tabs.all') }}
        </x-ui.button>

        <x-ui.button
            wire:click="setTab('buying')"
            :variant="$tab === 'buying' ? 'primary' : 'ghost'"
            class="!text-sm !py-1.5 !px-4 shrink-0"
            role="tab"
            :aria-selected="$tab === 'buying' ? 'true' : 'false'">
            {{ __('dashboard.history.tabs.buying') }}
        </x-ui.button>

        <x-ui.button
            wire:click="setTab('selling')"
            :variant="$tab === 'selling' ? 'primary' : 'ghost'"
            class="!text-sm !py-1.5 !px-4 shrink-0"
            role="tab"
            :aria-selected="$tab === 'selling' ? 'true' : 'false'">
            {{ __('dashboard.history.tabs.selling') }}
        </x-ui.button>

        <x-ui.button
            wire:click="setTab('results')"
            :variant="$tab === 'results' ? 'primary' : 'ghost'"
            class="!text-sm !py-1.5 !px-4 shrink-0"
            role="tab"
            :aria-selected="$tab === 'results' ? 'true' : 'false'">
            {{ __('dashboard.history.tabs.results') }}
        </x-ui.button>

        @if ($isApprovedInspector)
            <x-ui.button
                wire:click="setTab('jobs')"
                :variant="$tab === 'jobs' ? 'primary' : 'ghost'"
                class="!text-sm !py-1.5 !px-4 shrink-0"
                role="tab"
                :aria-selected="$tab === 'jobs' ? 'true' : 'false'">
                {{ __('dashboard.history.tabs.jobs') }}
            </x-ui.button>
        @endif
    </div>

    {{-- Indikator memuat saat pergantian tab --}}
    <div wire:loading class="w-full">
        <x-ui.card class="p-6">
            <div class="flex items-center justify-center gap-3 py-6 text-slate">
                <x-ui.icon name="jam" class="w-5 h-5 animate-spin" />
                <span class="text-sm font-medium">{{ __('ui.actions.loading') }}</span>
            </div>
        </x-ui.card>
    </div>

    <div wire:loading.remove class="space-y-10">
        {{-- (1) Bagian Transaksi Pembelian --}}
        @if ($tab === 'all' || $tab === 'buying')
            <section aria-labelledby="heading-buying-transactions" class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 id="heading-buying-transactions" class="text-lg font-bold text-obs">
                            {{ __('dashboard.history.buying_transactions') }}
                        </h2>
                        <p class="text-xs text-slate">
                            {{ __('dashboard.history.buying_transactions_desc') }}
                        </p>
                    </div>
                </div>

                @if ($buyingTransactions->isEmpty())
                    <x-ui.empty-state
                        icon="dokumen"
                        :title="__('dashboard.history.empty_buying_title')"
                        :description="__('dashboard.history.empty_buying_desc')"
                        :actionLabel="__('dashboard.history.action_browse')"
                        :actionUrl="route('catalog.index')"
                        actionIcon="kaca-pembesar"
                    />
                @else
                    <div class="space-y-3">
                        @foreach ($buyingTransactions as $tx)
                            <x-ui.card class="p-4 sm:p-5 hover:border-pebble transition-colors">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div class="space-y-1.5">
                                        <div class="flex items-center gap-2">
                                            <h3 class="text-base font-bold text-obs">
                                                {{ $tx->listing?->title ?? __('dashboard.history.vehicle') }}
                                            </h3>
                                            <x-ui.status-chip
                                                :label="$tx->status->label()"
                                                :variant="$tx->status->color()"
                                                :icon="$tx->status->icon()"
                                            />
                                        </div>
                                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate">
                                            <span>
                                                <x-ui.money :amount="$tx->price_amount" />
                                            </span>
                                            @if ($tx->listing?->area_name)
                                                <span>{{ __('dashboard.history.area', ['area' => $tx->listing->area_name]) }}</span>
                                            @endif
                                            <span>
                                                <x-ui.datetime :value="$tx->created_at" />
                                            </span>
                                        </div>
                                    </div>
                                    <div class="shrink-0">
                                        <x-ui.button
                                            :href="route('transactions.show', $tx)"
                                            variant="secondary"
                                            class="!min-h-[38px] !px-4 !py-1.5 !text-sm">
                                            {{ __('dashboard.history.view_detail') }}
                                        </x-ui.button>
                                    </div>
                                </div>
                            </x-ui.card>
                        @endforeach

                        <x-ui.pagination :paginator="$buyingTransactions" />
                    </div>
                @endif
            </section>
        @endif

        {{-- (2) Bagian Transaksi Penjualan --}}
        @if ($tab === 'all' || $tab === 'selling')
            <section aria-labelledby="heading-selling-transactions" class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 id="heading-selling-transactions" class="text-lg font-bold text-obs">
                            {{ __('dashboard.history.selling_transactions') }}
                        </h2>
                        <p class="text-xs text-slate">
                            {{ __('dashboard.history.selling_transactions_desc') }}
                        </p>
                    </div>
                </div>

                @if ($sellingTransactions->isEmpty())
                    <x-ui.empty-state
                        icon="dokumen"
                        :title="__('dashboard.history.empty_selling_title')"
                        :description="__('dashboard.history.empty_selling_desc')"
                        :actionLabel="__('dashboard.history.action_create_listing')"
                        :actionUrl="route('listings.create')"
                        actionIcon="centang"
                    />
                @else
                    <div class="space-y-3">
                        @foreach ($sellingTransactions as $tx)
                            <x-ui.card class="p-4 sm:p-5 hover:border-pebble transition-colors">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div class="space-y-1.5">
                                        <div class="flex items-center gap-2">
                                            <h3 class="text-base font-bold text-obs">
                                                {{ $tx->listing?->title ?? __('dashboard.history.vehicle') }}
                                            </h3>
                                            <x-ui.status-chip
                                                :label="$tx->status->label()"
                                                :variant="$tx->status->color()"
                                                :icon="$tx->status->icon()"
                                            />
                                        </div>
                                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate">
                                            <span>
                                                <x-ui.money :amount="$tx->price_amount" />
                                            </span>
                                            @if ($tx->buyer)
                                                <span>{{ __('dashboard.history.buyer', ['name' => $tx->buyer->name]) }}</span>
                                            @endif
                                            <span>
                                                <x-ui.datetime :value="$tx->created_at" />
                                            </span>
                                        </div>
                                    </div>
                                    <div class="shrink-0">
                                        <x-ui.button
                                            :href="route('transactions.show', $tx)"
                                            variant="secondary"
                                            class="!min-h-[38px] !px-4 !py-1.5 !text-sm">
                                            {{ __('dashboard.history.view_detail') }}
                                        </x-ui.button>
                                    </div>
                                </div>
                            </x-ui.card>
                        @endforeach

                        <x-ui.pagination :paginator="$sellingTransactions" />
                    </div>
                @endif
            </section>
        @endif

        {{-- (3) Bagian Hasil Inspeksi (Tetap tampil walau listing terjual) --}}
        @if ($tab === 'all' || $tab === 'results')
            <section aria-labelledby="heading-inspection-results" class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 id="heading-inspection-results" class="text-lg font-bold text-obs">
                            {{ __('dashboard.history.inspection_results') }}
                        </h2>
                        <p class="text-xs text-slate">
                            {{ __('dashboard.history.inspection_results_desc') }}
                        </p>
                    </div>
                </div>

                @if ($inspectionResults->isEmpty())
                    <x-ui.empty-state
                        icon="kaca-pembesar"
                        :title="__('dashboard.history.empty_results_title')"
                        :description="__('dashboard.history.empty_results_desc')"
                        :actionLabel="__('dashboard.history.action_browse')"
                        :actionUrl="route('catalog.index')"
                        actionIcon="kaca-pembesar"
                    />
                @else
                    <div class="space-y-3">
                        @foreach ($inspectionResults as $result)
                            <x-ui.card class="p-4 sm:p-5 hover:border-pebble transition-colors">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div class="space-y-1.5">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-base font-bold text-obs">
                                                {{ $result->listing?->title ?? __('dashboard.history.vehicle') }}
                                            </h3>
                                            @if ($result->recommendation)
                                                <x-ui.status-chip
                                                    :label="$result->recommendation->label()"
                                                    :variant="$result->recommendation->color()"
                                                    :icon="$result->recommendation->icon()"
                                                />
                                            @endif
                                            @if ($result->listing && $result->listing->status->value === 'sold')
                                                <x-ui.status-chip
                                                    :label="$result->listing->status->label()"
                                                    :variant="$result->listing->status->color()"
                                                    :icon="$result->listing->status->icon()"
                                                />
                                            @endif
                                        </div>
                                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate">
                                            @if ($result->total_score !== null)
                                                <span class="font-semibold text-obs">
                                                    {{ __('dashboard.history.score', ['score' => $result->total_score]) }}
                                                </span>
                                            @endif
                                            @if ($result->inspector)
                                                <span>{{ $result->inspector->name }}</span>
                                            @endif
                                            <span>
                                                <x-ui.datetime :value="$result->submitted_at ?? $result->created_at" />
                                            </span>
                                        </div>
                                    </div>
                                    <div class="shrink-0">
                                        @if ($result->listing)
                                            <x-ui.button
                                                :href="route('results.show', $result->listing)"
                                                variant="secondary"
                                                icon="dokumen"
                                                class="!min-h-[38px] !px-4 !py-1.5 !text-sm">
                                                {{ __('dashboard.history.view_result') }}
                                            </x-ui.button>
                                        @endif
                                    </div>
                                </div>
                            </x-ui.card>
                        @endforeach

                        <x-ui.pagination :paginator="$inspectionResults" />
                    </div>
                @endif
            </section>
        @endif

        {{-- (4) Bagian Pekerjaan Inspeksi (Khusus Inspektor Terverifikasi) --}}
        @if ($isApprovedInspector && ($tab === 'all' || $tab === 'jobs'))
            <section aria-labelledby="heading-inspector-jobs" class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 id="heading-inspector-jobs" class="text-lg font-bold text-obs">
                            {{ __('dashboard.history.inspector_jobs') }}
                        </h2>
                        <p class="text-xs text-slate">
                            {{ __('dashboard.history.inspector_jobs_desc') }}
                        </p>
                    </div>
                </div>

                @if ($inspectorJobs === null || $inspectorJobs->isEmpty())
                    <x-ui.empty-state
                        icon="jam"
                        :title="__('dashboard.history.empty_jobs_title')"
                        :description="__('dashboard.history.empty_jobs_desc')"
                        :actionLabel="__('dashboard.history.action_open_jobs')"
                        :actionUrl="route('jobs.open')"
                        actionIcon="kaca-pembesar"
                    />
                @else
                    <div class="space-y-3">
                        @foreach ($inspectorJobs as $job)
                            <x-ui.card class="p-4 sm:p-5 hover:border-pebble transition-colors">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div class="space-y-1.5">
                                        <div class="flex items-center gap-2">
                                            <h3 class="text-base font-bold text-obs">
                                                {{ $job->listing?->title ?? __('dashboard.history.vehicle') }}
                                            </h3>
                                            <x-ui.status-chip
                                                :label="$job->status->label()"
                                                :variant="$job->status->color()"
                                                :icon="$job->status->icon()"
                                            />
                                        </div>
                                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate">
                                            <span>
                                                <x-ui.money :amount="$job->fee_amount" />
                                            </span>
                                            <span>
                                                <x-ui.datetime :value="$job->completed_at ?? $job->updated_at" />
                                            </span>
                                        </div>
                                    </div>
                                    <div class="shrink-0">
                                        <x-ui.button
                                            :href="route('jobs.show', $job)"
                                            variant="secondary"
                                            class="!min-h-[38px] !px-4 !py-1.5 !text-sm">
                                            {{ __('dashboard.history.view_detail') }}
                                        </x-ui.button>
                                    </div>
                                </div>
                            </x-ui.card>
                        @endforeach

                        <x-ui.pagination :paginator="$inspectorJobs" />
                    </div>
                @endif
            </section>
        @endif
    </div>
</div>
