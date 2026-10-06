<div class="max-w-7xl mx-auto py-6 space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.jobs.index') }}" class="text-sm font-medium text-primary hover:underline flex items-center gap-1">
                    &larr; {{ __('admin.jobs.back_to_list') }}
                </a>
            </div>
            <h1 class="text-2xl font-bold text-text mt-2 flex items-center gap-3">
                <span>{{ __('admin.jobs.detail_title', ['id' => $job->id]) }}</span>
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
            </h1>
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-wrap items-center gap-2">
            @if ($canCancel)
                <x-ui.button
                    wire:click="openCancelModal"
                    variant="danger"
                    class="text-sm"
                >
                    {{ __('admin.jobs.cancel_job') }}
                </x-ui.button>
            @endif

            @if ($canComplete)
                <x-ui.button
                    wire:click="openCompleteModal"
                    variant="primary"
                    class="text-sm"
                >
                    {{ __('admin.jobs.complete_job') }}
                </x-ui.button>
            @endif

            @if ($canReopen)
                <x-ui.button
                    wire:click="openReopenModal"
                    variant="secondary"
                    class="text-sm"
                >
                    {{ __('admin.jobs.reopen_result') }}
                </x-ui.button>
            @endif
        </div>
    </div>

    {{-- Alerts --}}
    @if ($successMessage)
        <x-ui.alert variant="success" :message="$successMessage" />
    @endif

    @if ($errorMessage)
        <x-ui.alert variant="danger" :message="$errorMessage" />
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left 2 Cols: Main Info --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Vehicle & Private Location Data (ADMIN ONLY) --}}
            <x-ui.card :title="__('admin.jobs.section_vehicle')" class="p-6">
                <div class="space-y-4">
                    <div>
                        <h3 class="text-lg font-bold text-text">{{ $job->listing?->title ?? '-' }}</h3>
                        <p class="text-sm text-muted">
                            {{ $job->listing?->brand }} {{ $job->listing?->model }} &bull; {{ $job->listing?->year }} &bull;
                            <x-ui.money :amount="$job->listing?->price ?? 0" />
                        </p>
                    </div>

                    {{-- Data Privat Persis (Hanya untuk Admin) --}}
                    <div class="p-4 rounded-lg bg-bg border border-muted/20 space-y-3">
                        <div class="text-xs font-semibold text-primary uppercase tracking-wider">
                            Data Sensitif &amp; Lokasi Persis
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-xs text-muted block">{{ __('admin.jobs.chassis_number') }}</span>
                                <span class="font-mono font-medium text-text">
                                    {{ $job->listing?->chassis_number ?? '-' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-xs text-muted block">{{ __('admin.jobs.coordinates') }}</span>
                                <span class="font-mono text-text">
                                    @if ($job->listing?->lat && $job->listing?->lng)
                                        {{ $job->listing->lat }}, {{ $job->listing->lng }}
                                    @else
                                        -
                                    @endif
                                </span>
                            </div>
                            <div class="sm:col-span-2">
                                <span class="text-xs text-muted block">{{ __('admin.jobs.address_full') }}</span>
                                <span class="text-text font-medium">
                                    {{ $job->listing?->address_full ?? '-' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </x-ui.card>

            {{-- Result Info if submitted/draft --}}
            @if ($result)
                <x-ui.card :title="__('admin.jobs.section_result')" class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <x-ui.status-chip
                                :label="$result->status->label()"
                                :variant="$result->status->color()"
                                :icon="$result->status->icon()"
                            />
                            @if ($result->recommendation)
                                <x-ui.status-chip
                                    :label="$result->recommendation->label()"
                                    :variant="$result->recommendation->color()"
                                    :icon="$result->recommendation->icon()"
                                />
                            @endif
                        </div>

                        @if ($result->total_score !== null)
                            <div class="flex items-center gap-4 p-4 rounded-lg bg-bg">
                                <div>
                                    <span class="text-xs text-muted block">Skor Total:</span>
                                    <span class="text-2xl font-bold text-text">{{ $result->total_score }} / 100</span>
                                </div>
                            </div>
                        @endif

                        @if ($result->summary_note)
                            <div>
                                <span class="text-xs text-muted block">Catatan Ringkasan:</span>
                                <p class="text-sm text-text mt-1">{{ $result->summary_note }}</p>
                            </div>
                        @endif

                        @if ($result->reopen_reason)
                            <div class="p-3 rounded bg-warning/10 border border-warning/20 text-xs space-y-1">
                                <span class="font-bold text-warning">Pernah Dibuka Kembali:</span>
                                <p class="text-text">Alasan: {{ $result->reopen_reason }}</p>
                                @if ($result->reopened_at)
                                    <p class="text-muted"><x-ui.datetime :value="$result->reopened_at" /></p>
                                @endif
                            </div>
                        @endif
                    </div>
                </x-ui.card>
            @endif
        </div>

        {{-- Right 1 Col: Parties & Timestamps --}}
        <div class="space-y-6">
            {{-- General & Timing Info --}}
            <x-ui.card :title="__('admin.jobs.section_general')" class="p-6">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-muted">{{ __('admin.jobs.fee') }}</dt>
                        <dd class="text-text font-bold"><x-ui.money :amount="$job->fee_amount" /></dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted">{{ __('admin.jobs.platform_fee_pct') }}</dt>
                        <dd class="text-text">{{ $job->platform_fee_pct }}%</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted">Waktu Dibuat</dt>
                        <dd class="text-text text-xs"><x-ui.datetime :value="$job->created_at" /></dd>
                    </div>
                    @if ($job->scheduled_at)
                        <div>
                            <dt class="text-xs text-muted">{{ __('admin.jobs.schedule_time') }}</dt>
                            <dd class="text-text text-xs"><x-ui.datetime :value="$job->scheduled_at" /></dd>
                        </div>
                    @endif
                    @if ($job->completed_at)
                        <div>
                            <dt class="text-xs text-muted">Selesai Pada</dt>
                            <dd class="text-text text-xs"><x-ui.datetime :value="$job->completed_at" /></dd>
                        </div>
                    @endif
                    @if ($job->cancel_reason)
                        <div class="pt-2 border-t border-muted/20">
                            <dt class="text-xs text-danger font-medium">Alasan Pembatalan</dt>
                            <dd class="text-text text-xs mt-1">{{ $job->cancel_reason }}</dd>
                        </div>
                    @endif
                </dl>
            </x-ui.card>

            {{-- Requester Info --}}
            <x-ui.card :title="__('admin.jobs.section_requester')" class="p-6">
                <div class="text-sm space-y-1">
                    <div class="font-medium text-text">{{ $job->requester?->name ?? '-' }}</div>
                    <div class="text-xs text-muted">{{ $job->requester?->email ?? '-' }}</div>
                    @if ($job->requester?->phone)
                        <div class="text-xs text-muted">{{ $job->requester->phone }}</div>
                    @endif
                </div>
            </x-ui.card>

            {{-- Inspector Info --}}
            <x-ui.card :title="__('admin.jobs.section_inspector')" class="p-6">
                @if ($job->inspector)
                    <div class="text-sm space-y-1">
                        <div class="font-medium text-text">{{ $job->inspector->name }}</div>
                        <div class="text-xs text-muted">{{ $job->inspector->email }}</div>
                        @if ($job->inspector->phone)
                            <div class="text-xs text-muted">{{ $job->inspector->phone }}</div>
                        @endif
                    </div>
                @else
                    <span class="text-sm text-muted italic">{{ __('admin.jobs.no_inspector') }}</span>
                @endif
            </x-ui.card>
        </div>
    </div>

    {{-- Modal 1: Cancel Job --}}
    @if ($showCancelModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-text/50">
            <div class="bg-surface rounded-xl shadow-lg max-w-lg w-full p-6 space-y-4">
                <h3 class="text-lg font-bold text-danger">
                    {{ __('admin.jobs.cancel_modal_title') }}
                </h3>
                <p class="text-sm text-muted">
                    {{ __('admin.jobs.cancel_modal_desc') }}
                </p>

                <div class="space-y-3">
                    <x-ui.textarea
                        id="cancelReason"
                        name="cancelReason"
                        wire:model="cancelReason"
                        :label="__('admin.jobs.cancel_reason_label')"
                        :placeholder="__('admin.jobs.cancel_reason_placeholder')"
                        :error="$errors->first('cancelReason')"
                        required
                    />

                    @if (in_array($job->status, [\App\Domains\Inspection\Enums\JobStatus::InProgress, \App\Domains\Inspection\Enums\JobStatus::ResultSubmitted], true))
                        <label class="flex items-center gap-2 cursor-pointer text-sm text-text pt-2">
                            <input
                                type="checkbox"
                                wire:model="refund"
                                class="rounded border-muted/30 text-primary focus:ring-accent w-4 h-4"
                            />
                            <span>{{ __('admin.jobs.refund_checkbox') }}</span>
                        </label>
                    @elseif (in_array($job->status, [\App\Domains\Inspection\Enums\JobStatus::Paid, \App\Domains\Inspection\Enums\JobStatus::Taken, \App\Domains\Inspection\Enums\JobStatus::Scheduled], true))
                        <p class="text-xs text-warning bg-warning/10 p-2 rounded">
                            {{ __('admin.jobs.refund_notice_auto') }}
                        </p>
                    @else
                        <p class="text-xs text-muted">
                            {{ __('admin.jobs.refund_notice_none') }}
                        </p>
                    @endif
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <x-ui.button
                        wire:click="closeCancelModal"
                        variant="secondary"
                    >
                        Batal
                    </x-ui.button>
                    <x-ui.button
                        wire:click="cancelJob"
                        variant="danger"
                    >
                        {{ __('admin.jobs.confirm_cancel_button') }}
                    </x-ui.button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal 2: Complete Job --}}
    @if ($showCompleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-text/50">
            <div class="bg-surface rounded-xl shadow-lg max-w-lg w-full p-6 space-y-4">
                <h3 class="text-lg font-bold text-text">
                    {{ __('admin.jobs.complete_modal_title') }}
                </h3>
                <p class="text-sm text-muted">
                    {{ __('admin.jobs.complete_modal_desc') }}
                </p>

                <div class="flex justify-end gap-2 pt-2">
                    <x-ui.button
                        wire:click="closeCompleteModal"
                        variant="secondary"
                    >
                        Batal
                    </x-ui.button>
                    <x-ui.button
                        wire:click="completeJob"
                        variant="primary"
                    >
                        {{ __('admin.jobs.confirm_complete_button') }}
                    </x-ui.button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal 3: Reopen Result --}}
    @if ($showReopenModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-text/50">
            <div class="bg-surface rounded-xl shadow-lg max-w-lg w-full p-6 space-y-4">
                <h3 class="text-lg font-bold text-text">
                    {{ __('admin.jobs.reopen_modal_title') }}
                </h3>
                <p class="text-sm text-muted">
                    {{ __('admin.jobs.reopen_modal_desc') }}
                </p>

                <div class="space-y-3">
                    <x-ui.textarea
                        id="reopenReason"
                        name="reopenReason"
                        wire:model="reopenReason"
                        :label="__('admin.jobs.reopen_reason_label')"
                        :placeholder="__('admin.jobs.reopen_reason_placeholder')"
                        :error="$errors->first('reopenReason')"
                        required
                    />
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <x-ui.button
                        wire:click="closeReopenModal"
                        variant="secondary"
                    >
                        Batal
                    </x-ui.button>
                    <x-ui.button
                        wire:click="reopenResult"
                        variant="primary"
                    >
                        {{ __('admin.jobs.confirm_reopen_button') }}
                    </x-ui.button>
                </div>
            </div>
        </div>
    @endif
</div>
