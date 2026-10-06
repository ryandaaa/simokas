<div class="mx-auto max-w-2xl px-4 py-6 sm:py-8">
    <h1 class="text-xl font-bold text-text sm:text-2xl">{{ __('inspection.mine.title') }}</h1>
    <p class="mt-1 text-sm text-muted">{{ __('inspection.mine.subtitle') }}</p>

    @if ($actionSuccess)
        <div class="mt-4">
            <x-ui.alert variant="success" :message="$actionSuccess" dismissible />
        </div>
    @endif

    @if ($actionError)
        <div class="mt-4">
            <x-ui.alert variant="danger" :message="$actionError" dismissible />
        </div>
    @endif

    <div wire:loading.delay class="mt-4 space-y-3">
        <x-ui.skeleton variant="card" />
        <x-ui.skeleton variant="card" />
    </div>

    <div wire:loading.remove.delay class="mt-4">
        @if ($errorMessage)
            <x-ui.alert variant="danger" :message="$errorMessage" />
            <div class="mt-3">
                <x-ui.button type="button" variant="secondary" wire:click="retry" wire:loading.attr="disabled">
                    {{ __('inspection.mine.retry') }}
                </x-ui.button>
            </div>
        @elseif ($jobs === null)
            <x-ui.alert variant="danger" :message="__('inspection.mine.load_failed')" />
            <div class="mt-3">
                <x-ui.button type="button" variant="secondary" wire:click="retry" wire:loading.attr="disabled">
                    {{ __('inspection.mine.retry') }}
                </x-ui.button>
            </div>
        @elseif ($jobs->isEmpty())
            <x-ui.empty-state
                icon="dokumen"
                :title="__('inspection.mine.empty_title')"
                :description="__('inspection.mine.empty_desc')"
                :actionLabel="__('inspection.mine.browse')"
                :actionUrl="route('catalog.index')"
            />
        @else
            <div class="space-y-3">
                @foreach ($jobs as $job)
                    <x-ui.card>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-text">{{ $job->listing?->title ?? $job->listing_id }}</p>
                                <p class="mt-0.5 text-xs text-muted">
                                    <x-ui.money :amount="$job->fee_amount" />
                                    •
                                    <x-ui.datetime :value="$job->created_at" format="date" />
                                    @if ($job->scheduled_at)
                                        •
                                        <span>{{ __('inspection.detail.scheduled_at_label') }}: {{ \App\Support\Clock::formatWib($job->scheduled_at) }}</span>
                                    @endif
                                </p>
                            </div>

                            <div class="flex items-center gap-2 self-start sm:self-auto">
                                <x-ui.status-chip :label="$job->status->label()" :variant="$job->status->color()" :icon="$job->status->icon()" />

                                @if ($job->status === \App\Domains\Inspection\Enums\JobStatus::Requested)
                                    @if (isset($paymentMap[$job->id]))
                                        <x-ui.button
                                            :href="route('payments.show', ['payment' => $paymentMap[$job->id]])"
                                            variant="primary"
                                            class="!min-h-[36px] !px-3.5 !py-1 text-xs"
                                        >
                                            {{ __('inspection.mine.pay') }}
                                        </x-ui.button>
                                    @else
                                        <div class="flex flex-col items-start text-xs text-muted">
                                            <span>{{ __('inspection.mine.bill_expired') }}</span>
                                            @if (\Illuminate\Support\Facades\Route::has('jobs.request') && $job->listing_id)
                                                <a
                                                    href="{{ route('jobs.request', ['listing' => $job->listing_id]) }}"
                                                    class="text-xs font-semibold text-primary hover:underline"
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
                                    <x-ui.button
                                        type="button"
                                        variant="ghost"
                                        wire:click="confirmCancel('{{ $job->id }}')"
                                        wire:loading.attr="disabled"
                                        class="text-xs text-danger hover:bg-danger/10"
                                    >
                                        {{ __('inspection.mine.cancel_button') }}
                                    </x-ui.button>
                                @elseif ($job->status === \App\Domains\Inspection\Enums\JobStatus::InProgress)
                                    <span class="text-xs text-muted italic ml-1">
                                        {{ __('inspection.mine.in_progress_notice') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </x-ui.card>
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

            <div>
                <x-ui.textarea
                    id="requester_cancel_reason"
                    name="cancel_reason"
                    :label="__('inspection.mine.cancel_reason_label')"
                    :placeholder="__('inspection.mine.cancel_reason_placeholder')"
                    wire:model="cancel_reason"
                    rows="2"
                />
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
