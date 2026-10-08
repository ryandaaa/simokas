<div class="ds-sans space-y-8 pb-12">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div class="space-y-2">
            <span class="text-ds-blue text-xs font-extrabold uppercase tracking-wider">{{ __('inspection.inspector_jobs.title') }}</span>
            <h1 class="text-3xl font-extrabold text-ds-ink tracking-tight">{{ __('inspection.inspector_jobs.title') }}</h1>
            <p class="text-sm text-ds-muted">{{ __('inspection.inspector_jobs.subtitle') }}</p>
        </div>
        <a href="{{ route('jobs.index') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md">
            <i aria-hidden="true" class="fa-solid fa-magnifying-glass text-xs"></i>
            {{ __('inspection.inspector_jobs.open_jobs_button') }}
        </a>
    </div>

    @if ($errorMessage)
        <div role="alert" class="p-4 rounded-2xl border border-ds-red-soft bg-ds-red-soft/40 text-xs font-semibold text-ds-ink">
            {{ $errorMessage }}
            <div class="mt-2">
                <button type="button" wire:click="retry" class="text-sm font-bold text-ds-blue hover:text-ds-blue-ink">
                    {{ __('inspection.mine.retry') }}
                </button>
            </div>
        </div>
    @endif

    <div wire:loading role="status" class="w-full text-center py-4 text-sm text-ds-muted">
        <i aria-hidden="true" class="fa-solid fa-circle-notch fa-spin mr-2"></i>
        {{ __('ui.actions.loading') }}
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

    @if ($jobs === null || $jobs->isEmpty())
        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-8 text-center max-w-2xl mx-auto">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-ds-soft text-ds-muted flex items-center justify-center text-xl mb-4">
                <i aria-hidden="true" class="fa-solid fa-file-lines"></i>
            </div>
            <h3 class="text-lg font-extrabold text-ds-ink">{{ __('inspection.inspector_jobs.empty_title') }}</h3>
            <p class="text-xs text-ds-muted mt-2">{{ __('inspection.inspector_jobs.empty_desc') }}</p>
            <div class="flex justify-center mt-6">
                <a href="{{ route('jobs.index') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md">
                    {{ __('inspection.inspector_jobs.open_jobs_button') }}
                </a>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($jobs as $job)
                <div class="group bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col justify-between">
                    <div class="p-5 space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h3 class="font-extrabold text-ds-ink text-lg group-hover:text-ds-blue transition-colors">
                                    {{ $job->listing?->brand }} {{ $job->listing?->model }}
                                </h3>
                                <p class="text-xs text-ds-muted mt-0.5">
                                    {{ $job->listing?->year }} · {{ ucfirst((string) ($job->listing?->vehicle_type?->value ?? $job->listing?->vehicle_type)) }}
                                    @if ($job->listing?->area_name)
                                        · {{ $job->listing->area_name }}
                                    @endif
                                </p>
                            </div>
                            <span class="text-[10px] px-2 py-0.5 rounded-md font-bold shrink-0 {{ $chip($job->status->color()) }}">
                                {{ $job->status->label() }}
                            </span>
                        </div>

                        <div class="pt-3 border-t border-ds-line/60 flex items-baseline justify-between">
                            <span class="text-xs text-ds-muted">{{ __('inspection.open_jobs.fee_label') }}</span>
                            <span class="text-lg font-extrabold text-ds-ink">
                                {{ \App\Support\Money::format($job->fee_amount) }}
                            </span>
                        </div>
                    </div>

                    <div class="px-5 py-3 bg-ds-canvas/80 border-t border-ds-line/60 space-y-2">
                        @if ($job->status === \App\Domains\Inspection\Enums\JobStatus::InProgress)
                            <a href="{{ route('result.fill', ['job' => $job->id]) }}"
                                class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 min-h-[44px] bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-xs rounded-xl transition-all shadow-md">
                                {{ __('inspection.inspector_jobs.fill_result') }}
                            </a>
                        @elseif (in_array($job->status, [\App\Domains\Inspection\Enums\JobStatus::ResultSubmitted, \App\Domains\Inspection\Enums\JobStatus::Completed], true) && \Illuminate\Support\Facades\Route::has('results.show'))
                            <a href="{{ route('results.show', ['listing' => $job->listing_id]) }}"
                                class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 min-h-[44px] bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-xs rounded-xl transition-all shadow-md">
                                {{ __('inspection.inspector_jobs.view_result') }}
                            </a>
                        @endif
                        <a href="{{ route('jobs.show', ['job' => $job->id]) }}"
                            class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 min-h-[44px] bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs">
                            {{ __('inspection.inspector_jobs.view_detail') }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            <x-ui.pagination :paginator="$jobs" />
        </div>
    @endif
</div>
