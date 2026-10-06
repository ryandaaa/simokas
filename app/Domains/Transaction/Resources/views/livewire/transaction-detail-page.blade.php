<div class="mx-auto max-w-2xl px-4 py-6 sm:py-8">
    <x-ui.button :href="route('transactions.index')" variant="ghost" icon="chevron-left">
        {{ __('transaction.detail.back') }}
    </x-ui.button>

    <div class="mt-4 flex items-center justify-between gap-3">
        <h1 class="text-xl font-bold text-text sm:text-2xl">{{ __('transaction.detail.title') }}</h1>
        <x-ui.status-chip
            :label="$transaction->status->label()"
            :variant="$transaction->status->color()"
            :icon="$transaction->status->icon()"
        />
    </div>

    <div wire:loading class="mt-4">
        <x-ui.skeleton variant="card" />
    </div>

    <div wire:loading.remove class="mt-4 space-y-4">
        @if ($errorMessage)
            <x-ui.alert variant="danger" :message="$errorMessage" />
        @endif

        @if ($actionSuccess)
            <x-ui.alert variant="success" :message="$actionSuccess" />
        @endif

        <x-ui.card :title="$transaction->listing->title">
            <div class="space-y-3 text-sm">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-muted">{{ $transaction->listing->vehicle_type->label() }} • {{ $transaction->listing->year }} • {{ $transaction->listing->area_name }}</span>
                </div>
                <div class="flex items-baseline justify-between border-t border-muted/15 pt-3">
                    <span class="text-muted">{{ __('transaction.detail.price_label') }}</span>
                    <span class="text-2xl font-bold text-primary"><x-ui.money :amount="$transaction->price_amount" /></span>
                </div>
                @if ($transaction->notes)
                    <div class="border-t border-muted/15 pt-3">
                        <p class="text-muted">{{ __('transaction.detail.notes_label') }}</p>
                        <p class="mt-1 text-text">{{ $transaction->notes }}</p>
                    </div>
                @endif
                <div class="flex items-baseline justify-between border-t border-muted/15 pt-3">
                    <span class="text-muted">{{ __('transaction.detail.expires_label') }}</span>
                    <x-ui.datetime :value="$transaction->expires_at" />
                </div>
            </div>
        </x-ui.card>

        @if ($isSeller && $transaction->status->value === 'offered')
            <x-ui.card :title="__('transaction.detail.breakdown_title')">
                <div class="space-y-3 text-sm">
                    <p class="text-muted">{{ __('transaction.detail.seller_offered_notice') }}</p>
                    <div class="flex items-baseline justify-between border-t border-muted/15 pt-3">
                        <span class="text-muted">{{ __('transaction.detail.price_label') }}</span>
                        <x-ui.money :amount="$breakdown['price']" />
                    </div>
                    @if ($breakdown['commissionPct'] !== null)
                        <div class="flex items-baseline justify-between">
                            <span class="text-muted">{{ __('transaction.detail.commission_label', ['pct' => $breakdown['commissionPct']]) }}</span>
                            <x-ui.money :amount="$breakdown['commission']" />
                        </div>
                    @else
                        <x-ui.alert variant="info" :message="__('transaction.detail.no_commission')" />
                    @endif
                    <div class="flex items-baseline justify-between border-t border-muted/15 pt-3">
                        <span class="font-medium text-text">{{ __('transaction.detail.seller_receives_label') }}</span>
                        <span class="text-xl font-bold text-primary"><x-ui.money :amount="$breakdown['sellerNet']" /></span>
                    </div>
                </div>

                <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                    <x-ui.button
                        type="button"
                        variant="primary"
                        class="w-full sm:w-auto"
                        @click="$dispatch('open-modal', 'accept-offer-modal')"
                        wire:loading.attr="disabled"
                    >
                        {{ __('transaction.detail.accept') }}
                    </x-ui.button>
                    <x-ui.button
                        type="button"
                        variant="secondary"
                        class="w-full sm:w-auto"
                        @click="$dispatch('open-modal', 'reject-offer-modal')"
                        wire:loading.attr="disabled"
                    >
                        {{ __('transaction.detail.reject') }}
                    </x-ui.button>
                </div>
            </x-ui.card>
        @endif

        @if ($isBuyer && $transaction->status->value === 'offered')
            <x-ui.alert variant="info" :message="__('transaction.detail.buyer_offered_notice')" />

            @if ($canCancel)
                <x-ui.button
                    type="button"
                    variant="secondary"
                    @click="$dispatch('open-modal', 'cancel-modal')"
                    wire:loading.attr="disabled"
                >
                    {{ __('transaction.detail.cancel') }}
                </x-ui.button>
            @endif
        @endif

        @if ($isBuyer && $transaction->status->value === 'accepted')
            @if ($payBlockedByJob)
                <x-ui.alert variant="info" :message="__('transaction.detail.pay_waiting_job')" />
            @elseif ($canPay)
                <x-ui.button
                    type="button"
                    variant="primary"
                    wire:click="pay"
                    wire:loading.attr="disabled"
                    wire:target="pay"
                >
                    <span wire:loading.remove wire:target="pay">
                        {{ __('transaction.detail.pay') }}
                    </span>
                    <span wire:loading wire:target="pay">
                        {{ __('transaction.detail.paying') }}
                    </span>
                </x-ui.button>
            @endif

            @if ($canCancel)
                <x-ui.button
                    type="button"
                    variant="secondary"
                    @click="$dispatch('open-modal', 'cancel-modal')"
                    wire:loading.attr="disabled"
                >
                    {{ __('transaction.detail.cancel') }}
                </x-ui.button>
            @endif
        @endif

        @if ($isSeller && $transaction->status->value === 'accepted' && $canCancel)
            <x-ui.button
                type="button"
                variant="secondary"
                @click="$dispatch('open-modal', 'cancel-modal')"
                wire:loading.attr="disabled"
            >
                {{ __('transaction.detail.cancel') }}
            </x-ui.button>
        @endif

        @if ($canConfirmHandover)
            <x-ui.button
                type="button"
                variant="primary"
                @click="$dispatch('open-modal', 'handover-modal')"
            >
                {{ __('transaction.detail.confirm_handover') }}
            </x-ui.button>
        @endif

        @if ($transaction->status->value === 'paid_held')
            <x-ui.alert variant="info" :message="__('transaction.detail.paid_held_notice')" />

            @if ($showNeedsAttention)
                <x-ui.alert variant="warning" :message="__('transaction.detail.needs_attention_notice')" />
            @endif

            <div class="flex flex-col gap-3 sm:flex-row">
                @if ($canRequestCancellation)
                    <x-ui.button
                        type="button"
                        variant="secondary"
                        @click="$dispatch('open-modal', 'request-cancellation-modal')"
                        wire:loading.attr="disabled"
                    >
                        {{ __('transaction.detail.request_cancellation') }}
                    </x-ui.button>
                @elseif ($isBuyer && $hasCancelRequest)
                    <x-ui.alert variant="info" :message="__('transaction.detail.cancel_request_waiting')" />
                @endif

                @if ($canApproveCancellation)
                    <x-ui.button
                        type="button"
                        variant="primary"
                        @click="$dispatch('open-modal', 'approve-cancellation-modal')"
                        wire:loading.attr="disabled"
                    >
                        {{ __('transaction.detail.approve_cancellation') }}
                    </x-ui.button>
                @endif

                @if ($canCancel && $isSeller)
                    <x-ui.button
                        type="button"
                        variant="danger"
                        @click="$dispatch('open-modal', 'cancel-refund-modal')"
                        wire:loading.attr="disabled"
                    >
                        {{ __('transaction.detail.cancel_refund') }}
                    </x-ui.button>
                @elseif ($canCancel && $isAdmin)
                    <x-ui.button
                        type="button"
                        variant="danger"
                        @click="$dispatch('open-modal', 'cancel-refund-modal')"
                        wire:loading.attr="disabled"
                    >
                        {{ __('transaction.detail.cancel_admin') }}
                    </x-ui.button>
                @endif
            </div>
        @endif

        @if ($transaction->status->value === 'cancelled')
            <x-ui.alert variant="muted" :message="__('transaction.detail.cancelled_notice')" />
        @endif

        @if ($transaction->status->value === 'expired')
            <x-ui.alert variant="muted" :message="__('transaction.detail.expired_notice')" />
        @endif

        @if ($transaction->status->value === 'completed')
            <x-ui.alert variant="success" :message="__('transaction.detail.completed_notice')" />
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
        <x-ui.textarea
            name="cancelReason"
            :label="__('transaction.detail.cancel_reason_label')"
            wire:model="cancelReason"
            rows="3"
        />

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
        <x-ui.textarea
            name="cancelReason"
            :label="__('transaction.detail.cancel_reason_required_label')"
            wire:model="cancelReason"
            rows="3"
            required
        />

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
        <x-ui.textarea
            name="cancelReason"
            :label="__('transaction.detail.cancel_reason_required_label')"
            wire:model="cancelReason"
            rows="3"
            required
        />

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
