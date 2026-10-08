<div class="ds-sans mx-auto max-w-2xl px-4 py-6 sm:py-8 space-y-6 pb-12">
    <div class="space-y-2">
        <h1 class="text-3xl font-extrabold text-ds-ink tracking-tight">{{ __('inspection.mine.title') }}</h1>
        <p class="text-sm text-ds-muted">{{ __('inspection.mine.subtitle') }}</p>
    </div>

    @if ($actionSuccess)
        <div role="status" class="p-4 rounded-2xl border border-ds-green-soft bg-ds-green-soft/40 text-xs font-semibold text-ds-ink">
            {{ $actionSuccess }}
        </div>
    @endif

    @if ($actionError)
        <div role="alert" class="p-4 rounded-2xl border border-ds-red-soft bg-ds-red-soft/40 text-xs font-semibold text-ds-ink">
            {{ $actionError }}
        </div>
    @endif

    <div wire:loading.delay class="space-y-3">
        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6 flex items-center justify-center gap-3 py-10 text-ds-muted">
            <i aria-hidden="true" class="fa-solid fa-circle-notch fa-spin"></i>
            <span class="text-sm font-medium">{{ __('ui.actions.loading') }}</span>
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

    <div wire:loading.remove.delay>
        @if ($errorMessage)
            <div role="alert" class="p-4 rounded-2xl border border-ds-red-soft bg-ds-red-soft/40 text-xs font-semibold text-ds-ink">
                {{ $errorMessage }}
            </div>
            <div class="mt-3">
                <button type="button" wire:click="retry" wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs disabled:opacity-60">
                    {{ __('inspection.mine.retry') }}
                </button>
            </div>
        @elseif ($jobs === null)
            <div role="alert" class="p-4 rounded-2xl border border-ds-red-soft bg-ds-red-soft/40 text-xs font-semibold text-ds-ink">
                {{ __('inspection.mine.load_failed') }}
            </div>
            <div class="mt-3">
                <button type="button" wire:click="retry" wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs disabled:opacity-60">
                    {{ __('inspection.mine.retry') }}
                </button>
            </div>
        @elseif ($jobs->isEmpty())
            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-8 text-center max-w-2xl mx-auto">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-ds-soft text-ds-muted flex items-center justify-center text-xl mb-4">
                    <i aria-hidden="true" class="fa-solid fa-file-lines"></i>
                </div>
                <h3 class="text-lg font-extrabold text-ds-ink">{{ __('inspection.mine.empty_title') }}</h3>
                <p class="text-xs text-ds-muted mt-2">{{ __('inspection.mine.empty_desc') }}</p>
                <div class="flex justify-center mt-6">
                    <a href="{{ route('catalog.index') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md">
                        {{ __('inspection.mine.browse') }}
                    </a>
                </div>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($jobs as $job)
                    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-md transition-all p-4 sm:p-5">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-extrabold text-ds-ink">{{ $job->listing?->title ?? $job->listing_id }}</p>
                                <p class="mt-0.5 text-xs text-ds-muted">
                                    <span class="font-extrabold text-ds-ink">{{ \App\Support\Money::format($job->fee_amount) }}</span>
                                    •
                                    {{ $job->created_at?->translatedFormat('d M Y') }}
                                    @if ($job->scheduled_at)
                                        •
                                        <span>{{ __('inspection.detail.scheduled_at_label') }}: {{ \App\Support\Clock::formatWib($job->scheduled_at) }}</span>
                                    @endif
                                </p>
                            </div>

                            <div class="flex items-center gap-2 self-start sm:self-auto">
                                <span class="text-[10px] px-2 py-0.5 rounded-md font-bold {{ $chip($job->status->color()) }}">
                                    {{ $job->status->label() }}
                                </span>

                                @if ($job->status === \App\Domains\Inspection\Enums\JobStatus::Requested)
                                    @if (isset($paymentMap[$job->id]))
                                        <a href="{{ route('payments.show', ['payment' => $paymentMap[$job->id]]) }}"
                                            class="inline-flex items-center justify-center px-3.5 py-1 min-h-[44px] text-xs font-bold bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink rounded-xl transition-all shadow-md">
                                            {{ __('inspection.mine.pay') }}
                                        </a>
                                    @else
                                        <div class="flex flex-col items-start text-xs text-ds-muted">
                                            <span>{{ __('inspection.mine.bill_expired') }}</span>
                                            @if (\Illuminate\Support\Facades\Route::has('jobs.request') && $job->listing_id)
                                                <a
                                                    href="{{ route('jobs.request', ['listing' => $job->listing_id]) }}"
                                                    class="text-xs font-bold text-ds-blue hover:text-ds-blue-ink"
                                                >
                                                    {{ __('inspection.mine.reapply') }}
                                                </a>
                                            @endif
                                        </div>
                                    @endif
                                @endif

                                @if (in_array($job->status, [
                                    \App\Domains\Inspection\Enums\JobStatus::Requested,
                                    \App\Domains\Inspection\Enums\JobStatus::Paid,
                                    \App\Domains\Inspection\Enums\JobStatus::Taken,
                                    \App\Domains\Inspection\Enums\JobStatus::Scheduled,
                                ], true))
                                    <button
                                        type="button"
                                        wire:click="confirmCancel('{{ $job->id }}')"
                                        wire:loading.attr="disabled"
                                        class="min-h-[44px] inline-flex items-center px-2 text-xs font-bold text-ds-danger hover:underline disabled:opacity-60"
                                    >
                                        {{ __('inspection.mine.cancel_button') }}
                                    </button>
                                @elseif ($job->status === \App\Domains\Inspection\Enums\JobStatus::InProgress)
                                    <span class="text-xs text-ds-muted italic ml-1">
                                        {{ __('inspection.mine.in_progress_notice') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $jobs->links() }}
            </div>
        @endif
    </div>

    {{-- Modal Pembatalan Pengajuan oleh Pemohon --}}
    <x-ui.modal
        name="requester-cancel-modal"
        :title="__('inspection.mine.cancel_modal_title')"
        maxWidth="max-w-md"
    >
        <div class="space-y-4 py-2">
            @if ($cancellingJob?->status === \App\Domains\Inspection\Enums\JobStatus::Requested)
                <p class="text-sm text-text">
                    {{ __('inspection.mine.cancel_requested_desc') }}
                </p>
            @else
                <div class="p-3 bg-success/10 border border-success/20 rounded-lg">
                    <p class="text-sm text-text font-medium">
                        {{ __('inspection.mine.cancel_refund_desc') }}
                    </p>
                </div>
            @endif

            <div class="p-3 bg-muted/10 rounded-lg">
                <p class="text-xs text-muted">
                    {{ __('inspection.mine.cancel_in_progress_notice') }}
                </p>
            </div>

            <div class="w-full">
                <label for="requester_cancel_reason" class="block text-xs font-bold text-ds-ink mb-1.5">
                    {{ __('inspection.mine.cancel_reason_label') }}
                </label>
                <textarea id="requester_cancel_reason" name="cancel_reason" wire:model="cancel_reason" rows="2" placeholder="{{ __('inspection.mine.cancel_reason_placeholder') }}"
                    class="w-full px-4 py-3 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors"></textarea>
            </div>
        </div>

        <x-slot:footer>
            <x-ui.button
                type="button"
                variant="secondary"
                @click="open = false"
            >
                {{ __('ui.actions.cancel') }}
            </x-ui.button>

            <x-ui.button
                type="button"
                variant="danger"
                wire:click="cancelJob"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="cancelJob">
                    {{ __('inspection.mine.cancel_confirm') }}
                </span>
                <span wire:loading wire:target="cancelJob">
                    {{ __('inspection.mine.cancelling') }}
                </span>
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
