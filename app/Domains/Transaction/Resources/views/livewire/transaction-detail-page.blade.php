<div class="ds-sans mx-auto max-w-2xl px-4 py-6 sm:py-8 space-y-6 pb-12">
    <a href="{{ route('transactions.index') }}" class="min-h-[44px] inline-flex items-center gap-1 text-xs font-bold text-ds-blue hover:text-ds-blue-ink">
        <i aria-hidden="true" class="fa-solid fa-chevron-left"></i>
        {{ __('transaction.detail.back') }}
    </a>

    <div class="flex items-center justify-between gap-3">
        <div class="space-y-1">
            <span class="text-ds-blue text-xs font-extrabold uppercase tracking-wider">{{ __('transaction.detail.title') }}</span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-ds-ink tracking-tight">{{ __('transaction.detail.title') }}</h1>
        </div>
        @php
            $statusPill = match ($transaction->status->color()) {
                'success' => 'bg-ds-green-soft text-ds-green-ink',
                'warning' => 'bg-ds-amber-soft text-ds-amber-ink',
                'danger' => 'bg-ds-red-soft text-ds-red-ink',
                'primary' => 'bg-ds-blue-soft text-ds-blue-ink',
                default => 'bg-ds-soft text-ds-muted',
            };
        @endphp
        <span class="text-[10px] px-2.5 py-1 rounded-full font-bold uppercase tracking-wider {{ $statusPill }}">
            {{ $transaction->status->label() }}
        </span>
    </div>

    <div wire:loading>
        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6 flex items-center justify-center gap-3 py-10 text-ds-muted">
            <i aria-hidden="true" class="fa-solid fa-circle-notch fa-spin"></i>
            <span class="text-sm font-medium">{{ __('ui.actions.loading') }}</span>
        </div>
    </div>

    <div wire:loading.remove class="space-y-4">
        @if ($errorMessage)
            <div role="alert" class="p-4 rounded-2xl border border-ds-red-soft bg-ds-red-soft/40 text-xs font-semibold text-ds-ink">
                {{ $errorMessage }}
            </div>
        @endif

        @if ($actionSuccess)
            <div role="status" class="p-4 rounded-2xl border border-ds-green-soft bg-ds-green-soft/40 text-xs font-semibold text-ds-ink">
                {{ $actionSuccess }}
            </div>
        @endif

        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
            <h2 class="text-lg font-extrabold text-ds-ink">{{ $transaction->listing->title }}</h2>
            <div class="space-y-3 text-sm mt-3">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-ds-muted">{{ $transaction->listing->vehicle_type->label() }} • {{ $transaction->listing->year }} • {{ $transaction->listing->area_name }}</span>
                </div>
                <div class="flex items-baseline justify-between border-t border-ds-line/60 pt-3">
                    <span class="text-ds-muted">{{ __('transaction.detail.price_label') }}</span>
                    <span class="text-2xl font-extrabold text-ds-ink">{{ \App\Support\Money::format($transaction->price_amount) }}</span>
                </div>
                @if ($transaction->notes)
                    <div class="border-t border-ds-line/60 pt-3">
                        <p class="text-ds-muted">{{ __('transaction.detail.notes_label') }}</p>
                        <p class="mt-1 text-ds-ink">{{ $transaction->notes }}</p>
                    </div>
                @endif
                <div class="flex items-baseline justify-between border-t border-ds-line/60 pt-3">
                    <span class="text-ds-muted">{{ __('transaction.detail.expires_label') }}</span>
                    <span class="text-xs font-bold text-ds-ink">{{ $transaction->expires_at?->translatedFormat('d M Y H:i') }}</span>
                </div>
            </div>
        </div>

        @if ($isSeller && $transaction->status->value === 'offered')
            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                <h2 class="text-sm font-extrabold text-ds-ink">{{ __('transaction.detail.breakdown_title') }}</h2>
                <div class="space-y-3 text-sm mt-3">
                    <p class="text-ds-muted">{{ __('transaction.detail.seller_offered_notice') }}</p>
                    <div class="flex items-baseline justify-between border-t border-ds-line/60 pt-3">
                        <span class="text-ds-muted">{{ __('transaction.detail.price_label') }}</span>
                        <span class="font-extrabold text-ds-ink">{{ \App\Support\Money::format($breakdown['price']) }}</span>
                    </div>
                    @if ($breakdown['commissionPct'] !== null)
                        <div class="flex items-baseline justify-between">
                            <span class="text-ds-muted">{{ __('transaction.detail.commission_label', ['pct' => $breakdown['commissionPct']]) }}</span>
                            <span class="font-extrabold text-ds-ink">{{ \App\Support\Money::format($breakdown['commission']) }}</span>
                        </div>
                    @else
                        <div class="p-4 rounded-2xl border border-ds-blue-soft bg-ds-blue-soft/40 text-xs font-semibold text-ds-ink">
                            {{ __('transaction.detail.no_commission') }}
                        </div>
                    @endif
                    <div class="flex items-baseline justify-between border-t border-ds-line/60 pt-3">
                        <span class="font-bold text-ds-ink">{{ __('transaction.detail.seller_receives_label') }}</span>
                        <span class="text-xl font-extrabold text-ds-ink">{{ \App\Support\Money::format($breakdown['sellerNet']) }}</span>
                    </div>
                </div>

                <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                    <button
                        type="button"
                        @click="$dispatch('open-modal', 'accept-offer-modal')"
                        wire:loading.attr="disabled"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md disabled:opacity-60"
                    >
                        {{ __('transaction.detail.accept') }}
                    </button>
                    <button
                        type="button"
                        @click="$dispatch('open-modal', 'reject-offer-modal')"
                        wire:loading.attr="disabled"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3 bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-sm rounded-xl transition-all shadow-xs disabled:opacity-60"
                    >
                        {{ __('transaction.detail.reject') }}
                    </button>
                </div>
            </div>
        @endif

        @if ($isBuyer && $transaction->status->value === 'offered')
            <div class="p-4 rounded-2xl border border-ds-blue-soft bg-ds-blue-soft/40 text-xs font-semibold text-ds-ink">
                {{ __('transaction.detail.buyer_offered_notice') }}
            </div>

            @if ($canCancel)
                <button
                    type="button"
                    @click="$dispatch('open-modal', 'cancel-modal')"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 min-h-[44px] bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs disabled:opacity-60"
                >
                    {{ __('transaction.detail.cancel') }}
                </button>
            @endif
        @endif

        @if ($isBuyer && $transaction->status->value === 'accepted')
            @if ($payBlockedByJob)
                <div class="p-4 rounded-2xl border border-ds-blue-soft bg-ds-blue-soft/40 text-xs font-semibold text-ds-ink">
                    {{ __('transaction.detail.pay_waiting_job') }}
                </div>
            @elseif ($canPay)
                <button
                    type="button"
                    wire:click="pay"
                    wire:loading.attr="disabled"
                    wire:target="pay"
                    class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="pay">
                        {{ __('transaction.detail.pay') }}
                    </span>
                    <span wire:loading wire:target="pay">
                        {{ __('transaction.detail.paying') }}
                    </span>
                </button>
            @endif

            @if ($canCancel)
                <button
                    type="button"
                    @click="$dispatch('open-modal', 'cancel-modal')"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 min-h-[44px] bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs disabled:opacity-60"
                >
                    {{ __('transaction.detail.cancel') }}
                </button>
            @endif
        @endif

        @if ($isSeller && $transaction->status->value === 'accepted' && $canCancel)
            <button
                type="button"
                @click="$dispatch('open-modal', 'cancel-modal')"
                wire:loading.attr="disabled"
                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 min-h-[44px] bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs disabled:opacity-60"
            >
                {{ __('transaction.detail.cancel') }}
            </button>
        @endif

        @if ($canConfirmHandover)
            <button
                type="button"
                @click="$dispatch('open-modal', 'handover-modal')"
                class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md"
            >
                {{ __('transaction.detail.confirm_handover') }}
            </button>
        @endif

        @if ($transaction->status->value === 'paid_held')
            <div class="p-4 rounded-2xl border border-ds-blue-soft bg-ds-blue-soft/40 text-xs font-semibold text-ds-ink">
                {{ __('transaction.detail.paid_held_notice') }}
            </div>

            @if ($showNeedsAttention)
                <div class="p-4 rounded-2xl border border-ds-amber-soft bg-ds-amber-soft/40 text-xs font-semibold text-ds-ink">
                    {{ __('transaction.detail.needs_attention_notice') }}
                </div>
            @endif

            <div class="flex flex-col gap-3 sm:flex-row">
                @if ($canRequestCancellation)
                    <button
                        type="button"
                        @click="$dispatch('open-modal', 'request-cancellation-modal')"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 min-h-[44px] bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs disabled:opacity-60"
                    >
                        {{ __('transaction.detail.request_cancellation') }}
                    </button>
                @elseif ($isBuyer && $hasCancelRequest)
                    <div class="p-4 rounded-2xl border border-ds-blue-soft bg-ds-blue-soft/40 text-xs font-semibold text-ds-ink">
                        {{ __('transaction.detail.cancel_request_waiting') }}
                    </div>
                @endif

                @if ($canApproveCancellation)
                    <button
                        type="button"
                        @click="$dispatch('open-modal', 'approve-cancellation-modal')"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md disabled:opacity-60"
                    >
                        {{ __('transaction.detail.approve_cancellation') }}
                    </button>
                @endif

                @if ($canCancel && $isSeller)
                    <button
                        type="button"
                        @click="$dispatch('open-modal', 'cancel-refund-modal')"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 min-h-[44px] bg-white border border-ds-red-soft text-ds-danger hover:bg-ds-red-soft/30 font-bold text-xs rounded-xl transition-all shadow-xs disabled:opacity-60"
                    >
                        {{ __('transaction.detail.cancel_refund') }}
                    </button>
                @elseif ($canCancel && $isAdmin)
                    <button
                        type="button"
                        @click="$dispatch('open-modal', 'cancel-refund-modal')"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 min-h-[44px] bg-white border border-ds-red-soft text-ds-danger hover:bg-ds-red-soft/30 font-bold text-xs rounded-xl transition-all shadow-xs disabled:opacity-60"
                    >
                        {{ __('transaction.detail.cancel_admin') }}
                    </button>
                @endif
            </div>
        @endif

        @if ($transaction->status->value === 'cancelled')
            <div class="p-4 rounded-2xl border border-ds-line/80 bg-ds-canvas/60 text-xs font-semibold text-ds-muted">
                {{ __('transaction.detail.cancelled_notice') }}
            </div>
        @endif

        @if ($transaction->status->value === 'expired')
            <div class="p-4 rounded-2xl border border-ds-line/80 bg-ds-canvas/60 text-xs font-semibold text-ds-muted">
                {{ __('transaction.detail.expired_notice') }}
            </div>
        @endif

        @if ($transaction->status->value === 'completed')
            <div class="p-4 rounded-2xl border border-ds-green-soft bg-ds-green-soft/40 text-xs font-semibold text-ds-ink">
                {{ __('transaction.detail.completed_notice') }}
            </div>

            @if ($isBuyer && $transaction->inspector_id)
                <div class="pt-4">
                    <livewire:rating-form :transaction-id="$transaction->id" :key="'rating-'.$transaction->id" />
                </div>
            @endif
        @endif
    </div>

    <x-ui.modal
        name="accept-offer-modal"
        :title="__('transaction.detail.accept_confirm_title')"
        :description="__('transaction.detail.accept_confirm_description')"
    >
        <x-slot:footer>
            <x-ui.button
                type="button"
                variant="secondary"
                @click="open = false"
                wire:loading.attr="disabled"
            >
                {{ __('transaction.detail.cancel') }}
            </x-ui.button>

            <x-ui.button
                type="button"
                variant="primary"
                wire:click="accept"
                wire:loading.attr="disabled"
                wire:target="accept"
            >
                <span wire:loading.remove wire:target="accept">
                    {{ __('transaction.detail.accept') }}
                </span>
                <span wire:loading wire:target="accept">
                    {{ __('transaction.detail.accepting') }}
                </span>
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal
        name="reject-offer-modal"
        :title="__('transaction.detail.reject_confirm_title')"
        :description="__('transaction.detail.reject_confirm_description')"
    >
        <x-slot:footer>
            <x-ui.button
                type="button"
                variant="secondary"
                @click="open = false"
                wire:loading.attr="disabled"
            >
                {{ __('transaction.detail.cancel') }}
            </x-ui.button>

            <x-ui.button
                type="button"
                variant="danger"
                wire:click="reject"
                wire:loading.attr="disabled"
                wire:target="reject"
            >
                <span wire:loading.remove wire:target="reject">
                    {{ __('transaction.detail.reject') }}
                </span>
                <span wire:loading wire:target="reject">
                    {{ __('transaction.detail.rejecting') }}
                </span>
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal
        name="handover-modal"
        :title="__('transaction.detail.handover_confirm_title')"
        :description="__('transaction.detail.handover_confirm_description')"
    >
        <x-slot:footer>
            <x-ui.button
                type="button"
                variant="secondary"
                @click="open = false"
                wire:loading.attr="disabled"
            >
                {{ __('transaction.detail.cancel') }}
            </x-ui.button>

            <x-ui.button
                type="button"
                variant="primary"
                wire:click="confirmHandover"
                wire:loading.attr="disabled"
                wire:target="confirmHandover"
            >
                <span wire:loading.remove wire:target="confirmHandover">
                    {{ __('transaction.detail.confirm_handover') }}
                </span>
                <span wire:loading wire:target="confirmHandover">
                    {{ __('transaction.detail.confirming_handover') }}
                </span>
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal
        name="cancel-modal"
        :title="__('transaction.detail.cancel_confirm_title')"
        :description="__('transaction.detail.cancel_confirm_description')"
    >
        <div class="w-full">
            <label for="cancelReason" class="block text-xs font-bold text-ds-ink mb-1.5">
                {{ __('transaction.detail.cancel_reason_label') }}
            </label>
            <textarea id="cancelReason" name="cancelReason" wire:model="cancelReason" rows="3"
                class="w-full px-4 py-3 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors"></textarea>
        </div>

        <x-slot:footer>
            <x-ui.button
                type="button"
                variant="secondary"
                @click="open = false"
                wire:loading.attr="disabled"
            >
                {{ __('transaction.detail.cancel') }}
            </x-ui.button>

            <x-ui.button
                type="button"
                variant="danger"
                wire:click="cancel"
                wire:loading.attr="disabled"
                wire:target="cancel"
            >
                <span wire:loading.remove wire:target="cancel">
                    {{ __('transaction.detail.cancel') }}
                </span>
                <span wire:loading wire:target="cancel">
                    {{ __('transaction.detail.cancelling') }}
                </span>
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal
        name="cancel-refund-modal"
        :title="__('transaction.detail.cancel_refund_confirm_title')"
        :description="__('transaction.detail.cancel_refund_confirm_description')"
    >
        <div class="w-full">
            <label for="cancelReasonRefund" class="block text-xs font-bold text-ds-ink mb-1.5">
                {{ __('transaction.detail.cancel_reason_required_label') }}
            </label>
            <textarea id="cancelReasonRefund" name="cancelReason" wire:model="cancelReason" rows="3" required
                class="w-full px-4 py-3 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors"></textarea>
        </div>

        <x-slot:footer>
            <x-ui.button
                type="button"
                variant="secondary"
                @click="open = false"
                wire:loading.attr="disabled"
            >
                {{ __('transaction.detail.cancel') }}
            </x-ui.button>

            <x-ui.button
                type="button"
                variant="danger"
                wire:click="cancel"
                wire:loading.attr="disabled"
                wire:target="cancel"
            >
                <span wire:loading.remove wire:target="cancel">
                    {{ __('transaction.detail.cancel_refund') }}
                </span>
                <span wire:loading wire:target="cancel">
                    {{ __('transaction.detail.cancelling') }}
                </span>
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal
        name="request-cancellation-modal"
        :title="__('transaction.detail.request_cancellation_confirm_title')"
        :description="__('transaction.detail.request_cancellation_confirm_description')"
    >
        <x-slot:footer>
            <x-ui.button
                type="button"
                variant="secondary"
                @click="open = false"
                wire:loading.attr="disabled"
            >
                {{ __('transaction.detail.cancel') }}
            </x-ui.button>

            <x-ui.button
                type="button"
                variant="primary"
                wire:click="requestCancellation"
                wire:loading.attr="disabled"
                wire:target="requestCancellation"
            >
                <span wire:loading.remove wire:target="requestCancellation">
                    {{ __('transaction.detail.request_cancellation') }}
                </span>
                <span wire:loading wire:target="requestCancellation">
                    {{ __('transaction.detail.requesting_cancellation') }}
                </span>
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal
        name="approve-cancellation-modal"
        :title="__('transaction.detail.approve_cancellation_confirm_title')"
        :description="__('transaction.detail.approve_cancellation_confirm_description')"
    >
        <div class="w-full">
            <label for="cancelReasonApprove" class="block text-xs font-bold text-ds-ink mb-1.5">
                {{ __('transaction.detail.cancel_reason_required_label') }}
            </label>
            <textarea id="cancelReasonApprove" name="cancelReason" wire:model="cancelReason" rows="3" required
                class="w-full px-4 py-3 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors"></textarea>
        </div>

        <x-slot:footer>
            <x-ui.button
                type="button"
                variant="secondary"
                @click="open = false"
                wire:loading.attr="disabled"
            >
                {{ __('transaction.detail.cancel') }}
            </x-ui.button>

            <x-ui.button
                type="button"
                variant="primary"
                wire:click="approveCancellation"
                wire:loading.attr="disabled"
                wire:target="approveCancellation"
            >
                <span wire:loading.remove wire:target="approveCancellation">
                    {{ __('transaction.detail.approve_cancellation') }}
                </span>
                <span wire:loading wire:target="approveCancellation">
                    {{ __('transaction.detail.approving_cancellation') }}
                </span>
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
