<div class="ds-sans max-w-4xl mx-auto py-6 space-y-8 pb-12">
    {{-- Header & Last Updated Info --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div class="space-y-2">
            <span class="text-ds-blue text-xs font-extrabold uppercase tracking-wider">{{ __('admin.settings.title') }}</span>
            <h1 class="text-3xl font-extrabold text-ds-ink tracking-tight">
                {{ __('admin.settings.title') }}
            </h1>
            <p class="text-sm text-ds-muted">
                {{ __('admin.settings.subtitle') }}
            </p>
        </div>

        <div class="p-4 rounded-2xl border border-ds-line/80 bg-white shadow-xs text-xs text-ds-muted">
            <div class="font-extrabold text-ds-ink">
                {{ __('admin.settings.title') }}
            </div>
            <div class="mt-0.5">
                @if ($updatedByName && $updatedAtFormatted)
                    {{ __('admin.settings.last_updated', ['name' => $updatedByName, 'time' => $updatedAtFormatted]) }}
                @else
                    {{ __('admin.settings.never_updated') }}
                @endif
            </div>
        </div>
    </div>

    {{-- Flash Alerts --}}
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

    {{-- Form --}}
    <form wire:submit.prevent="confirmSave" class="space-y-6">
        {{-- Section 1: Tarif Inspeksi & Biaya Akses --}}
        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
            <h2 class="text-lg font-extrabold text-ds-ink">{{ __('admin.settings.section_fees') }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-4">
                <div class="w-full">
                    <label for="inspection_fee_car" class="block text-xs font-bold text-ds-ink mb-1.5">
                        {{ __('setting.keys.inspection_fee_car') }}
                        <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                        <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                    </label>
                    <input type="number" id="inspection_fee_car" name="form.inspection_fee_car" wire:model="form.inspection_fee_car" required
                        class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                    <p class="text-xs text-ds-faint mt-1.5">{{ __('setting.hints.inspection_fee_car') }}</p>
                    @error('form.inspection_fee_car')
                        <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="w-full">
                    <label for="inspection_fee_motorcycle" class="block text-xs font-bold text-ds-ink mb-1.5">
                        {{ __('setting.keys.inspection_fee_motorcycle') }}
                        <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                        <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                    </label>
                    <input type="number" id="inspection_fee_motorcycle" name="form.inspection_fee_motorcycle" wire:model="form.inspection_fee_motorcycle" required
                        class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                    <p class="text-xs text-ds-faint mt-1.5">{{ __('setting.hints.inspection_fee_motorcycle') }}</p>
                    @error('form.inspection_fee_motorcycle')
                        <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Section 2: Persentase Komisi --}}
        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
            <h2 class="text-lg font-extrabold text-ds-ink">{{ __('admin.settings.section_commissions') }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-4">
                <div class="w-full">
                    <label for="platform_fee_pct" class="block text-xs font-bold text-ds-ink mb-1.5">
                        {{ __('setting.keys.platform_fee_pct') }}
                        <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                        <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                    </label>
                    <input type="number" id="platform_fee_pct" name="form.platform_fee_pct" wire:model="form.platform_fee_pct" required
                        class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                    <p class="text-xs text-ds-faint mt-1.5">{{ __('setting.hints.platform_fee_pct') }}</p>
                    @error('form.platform_fee_pct')
                        <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="w-full">
                    <label for="sale_commission_pct" class="block text-xs font-bold text-ds-ink mb-1.5">
                        {{ __('setting.keys.sale_commission_pct') }}
                        <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                        <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                    </label>
                    <input type="number" id="sale_commission_pct" name="form.sale_commission_pct" wire:model="form.sale_commission_pct" required
                        class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                    <p class="text-xs text-ds-faint mt-1.5">{{ __('setting.hints.sale_commission_pct') }}</p>
                    @error('form.sale_commission_pct')
                        <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Section 3: Parameter Waktu Operasional --}}
        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
            <h2 class="text-lg font-extrabold text-ds-ink">{{ __('admin.settings.section_timeouts') }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-4">
                <div class="w-full">
                    <label for="auto_accept_days" class="block text-xs font-bold text-ds-ink mb-1.5">
                        {{ __('setting.keys.auto_accept_days') }}
                        <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                        <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                    </label>
                    <input type="number" id="auto_accept_days" name="form.auto_accept_days" wire:model="form.auto_accept_days" required
                        class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                    <p class="text-xs text-ds-faint mt-1.5">{{ __('setting.hints.auto_accept_days') }}</p>
                    @error('form.auto_accept_days')
                        <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="w-full">
                    <label for="paid_job_timeout_days" class="block text-xs font-bold text-ds-ink mb-1.5">
                        {{ __('setting.keys.paid_job_timeout_days') }}
                        <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                        <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                    </label>
                    <input type="number" id="paid_job_timeout_days" name="form.paid_job_timeout_days" wire:model="form.paid_job_timeout_days" required
                        class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                    <p class="text-xs text-ds-faint mt-1.5">{{ __('setting.hints.paid_job_timeout_days') }}</p>
                    @error('form.paid_job_timeout_days')
                        <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3 pt-2">
            <button
                type="submit"
                wire:loading.attr="disabled"
                class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="confirmSave">
                    {{ __('admin.settings.save') }}
                </span>
                <span wire:loading wire:target="confirmSave">
                    {{ __('ui.actions.loading') }}
                </span>
            </button>
        </div>
    </form>

    {{-- Confirmation Modal --}}
    <x-ui.modal
        name="confirm-save-settings"
        :title="__('admin.settings.confirm_title')"
        :description="__('admin.settings.confirm_description')"
    >
        <x-slot:footer>
            <x-ui.button
                type="button"
                variant="secondary"
                wire:click="closeConfirmModal"
            >
                {{ __('admin.settings.cancel_button') }}
            </x-ui.button>

            <x-ui.button
                type="button"
                variant="primary"
                wire:click="save"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="save">
                    {{ __('admin.settings.confirm_button') }}
                </span>
                <span wire:loading wire:target="save">
                    {{ __('ui.actions.loading') }}
                </span>
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
