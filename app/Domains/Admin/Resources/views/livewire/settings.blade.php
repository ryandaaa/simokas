<div class="max-w-4xl mx-auto py-6 space-y-6">
    {{-- Header & Last Updated Info --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-text">
                {{ __('admin.settings.title') }}
            </h1>
            <p class="mt-1 text-sm text-muted">
                {{ __('admin.settings.subtitle') }}
            </p>
        </div>

        <div class="p-3 rounded-xl border border-muted/20 bg-surface text-xs text-muted">
            <div class="font-medium text-text">
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
        <x-ui.alert variant="success" :message="$successMessage" />
    @endif

    @if ($errorMessage)
        <x-ui.alert variant="danger" :message="$errorMessage" />
    @endif

    {{-- Form --}}
    <form wire:submit.prevent="confirmSave" class="space-y-6">
        {{-- Section 1: Tarif Inspeksi & Biaya Akses --}}
        <x-ui.card :title="__('admin.settings.section_fees')">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <x-ui.input
                        id="inspection_fee_car"
                        name="form.inspection_fee_car"
                        type="number"
                        wire:model="form.inspection_fee_car"
                        :label="__('setting.keys.inspection_fee_car')"
                        :hint="__('setting.hints.inspection_fee_car')"
                        :error="$errors->first('form.inspection_fee_car')"
                        required
                    />
                </div>

                <div>
                    <x-ui.input
                        id="inspection_fee_motorcycle"
                        name="form.inspection_fee_motorcycle"
                        type="number"
                        wire:model="form.inspection_fee_motorcycle"
                        :label="__('setting.keys.inspection_fee_motorcycle')"
                        :hint="__('setting.hints.inspection_fee_motorcycle')"
                        :error="$errors->first('form.inspection_fee_motorcycle')"
                        required
                    />
                </div>
            </div>
        </x-ui.card>

        {{-- Section 2: Persentase Komisi --}}
        <x-ui.card :title="__('admin.settings.section_commissions')">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <x-ui.input
                        id="platform_fee_pct"
                        name="form.platform_fee_pct"
                        type="number"
                        wire:model="form.platform_fee_pct"
                        :label="__('setting.keys.platform_fee_pct')"
                        :hint="__('setting.hints.platform_fee_pct')"
                        :error="$errors->first('form.platform_fee_pct')"
                        required
                    />
                </div>

                <div>
                    <x-ui.input
                        id="sale_commission_pct"
                        name="form.sale_commission_pct"
                        type="number"
                        wire:model="form.sale_commission_pct"
                        :label="__('setting.keys.sale_commission_pct')"
                        :hint="__('setting.hints.sale_commission_pct')"
                        :error="$errors->first('form.sale_commission_pct')"
                        required
                    />
                </div>
            </div>
        </x-ui.card>

        {{-- Section 3: Parameter Waktu Operasional --}}
        <x-ui.card :title="__('admin.settings.section_timeouts')">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <x-ui.input
                        id="auto_accept_days"
                        name="form.auto_accept_days"
                        type="number"
                        wire:model="form.auto_accept_days"
                        :label="__('setting.keys.auto_accept_days')"
                        :hint="__('setting.hints.auto_accept_days')"
                        :error="$errors->first('form.auto_accept_days')"
                        required
                    />
                </div>

                <div>
                    <x-ui.input
                        id="paid_job_timeout_days"
                        name="form.paid_job_timeout_days"
                        type="number"
                        wire:model="form.paid_job_timeout_days"
                        :label="__('setting.keys.paid_job_timeout_days')"
                        :hint="__('setting.hints.paid_job_timeout_days')"
                        :error="$errors->first('form.paid_job_timeout_days')"
                        required
                    />
                </div>
            </div>
        </x-ui.card>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3 pt-2">
            <x-ui.button
                type="submit"
                variant="primary"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="confirmSave">
                    {{ __('admin.settings.save') }}
                </span>
                <span wire:loading wire:target="confirmSave">
                    {{ __('ui.actions.loading') }}
                </span>
            </x-ui.button>
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
