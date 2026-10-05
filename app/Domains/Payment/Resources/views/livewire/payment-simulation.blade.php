<div class="max-w-2xl mx-auto py-6 sm:py-8 px-4 sm:px-0">
    {{-- Header dengan Label SIMULASI --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-accent/20 text-accent font-bold text-xs uppercase tracking-wider mb-2">
                <x-ui.icon name="info" class="w-4 h-4" />
                <span>{{ __('payment.simulation_label') }}</span>
            </div>
            <h1 class="text-2xl font-bold text-text">{{ __('payment.title') }}</h1>
            <p class="text-xs sm:text-sm text-muted mt-1">{{ __('payment.simulation_badge_help') }}</p>
        </div>

        <div>
            <x-ui.status-chip
                :label="$payment->status->label()"
                :variant="$payment->status->color()"
                :icon="$payment->status->icon()"
            />
        </div>
    </div>

    {{-- Pesan Status / Galat --}}
    @if ($errorMessage)
        <div class="mb-6">
            <x-ui.alert variant="danger" :message="$errorMessage" />
        </div>
    @endif

    @if ($successMessage)
        <div class="mb-6">
            <x-ui.alert variant="success" :message="$successMessage" />
        </div>
    @endif

    {{-- Kartu Ringkasan Tagihan --}}
    <div class="mb-6">
        <x-ui.card :title="__('payment.fields.amount')">
            <div class="space-y-4">
                <div class="flex items-baseline justify-between border-b border-muted/15 pb-4">
                    <span class="text-sm text-muted">{{ $payment->purpose->label() }}</span>
                    <span class="text-2xl sm:text-3xl font-bold text-primary">
                        <x-ui.money :amount="$payment->amount" />
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm pt-2">
                    <div>
                        <span class="text-muted block text-xs">{{ __('payment.fields.payment_id') }}</span>
                        <span class="font-mono text-xs text-text break-all">{{ $payment->id }}</span>
                    </div>

                    <div>
                        <span class="text-muted block text-xs">{{ __('payment.fields.expires_at') }}</span>
                        <x-ui.datetime :value="$payment->expires_at" class="text-xs text-text font-medium" />
                    </div>

                    @if ($payment->method)
                        <div>
                            <span class="text-muted block text-xs">{{ __('payment.fields.method') }}</span>
                            <span class="text-xs text-text font-medium">{{ $payment->method->label() }}</span>
                        </div>
                    @endif

                    @if ($payment->paid_at)
                        <div>
                            <span class="text-muted block text-xs">{{ __('payment.fields.paid_at') }}</span>
                            <x-ui.datetime :value="$payment->paid_at" class="text-xs text-text font-medium" />
                        </div>
                    @endif
                </div>
            </div>
        </x-ui.card>
    </div>

    {{-- Formulir Pembayaran Simulasi --}}
    @if ($payment->canBePaid())
        <x-ui.card :title="__('payment.fields.method')">
            <form wire:submit.prevent="$dispatch('open-modal', 'confirm-payment')" class="space-y-6">
                {{-- Pilihan Metode Pembayaran --}}
                <div class="space-y-3">
                    <label class="block text-sm font-medium text-text">
                        {{ __('payment.fields.method') }}
                    </label>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        @foreach ($methods as $availableMethod)
                            <label class="relative flex flex-col p-4 border rounded-xl cursor-pointer transition-all {{ $method === $availableMethod->value ? 'border-primary bg-primary/5 ring-2 ring-primary/20' : 'border-muted/20 hover:border-muted/40' }}">
                                <input
                                    type="radio"
                                    name="method"
                                    value="{{ $availableMethod->value }}"
                                    wire:model.live="method"
                                    class="sr-only"
                                />
                                <span class="font-bold text-sm text-text">{{ $availableMethod->label() }}</span>
                                <span class="text-xs text-muted mt-1 uppercase">{{ $availableMethod->value }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('method') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                </div>

                {{-- Mode Pilih Hasil Bebas (Non-Produksi / Demo) --}}
                @if (! $isProduction)
                    <div class="space-y-3 pt-4 border-t border-muted/15">
                        <div class="flex items-center justify-between">
                            <label class="block text-sm font-medium text-text">
                                {{ __('payment.fields.outcome') }}
                            </label>
                            <span class="text-xs text-muted bg-muted/10 px-2 py-0.5 rounded">Demo / Test</span>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            @foreach ($outcomes as $availableOutcome)
                                <label class="relative flex flex-col p-3 border rounded-xl cursor-pointer text-center transition-all {{ $outcome === $availableOutcome->value ? 'border-primary bg-primary/5 ring-2 ring-primary/20' : 'border-muted/20 hover:border-muted/40' }}">
                                    <input
                                        type="radio"
                                        name="outcome"
                                        value="{{ $availableOutcome->value }}"
                                        wire:model.live="outcome"
                                        class="sr-only"
                                    />
                                    <span class="font-medium text-xs sm:text-sm text-text">{{ $availableOutcome->label() }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('outcome') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                    </div>
                @endif

                {{-- Tombol Aksi --}}
                <div class="pt-4 flex flex-col sm:flex-row gap-3">
                    <x-ui.button
                        type="button"
                        variant="primary"
                        class="w-full sm:w-auto"
                        @click="$dispatch('open-modal', 'confirm-payment')"
                        wire:loading.attr="disabled"
                    >
                        {{ __('payment.actions.pay') }}
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>

        {{-- Modal Konfirmasi Sebelum Bayar --}}
        <x-ui.modal
            name="confirm-payment"
            :title="__('payment.actions.confirm_title')"
            :description="__('payment.actions.confirm_description')"
        >
            <div class="my-4 p-4 rounded-lg bg-bg space-y-2 text-sm border border-muted/15">
                <div class="flex justify-between">
                    <span class="text-muted">{{ __('payment.fields.amount') }}:</span>
                    <span class="font-bold text-primary"><x-ui.money :amount="$payment->amount" /></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted">{{ __('payment.fields.method') }}:</span>
                    <span class="font-medium text-text uppercase">{{ $method }}</span>
                </div>
                @if (! $isProduction)
                    <div class="flex justify-between">
                        <span class="text-muted">{{ __('payment.fields.outcome') }}:</span>
                        <span class="font-medium text-text uppercase">{{ $outcome }}</span>
                    </div>
                @endif
            </div>

            <x-slot:footer>
                <x-ui.button
                    type="button"
                    variant="secondary"
                    @click="open = false"
                    wire:loading.attr="disabled"
                >
                    {{ __('ui.actions.cancel') }}
                </x-ui.button>

                <x-ui.button
                    type="button"
                    variant="primary"
                    wire:click="pay"
                    wire:loading.attr="disabled"
                    wire:target="pay"
                >
                    <span wire:loading.remove wire:target="pay">
                        {{ __('payment.actions.confirm_button') }}
                    </span>
                    <span wire:loading wire:target="pay">
                        {{ __('payment.actions.processing') }}
                    </span>
                </x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @elseif ($payment->status === \App\Domains\Payment\Enums\PaymentStatus::Success)
        <x-ui.alert variant="success" :message="__('payment.messages.already_paid')" />
    @elseif ($payment->status === \App\Domains\Payment\Enums\PaymentStatus::Failed)
        <div class="space-y-4">
            <x-ui.alert variant="danger" :message="__('payment.messages.payment_failed')" />
            <x-ui.button type="button" variant="secondary" wire:click="retry">
                {{ __('payment.actions.retry') }}
            </x-ui.button>
        </div>
    @endif
</div>
