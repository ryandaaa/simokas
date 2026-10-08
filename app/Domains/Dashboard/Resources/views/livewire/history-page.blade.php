<div class="ds-sans space-y-8 pb-12">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div class="space-y-2">
            <span class="text-ds-blue text-xs font-extrabold uppercase tracking-wider">{{ __('dashboard.history.title') }}</span>
            <h1 class="text-3xl font-extrabold text-ds-ink tracking-tight">
                {{ __('dashboard.history.title') }}
            </h1>
            <p class="text-sm text-ds-muted">
                {{ __('dashboard.history.subtitle') }}
            </p>
        </div>

        <div>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs">
                <i aria-hidden="true" class="fa-solid fa-chevron-left"></i>
                {{ __('dashboard.title') }}
            </a>
        </div>
    </div>

    {{-- Pesan Galat --}}
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

    {{-- Filter Nav Tabs --}}
    <div class="flex items-center gap-2 overflow-x-auto pb-2" role="tablist">
        @foreach ([
            'all' => __('dashboard.history.tabs.all'),
            'buying' => __('dashboard.history.tabs.buying'),
            'selling' => __('dashboard.history.tabs.selling'),
            'results' => __('dashboard.history.tabs.results'),
        ] as $key => $label)
            <button
                wire:click="setTab('{{ $key }}')"
                role="tab"
                aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                class="shrink-0 px-4 py-1.5 text-sm font-bold rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-ds-blue {{ $tab === $key ? 'bg-ds-ink text-white' : 'bg-white text-ds-muted border border-ds-line hover:border-ds-blue hover:text-ds-blue' }}">
                {{ $label }}
            </button>
        @endforeach

        @if ($isApprovedInspector)
            <button
                wire:click="setTab('jobs')"
                role="tab"
                aria-selected="{{ $tab === 'jobs' ? 'true' : 'false' }}"
                class="shrink-0 px-4 py-1.5 text-sm font-bold rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-ds-blue {{ $tab === 'jobs' ? 'bg-ds-ink text-white' : 'bg-white text-ds-muted border border-ds-line hover:border-ds-blue hover:text-ds-blue' }}">
                {{ __('dashboard.history.tabs.jobs') }}
            </button>
        @endif
    </div>

    {{-- Indikator memuat saat pergantian tab --}}
    <div wire:loading class="w-full">
        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
            <div class="flex items-center justify-center gap-3 py-6 text-ds-muted">
                <i aria-hidden="true" class="fa-solid fa-circle-notch fa-spin"></i>
                <span class="text-sm font-medium">{{ __('ui.actions.loading') }}</span>
            </div>
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

    <div wire:loading.remove class="space-y-10">
        {{-- (1) Bagian Transaksi Pembelian --}}
        @if ($tab === 'all' || $tab === 'buying')
            <section aria-labelledby="heading-buying-transactions" class="space-y-4">
                <div>
                    <h2 id="heading-buying-transactions" class="text-xl font-extrabold text-ds-ink tracking-tight">
                        {{ __('dashboard.history.buying_transactions') }}
                    </h2>
                    <p class="text-xs text-ds-muted mt-1">
                        {{ __('dashboard.history.buying_transactions_desc') }}
                    </p>
                </div>

                @if ($buyingTransactions->isEmpty())
                    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-8 text-center max-w-2xl mx-auto">
                        <div class="w-14 h-14 mx-auto rounded-2xl bg-ds-soft text-ds-muted flex items-center justify-center text-xl mb-4">
                            <i aria-hidden="true" class="fa-solid fa-file-lines"></i>
                        </div>
                        <h3 class="text-base font-extrabold text-ds-ink">{{ __('dashboard.history.empty_buying_title') }}</h3>
                        <p class="text-xs text-ds-muted mt-2">{{ __('dashboard.history.empty_buying_desc') }}</p>
                        <div class="flex justify-center mt-6">
                            <a href="{{ route('catalog.index') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md">
                                {{ __('dashboard.history.action_browse') }}
                            </a>
                        </div>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($buyingTransactions as $tx)
                            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-md transition-all p-4 sm:p-5">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div class="space-y-1.5">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-base font-extrabold text-ds-ink">
                                                {{ $tx->listing?->title ?? __('dashboard.history.vehicle') }}
                                            </h3>
                                            <span class="text-[10px] px-2 py-0.5 rounded-md font-bold {{ $chip($tx->status->color()) }}">
                                                {{ $tx->status->label() }}
                                            </span>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-ds-muted">
                                            <span class="font-extrabold text-ds-ink">
                                                {{ \App\Support\Money::format($tx->price_amount) }}
                                            </span>
                                            @if ($tx->listing?->area_name)
                                                <span>{{ __('dashboard.history.area', ['area' => $tx->listing->area_name]) }}</span>
                                            @endif
                                            <span>{{ $tx->created_at?->translatedFormat('d M Y') }}</span>
                                        </div>
                                    </div>
                                    <div class="shrink-0">
                                        <a href="{{ route('transactions.show', $tx) }}"
                                            class="inline-flex items-center justify-center px-4 py-1.5 min-h-[38px] text-sm font-bold bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink rounded-xl transition-all shadow-xs">
                                            {{ __('dashboard.history.view_detail') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <x-ui.pagination :paginator="$buyingTransactions" />
                    </div>
                @endif
            </section>
        @endif

        {{-- (2) Bagian Transaksi Penjualan --}}
        @if ($tab === 'all' || $tab === 'selling')
            <section aria-labelledby="heading-selling-transactions" class="space-y-4">
                <div>
                    <h2 id="heading-selling-transactions" class="text-xl font-extrabold text-ds-ink tracking-tight">
                        {{ __('dashboard.history.selling_transactions') }}
                    </h2>
                    <p class="text-xs text-ds-muted mt-1">
                        {{ __('dashboard.history.selling_transactions_desc') }}
                    </p>
                </div>

                @if ($sellingTransactions->isEmpty())
                    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-8 text-center max-w-2xl mx-auto">
                        <div class="w-14 h-14 mx-auto rounded-2xl bg-ds-soft text-ds-muted flex items-center justify-center text-xl mb-4">
                            <i aria-hidden="true" class="fa-solid fa-file-lines"></i>
                        </div>
                        <h3 class="text-base font-extrabold text-ds-ink">{{ __('dashboard.history.empty_selling_title') }}</h3>
                        <p class="text-xs text-ds-muted mt-2">{{ __('dashboard.history.empty_selling_desc') }}</p>
                        <div class="flex justify-center mt-6">
                            <a href="{{ route('listings.create') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md">
                                {{ __('dashboard.history.action_create_listing') }}
                            </a>
                        </div>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($sellingTransactions as $tx)
                            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-md transition-all p-4 sm:p-5">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div class="space-y-1.5">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-base font-extrabold text-ds-ink">
                                                {{ $tx->listing?->title ?? __('dashboard.history.vehicle') }}
                                            </h3>
                                            <span class="text-[10px] px-2 py-0.5 rounded-md font-bold {{ $chip($tx->status->color()) }}">
                                                {{ $tx->status->label() }}
                                            </span>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-ds-muted">
                                            <span class="font-extrabold text-ds-ink">
                                                {{ \App\Support\Money::format($tx->price_amount) }}
                                            </span>
                                            @if ($tx->buyer)
                                                <span>{{ __('dashboard.history.buyer', ['name' => $tx->buyer->name]) }}</span>
                                            @endif
                                            <span>{{ $tx->created_at?->translatedFormat('d M Y') }}</span>
                                        </div>
                                    </div>
                                    <div class="shrink-0">
                                        <a href="{{ route('transactions.show', $tx) }}"
                                            class="inline-flex items-center justify-center px-4 py-1.5 min-h-[38px] text-sm font-bold bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink rounded-xl transition-all shadow-xs">
                                            {{ __('dashboard.history.view_detail') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <x-ui.pagination :paginator="$sellingTransactions" />
                    </div>
                @endif
            </section>
        @endif

        {{-- (3) Bagian Hasil Inspeksi (Tetap tampil walau listing terjual) --}}
        @if ($tab === 'all' || $tab === 'results')
            <section aria-labelledby="heading-inspection-results" class="space-y-4">
                <div>
                    <h2 id="heading-inspection-results" class="text-xl font-extrabold text-ds-ink tracking-tight">
                        {{ __('dashboard.history.inspection_results') }}
                    </h2>
                    <p class="text-xs text-ds-muted mt-1">
                        {{ __('dashboard.history.inspection_results_desc') }}
                    </p>
                </div>

                @if ($inspectionResults->isEmpty())
                    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-8 text-center max-w-2xl mx-auto">
                        <div class="w-14 h-14 mx-auto rounded-2xl bg-ds-soft text-ds-muted flex items-center justify-center text-xl mb-4">
                            <i aria-hidden="true" class="fa-solid fa-magnifying-glass"></i>
                        </div>
                        <h3 class="text-base font-extrabold text-ds-ink">{{ __('dashboard.history.empty_results_title') }}</h3>
                        <p class="text-xs text-ds-muted mt-2">{{ __('dashboard.history.empty_results_desc') }}</p>
                        <div class="flex justify-center mt-6">
                            <a href="{{ route('catalog.index') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md">
                                {{ __('dashboard.history.action_browse') }}
                            </a>
                        </div>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($inspectionResults as $result)
                            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-md transition-all p-4 sm:p-5">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div class="space-y-1.5">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-base font-extrabold text-ds-ink">
                                                {{ $result->listing?->title ?? __('dashboard.history.vehicle') }}
                                            </h3>
                                            @if ($result->recommendation)
                                                <span class="text-[10px] px-2 py-0.5 rounded-md font-bold {{ $chip($result->recommendation->color()) }}">
                                                    {{ $result->recommendation->label() }}
                                                </span>
                                            @endif
                                            @if ($result->listing && $result->listing->status->value === 'sold')
                                                <span class="text-[10px] px-2 py-0.5 rounded-md font-bold {{ $chip($result->listing->status->color()) }}">
                                                    {{ $result->listing->status->label() }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-ds-muted">
                                            @if ($result->total_score !== null)
                                                <span class="font-bold text-ds-ink">
                                                    {{ __('dashboard.history.score', ['score' => $result->total_score]) }}
                                                </span>
                                            @endif
                                            @if ($result->inspector)
                                                <span>{{ $result->inspector->name }}</span>
                                            @endif
                                            <span>{{ ($result->submitted_at ?? $result->created_at)?->translatedFormat('d M Y') }}</span>
                                        </div>
                                    </div>
                                    <div class="shrink-0">
                                        @if ($result->listing)
                                            <a href="{{ route('results.show', $result->listing) }}"
                                                class="inline-flex items-center justify-center gap-2 px-4 py-1.5 min-h-[38px] text-sm font-bold bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink rounded-xl transition-all shadow-xs">
                                                <i aria-hidden="true" class="fa-solid fa-file-lines text-xs"></i>
                                                {{ __('dashboard.history.view_result') }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <x-ui.pagination :paginator="$inspectionResults" />
                    </div>
                @endif
            </section>
        @endif

        {{-- (4) Bagian Pekerjaan Inspeksi (Khusus Inspektor Terverifikasi) --}}
        @if ($isApprovedInspector && ($tab === 'all' || $tab === 'jobs'))
            <section aria-labelledby="heading-inspector-jobs" class="space-y-4">
                <div>
                    <h2 id="heading-inspector-jobs" class="text-xl font-extrabold text-ds-ink tracking-tight">
                        {{ __('dashboard.history.inspector_jobs') }}
                    </h2>
                    <p class="text-xs text-ds-muted mt-1">
                        {{ __('dashboard.history.inspector_jobs_desc') }}
                    </p>
                </div>

                @if ($inspectorJobs === null || $inspectorJobs->isEmpty())
                    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-8 text-center max-w-2xl mx-auto">
                        <div class="w-14 h-14 mx-auto rounded-2xl bg-ds-soft text-ds-muted flex items-center justify-center text-xl mb-4">
                            <i aria-hidden="true" class="fa-solid fa-briefcase"></i>
                        </div>
                        <h3 class="text-base font-extrabold text-ds-ink">{{ __('dashboard.history.empty_jobs_title') }}</h3>
                        <p class="text-xs text-ds-muted mt-2">{{ __('dashboard.history.empty_jobs_desc') }}</p>
                        <div class="flex justify-center mt-6">
                            <a href="{{ route('jobs.open') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md">
                                {{ __('dashboard.history.action_open_jobs') }}
                            </a>
                        </div>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($inspectorJobs as $job)
                            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-md transition-all p-4 sm:p-5">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div class="space-y-1.5">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-base font-extrabold text-ds-ink">
                                                {{ $job->listing?->title ?? __('dashboard.history.vehicle') }}
                                            </h3>
                                            <span class="text-[10px] px-2 py-0.5 rounded-md font-bold {{ $chip($job->status->color()) }}">
                                                {{ $job->status->label() }}
                                            </span>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-ds-muted">
                                            <span class="font-extrabold text-ds-ink">
                                                {{ \App\Support\Money::format($job->fee_amount) }}
                                            </span>
                                            <span>{{ ($job->completed_at ?? $job->updated_at)?->translatedFormat('d M Y') }}</span>
                                        </div>
                                    </div>
                                    <div class="shrink-0">
                                        <a href="{{ route('jobs.show', $job) }}"
                                            class="inline-flex items-center justify-center px-4 py-1.5 min-h-[38px] text-sm font-bold bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink rounded-xl transition-all shadow-xs">
                                            {{ __('dashboard.history.view_detail') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <x-ui.pagination :paginator="$inspectorJobs" />
                    </div>
                @endif
            </section>
        @endif
    </div>
</div>
