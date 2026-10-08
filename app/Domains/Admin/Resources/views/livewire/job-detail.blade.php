<div class="ds-sans max-w-7xl mx-auto py-6 space-y-8 pb-12">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div class="space-y-2">
            <a href="{{ route('admin.jobs.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-ds-blue hover:text-ds-blue-ink">
                <i aria-hidden="true" class="fa-solid fa-chevron-left"></i>
                {{ __('admin.jobs.back_to_list') }}
            </a>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-ds-ink tracking-tight flex flex-wrap items-center gap-3">
                <span>{{ __('admin.jobs.detail_title', ['id' => $job->id]) }}</span>
                @php
                    $statusPill = match ($job->status->color()) {
                        'success' => 'bg-ds-green-soft text-ds-green-ink',
                        'warning' => 'bg-ds-amber-soft text-ds-amber-ink',
                        'danger' => 'bg-ds-red-soft text-ds-red-ink',
                        'primary' => 'bg-ds-blue-soft text-ds-blue-ink',
                        default => 'bg-ds-soft text-ds-muted',
                    };
                @endphp
                <span class="text-[10px] px-2.5 py-1 rounded-full font-bold uppercase tracking-wider {{ $statusPill }}">
                    {{ $job->status->label() }}
                </span>
                @if ($job->needs_attention_at && !$job->isFinal())
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-extrabold bg-ds-red-soft text-ds-red-ink">
                        {{ __('admin.jobs.needs_action') }}
                    </span>
                @endif
            </h1>
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-wrap items-center gap-2">
            @if ($canCancel)
                <button
                    wire:click="openCancelModal"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-bold bg-white border border-ds-red-soft text-ds-danger hover:bg-ds-red-soft/30 rounded-xl transition-all shadow-xs"
                >
                    {{ __('admin.jobs.cancel_job') }}
                </button>
            @endif

            @if ($canComplete)
                <button
                    wire:click="openCompleteModal"
                    class="inline-flex items-center justify-center gap-2 px-7 py-3 text-sm font-bold bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink rounded-xl transition-all shadow-md"
                >
                    {{ __('admin.jobs.complete_job') }}
                </button>
            @endif

            @if ($canReopen)
                <button
                    wire:click="openReopenModal"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-bold bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink rounded-xl transition-all shadow-xs"
                >
                    {{ __('admin.jobs.reopen_result') }}
                </button>
            @endif
        </div>
    </div>

    {{-- Alerts --}}
    @if ($successMessage)
        <div role="status" class="p-4 rounded-2xl border border-ds-green-soft bg-ds-green-soft/40 text-xs font-semibold text-ds-ink">
            {{ $successMessage }}
        </div>
    @endif

    @if ($errorMessage)
        <div role="alert" class="p-4 rounded-2xl border border-ds-red-soft bg-ds-red-soft/40 text-xs font-semibold text-ds-ink">
            {{ $errorMessage }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left 2 Cols: Main Info --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Vehicle & Private Location Data (ADMIN ONLY) --}}
            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                <h2 class="text-sm font-extrabold text-ds-ink">{{ __('admin.jobs.section_vehicle') }}</h2>
                <div class="space-y-4 mt-4">
                    <div>
                        <h3 class="text-lg font-extrabold text-ds-ink">{{ $job->listing?->title ?? '-' }}</h3>
                        <p class="text-sm text-ds-muted">
                            {{ $job->listing?->brand }} {{ $job->listing?->model }} &bull; {{ $job->listing?->year }} &bull;
                            <span class="font-extrabold text-ds-ink">{{ \App\Support\Money::format($job->listing?->price ?? 0) }}</span>
                        </p>
                    </div>

                    {{-- Data Privat Persis (Hanya untuk Admin) --}}
                    <div class="p-4 rounded-2xl bg-ds-canvas/60 border border-ds-line/80 space-y-3">
                        <div class="text-xs font-extrabold text-ds-blue uppercase tracking-wider">
                            {{ __('admin.jobs.sensitive_title') }}
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-xs text-ds-muted block">{{ __('admin.jobs.chassis_number') }}</span>
                                <span class="font-mono font-bold text-ds-ink">
                                    {{ $job->listing?->chassis_number ?? '-' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-xs text-ds-muted block">{{ __('admin.jobs.coordinates') }}</span>
                                <span class="font-mono text-ds-ink">
                                    @if ($job->listing?->lat && $job->listing?->lng)
                                        {{ $job->listing->lat }}, {{ $job->listing->lng }}
                                    @else
                                        -
                                    @endif
                                </span>
                            </div>
                            <div class="sm:col-span-2">
                                <span class="text-xs text-ds-muted block">{{ __('admin.jobs.address_full') }}</span>
                                <span class="text-ds-ink font-bold">
                                    {{ $job->listing?->address_full ?? '-' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Result Info if submitted/draft --}}
            @if ($result)
                <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                    <h2 class="text-sm font-extrabold text-ds-ink">{{ __('admin.jobs.section_result') }}</h2>
                    <div class="space-y-4 mt-4">
                        <div class="flex items-center gap-2">
                            @php
                                $resultPill = match ($result->status->color()) {
                                    'success' => 'bg-ds-green-soft text-ds-green-ink',
                                    'warning' => 'bg-ds-amber-soft text-ds-amber-ink',
                                    'danger' => 'bg-ds-red-soft text-ds-red-ink',
                                    'primary' => 'bg-ds-blue-soft text-ds-blue-ink',
                                    default => 'bg-ds-soft text-ds-muted',
                                };
                                $recPill = $result->recommendation ? match ($result->recommendation->color()) {
                                    'success' => 'bg-ds-green-soft text-ds-green-ink',
                                    'warning' => 'bg-ds-amber-soft text-ds-amber-ink',
                                    'danger' => 'bg-ds-red-soft text-ds-red-ink',
                                    default => 'bg-ds-soft text-ds-muted',
                                } : '';
                            @endphp
                            <span class="text-[10px] px-2 py-0.5 rounded-md font-bold {{ $resultPill }}">
                                {{ $result->status->label() }}
                            </span>
                            @if ($result->recommendation)
                                <span class="text-[10px] px-2 py-0.5 rounded-md font-bold {{ $recPill }}">
                                    {{ $result->recommendation->label() }}
                                </span>
                            @endif
                        </div>

                        @if ($result->total_score !== null)
                            <div class="flex items-center gap-4 p-4 rounded-2xl bg-ds-canvas/60 border border-ds-line/60">
                                <div>
                                    <span class="text-xs text-ds-muted block">{{ __('admin.jobs.score_total') }}</span>
                                    <span class="text-2xl font-extrabold text-ds-ink">{{ $result->total_score }} / 100</span>
                                </div>
                            </div>
                        @endif

                        @if ($result->summary_note)
                            <div>
                                <span class="text-xs text-ds-muted block">{{ __('admin.jobs.summary_notes') }}</span>
                                <p class="text-sm text-ds-ink mt-1">{{ $result->summary_note }}</p>
                            </div>
                        @endif

                        @if ($result->reopen_reason)
                            <div class="p-3 rounded-2xl bg-ds-amber-soft/40 border border-ds-amber-soft text-xs space-y-1">
                                <span class="font-extrabold text-ds-amber-ink">{{ __('admin.jobs.reopened_title') }}</span>
                                <p class="text-ds-ink">{{ __('admin.jobs.reopen_reason_prefix') }} {{ $result->reopen_reason }}</p>
                                @if ($result->reopened_at)
                                    <p class="text-ds-muted">{{ $result->reopened_at?->translatedFormat('d M Y H:i') }}</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- Right 1 Col: Parties & Timestamps --}}
        <div class="space-y-6">
            {{-- General & Timing Info --}}
            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                <h2 class="text-sm font-extrabold text-ds-ink">{{ __('admin.jobs.section_general') }}</h2>
                <dl class="space-y-3 text-sm mt-4">
                    <div>
                        <dt class="text-xs text-ds-muted">{{ __('admin.jobs.fee') }}</dt>
                        <dd class="text-ds-ink font-extrabold">{{ \App\Support\Money::format($job->fee_amount) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-ds-muted">{{ __('admin.jobs.platform_fee_pct') }}</dt>
                        <dd class="text-ds-ink font-bold">{{ $job->platform_fee_pct }}%</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-ds-muted">{{ __('admin.jobs.created_label') }}</dt>
                        <dd class="text-ds-ink text-xs font-bold">{{ $job->created_at?->translatedFormat('d M Y H:i') }}</dd>
                    </div>
                    @if ($job->scheduled_at)
                        <div>
                            <dt class="text-xs text-ds-muted">{{ __('admin.jobs.schedule_time') }}</dt>
                            <dd class="text-ds-ink text-xs font-bold">{{ $job->scheduled_at?->translatedFormat('d M Y H:i') }}</dd>
                        </div>
                    @endif
                    @if ($job->completed_at)
                        <div>
                            <dt class="text-xs text-ds-muted">{{ __('admin.jobs.completed_label') }}</dt>
                            <dd class="text-ds-ink text-xs font-bold">{{ $job->completed_at?->translatedFormat('d M Y H:i') }}</dd>
                        </div>
                    @endif
                    @if ($job->cancel_reason)
                        <div class="pt-2 border-t border-ds-line/60">
                            <dt class="text-xs text-ds-danger font-bold">{{ __('admin.jobs.cancel_reason_title') }}</dt>
                            <dd class="text-ds-ink text-xs mt-1">{{ $job->cancel_reason }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Requester Info --}}
            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                <h2 class="text-sm font-extrabold text-ds-ink">{{ __('admin.jobs.section_requester') }}</h2>
                <div class="text-sm space-y-1 mt-4">
                    <div class="font-bold text-ds-ink">{{ $job->requester?->name ?? '-' }}</div>
                    <div class="text-xs text-ds-muted">{{ $job->requester?->email ?? '-' }}</div>
                    @if ($job->requester?->phone)
                        <div class="text-xs text-ds-muted">{{ $job->requester->phone }}</div>
                    @endif
                </div>
            </div>

            {{-- Inspector Info --}}
            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                <h2 class="text-sm font-extrabold text-ds-ink">{{ __('admin.jobs.section_inspector') }}</h2>
                @if ($job->inspector)
                    <div class="text-sm space-y-1 mt-4">
                        <div class="font-bold text-ds-ink">{{ $job->inspector->name }}</div>
                        <div class="text-xs text-ds-muted">{{ $job->inspector->email }}</div>
                        @if ($job->inspector->phone)
                            <div class="text-xs text-ds-muted">{{ $job->inspector->phone }}</div>
                        @endif
                    </div>
                @else
                    <span class="text-sm text-ds-muted italic">{{ __('admin.jobs.no_inspector') }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal 1: Cancel Job --}}
    @if ($showCancelModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-ds-ink/50">
            <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-6 space-y-4">
                <h3 class="text-lg font-extrabold text-ds-danger">
                    {{ __('admin.jobs.cancel_modal_title') }}
                </h3>
                <p class="text-sm text-ds-muted">
                    {{ __('admin.jobs.cancel_modal_desc') }}
                </p>

                <div class="space-y-3">
                    <div class="w-full">
                        <label for="cancelReason" class="block text-xs font-bold text-ds-ink mb-1.5">
                            {{ __('admin.jobs.cancel_reason_label') }}
                            <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                            <span class="sr-only">(wajib diisi)</span>
                        </label>
                        <textarea id="cancelReason" name="cancelReason" wire:model="cancelReason" rows="3" required
                            placeholder="{{ __('admin.jobs.cancel_reason_placeholder') }}"
                            class="w-full px-4 py-3 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors"></textarea>
                        @error('cancelReason')
                            <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    @if (in_array($job->status, [\App\Domains\Inspection\Enums\JobStatus::InProgress, \App\Domains\Inspection\Enums\JobStatus::ResultSubmitted], true))
                        <label class="flex items-center gap-2 cursor-pointer text-sm text-ds-ink pt-2">
                            <input
                                type="checkbox"
                                wire:model="refund"
                                class="rounded border-ds-line text-ds-blue focus:ring-ds-blue/30 w-4 h-4"
                            />
                            <span>{{ __('admin.jobs.refund_checkbox') }}</span>
                        </label>
                    @elseif (in_array($job->status, [\App\Domains\Inspection\Enums\JobStatus::Paid, \App\Domains\Inspection\Enums\JobStatus::Taken, \App\Domains\Inspection\Enums\JobStatus::Scheduled], true))
                        <p class="text-xs text-ds-amber-ink bg-ds-amber-soft/40 p-2 rounded-xl font-semibold">
                            {{ __('admin.jobs.refund_notice_auto') }}
                        </p>
                    @else
                        <p class="text-xs text-ds-muted">
                            {{ __('admin.jobs.refund_notice_none') }}
                        </p>
                    @endif
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="closeCancelModal"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-bold bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink rounded-xl transition-all shadow-xs">
                        Batal
                    </button>
                    <button type="button" wire:click="cancelJob"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-bold bg-white border border-ds-red-soft text-ds-danger hover:bg-ds-red-soft/30 rounded-xl transition-all shadow-xs">
                        {{ __('admin.jobs.confirm_cancel_button') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal 2: Complete Job --}}
    @if ($showCompleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-ds-ink/50">
            <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-6 space-y-4">
                <h3 class="text-lg font-extrabold text-ds-ink">
                    {{ __('admin.jobs.complete_modal_title') }}
                </h3>
                <p class="text-sm text-ds-muted">
                    {{ __('admin.jobs.complete_modal_desc') }}
                </p>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="closeCompleteModal"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-bold bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink rounded-xl transition-all shadow-xs">
                        Batal
                    </button>
                    <button type="button" wire:click="completeJob"
                        class="inline-flex items-center justify-center gap-2 px-7 py-3 text-sm font-bold bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink rounded-xl transition-all shadow-md">
                        {{ __('admin.jobs.confirm_complete_button') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal 3: Reopen Result --}}
    @if ($showReopenModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-ds-ink/50">
            <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-6 space-y-4">
                <h3 class="text-lg font-extrabold text-ds-ink">
                    {{ __('admin.jobs.reopen_modal_title') }}
                </h3>
                <p class="text-sm text-ds-muted">
                    {{ __('admin.jobs.reopen_modal_desc') }}
                </p>

                <div class="space-y-3">
                    <div class="w-full">
                        <label for="reopenReason" class="block text-xs font-bold text-ds-ink mb-1.5">
                            {{ __('admin.jobs.reopen_reason_label') }}
                            <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                            <span class="sr-only">(wajib diisi)</span>
                        </label>
                        <textarea id="reopenReason" name="reopenReason" wire:model="reopenReason" rows="3" required
                            placeholder="{{ __('admin.jobs.reopen_reason_placeholder') }}"
                            class="w-full px-4 py-3 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors"></textarea>
                        @error('reopenReason')
                            <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="closeReopenModal"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-bold bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink rounded-xl transition-all shadow-xs">
                        Batal
                    </button>
                    <button type="button" wire:click="reopenResult"
                        class="inline-flex items-center justify-center gap-2 px-7 py-3 text-sm font-bold bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink rounded-xl transition-all shadow-md">
                        {{ __('admin.jobs.confirm_reopen_button') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
