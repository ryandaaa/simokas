<div class="ds-sans max-w-2xl mx-auto py-6 sm:py-8 px-4 sm:px-0 space-y-6 pb-12">
    {{-- Header dengan Label SIMULASI --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div class="space-y-2">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-ds-yellow-soft text-ds-amber-ink font-bold text-[10px] uppercase tracking-wider">
                <i aria-hidden="true" class="fa-solid fa-circle-info text-xs"></i>
                <span>{{ __('payment.simulation_label') }}</span>
            </span>
            <h1 class="text-3xl font-extrabold text-ds-ink tracking-tight">{{ __('payment.title') }}</h1>
            <p class="text-xs sm:text-sm text-ds-muted">{{ __('payment.simulation_badge_help') }}</p>
        </div>

        <div>
            @php
                $statusPill = match ($payment->status->color()) {
                    'success' => 'bg-ds-green-soft text-ds-green-ink',
                    'warning' => 'bg-ds-amber-soft text-ds-amber-ink',
                    'danger' => 'bg-ds-red-soft text-ds-red-ink',
                    'primary' => 'bg-ds-blue-soft text-ds-blue-ink',
                    default => 'bg-ds-soft text-ds-muted',
                };
            @endphp
            <span class="text-[10px] px-2.5 py-1 rounded-full font-bold uppercase tracking-wider {{ $statusPill }}">
                {{ $payment->status->label() }}
            </span>
        </div>
    </div>

    {{-- Pesan Status / Galat --}}
    @if ($errorMessage)
        <div role="alert" class="p-4 rounded-2xl border border-ds-red-soft bg-ds-red-soft/40 text-xs font-semibold text-ds-ink">
            {{ $errorMessage }}
        </div>
    @endif

    @if ($successMessage)
        <div role="status" class="p-4 rounded-2xl border border-ds-green-soft bg-ds-green-soft/40 text-xs font-semibold text-ds-ink">
            {{ $successMessage }}
        </div>
    @endif

    {{-- Kartu Ringkasan Tagihan --}}
    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
        <h2 class="text-sm font-extrabold text-ds-ink">{{ __('payment.fields.amount') }}</h2>
        <div class="space-y-4 mt-4">
            <div class="flex items-baseline justify-between border-b border-ds-line/60 pb-4">
                <span class="text-sm text-ds-muted">{{ $payment->purpose->label() }}</span>
                <span class="text-2xl sm:text-3xl font-extrabold text-ds-ink">
                    {{ \App\Support\Money::format($payment->amount) }}
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm pt-2">
                <div>
                    <span class="text-ds-muted block text-xs">{{ __('payment.fields.payment_id') }}</span>
                    <span class="font-mono text-xs text-ds-ink break-all">{{ $payment->id }}</span>
                </div>

                <div>
                    <span class="text-ds-muted block text-xs">{{ __('payment.fields.expires_at') }}</span>
                    <span class="text-xs text-ds-ink font-medium">{{ $payment->expires_at?->translatedFormat('d M Y H:i') }}</span>
                </div>

                @if ($payment->method)
                    <div>
                        <span class="text-ds-muted block text-xs">{{ __('payment.fields.method') }}</span>
                        <span class="text-xs text-ds-ink font-medium">{{ $payment->method->label() }}</span>
                    </div>
                @endif

                @if ($payment->paid_at)
                    <div>
                        <span class="text-ds-muted block text-xs">{{ __('payment.fields.paid_at') }}</span>
                        <span class="text-xs text-ds-ink font-medium">{{ $payment->paid_at?->translatedFormat('d M Y H:i') }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Formulir Pembayaran Simulasi --}}
    @if ($payment->canBePaid())
        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
            <h2 class="text-sm font-extrabold text-ds-ink">{{ __('payment.fields.method') }}</h2>
            <form wire:submit.prevent="$dispatch('open-modal', 'confirm-payment')" class="space-y-6 mt-4">
                {{-- Pilihan Metode Pembayaran --}}
                <div class="space-y-3">
                    <span class="block text-sm font-bold text-ds-ink" id="method-label">
                        {{ __('payment.fields.method') }}
                    </span>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3" role="radiogroup" aria-labelledby="method-label">
                        @foreach ($methods as $availableMethod)
                            <label class="relative flex flex-col p-4 border rounded-2xl cursor-pointer transition-all {{ $method === $availableMethod->value ? 'border-ds-blue bg-ds-blue-soft/40 ring-2 ring-ds-blue/20' : 'border-ds-line/80 hover:border-ds-blue' }}">
                                <input
                                    type="radio"
                                    name="method"
                                    value="{{ $availableMethod->value }}"
                                    wire:model.live="method"
                                    class="sr-only"
                                />
                                <span class="font-bold text-sm text-ds-ink">{{ $availableMethod->label() }}</span>
                                <span class="text-xs text-ds-muted mt-1 uppercase">{{ $availableMethod->value }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('method') <span class="text-xs text-ds-danger" role="alert">{{ $message }}</span> @enderror
                </div>

                {{-- Mode Pilih Hasil Bebas (Non-Produksi / Demo) --}}
                @if (! $isProduction)
                    <div class="space-y-3 pt-4 border-t border-ds-line/60">
                        <div class="flex items-center justify-between">
                            <span class="block text-sm font-bold text-ds-ink">
                                {{ __('payment.fields.outcome') }}
                            </span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider bg-ds-soft text-ds-muted">Demo / Test</span>
                        </div>

                        <div class="grid grid-cols-3 gap-3" role="radiogroup" aria-label="{{ __('payment.fields.outcome') }}">
                            @foreach ($outcomes as $availableOutcome)
                                <label class="relative flex flex-col p-3 border rounded-2xl cursor-pointer text-center transition-all {{ $outcome === $availableOutcome->value ? 'border-ds-blue bg-ds-blue-soft/40 ring-2 ring-ds-blue/20' : 'border-ds-line/80 hover:border-ds-blue' }}">
                                    <input
                                        type="radio"
                                        name="outcome"
                                        value="{{ $availableOutcome->value }}"
                                        wire:model.live="outcome"
                                        class="sr-only"
                                    />
                                    <span class="font-bold text-xs sm:text-sm text-ds-ink">{{ $availableOutcome->label() }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('outcome') <span class="text-xs text-ds-danger" role="alert">{{ $message }}</span> @enderror
                    </div>
                @endif

                {{-- Tombol Aksi --}}
                <div class="pt-4 flex flex-col sm:flex-row gap-3">
                    <button
                        type="button"
                        @click="$dispatch('open-modal', 'confirm-payment')"
                        wire:loading.attr="disabled"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md disabled:opacity-60"
                    >
                        {{ __('payment.actions.pay') }}
                        <i aria-hidden="true" class="fa-solid fa-arrow-right text-xs"></i>
                    </button>
                </div>
            </form>
        </div>

        {{-- Modal Konfirmasi Sebelum Bayar --}}
        <x-ui.modal
            name="confirm-payment"
            :title="__('payment.actions.confirm_title')"
            :description="__('payment.actions.confirm_description')"
        >
            <div class="my-4 p-4 rounded-2xl bg-ds-canvas/60 space-y-2 text-sm border border-ds-line/80">
                <div class="flex justify-between">
                    <span class="text-ds-muted">{{ __('payment.fields.amount') }}:</span>
                    <span class="font-extrabold text-ds-ink">{{ \App\Support\Money::format($payment->amount) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-ds-muted">{{ __('payment.fields.method') }}:</span>
                    <span class="font-bold text-ds-ink uppercase">{{ $method }}</span>
                </div>
                @if (! $isProduction)
                    <div class="flex justify-between">
                        <span class="text-ds-muted">{{ __('payment.fields.outcome') }}:</span>
                        <span class="font-bold text-ds-ink uppercase">{{ $outcome }}</span>
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
        <div role="status" class="p-4 rounded-2xl border border-ds-green-soft bg-ds-green-soft/40 text-xs font-semibold text-ds-ink">
            {{ __('payment.messages.already_paid') }}
        </div>
    @elseif ($payment->status === \App\Domains\Payment\Enums\PaymentStatus::Failed)
        <div class="space-y-4">
            <div role="alert" class="p-4 rounded-2xl border border-ds-red-soft bg-ds-red-soft/40 text-xs font-semibold text-ds-ink">
                {{ __('payment.messages.payment_failed') }}
            </div>
            <button type="button" wire:click="retry"
                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs">
                {{ __('payment.actions.retry') }}
            </button>
        </div>
    @endif
</div>
