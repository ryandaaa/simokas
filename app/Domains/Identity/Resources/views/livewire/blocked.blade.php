<div class="max-w-md mx-auto py-12">
    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs overflow-hidden">
        <div class="p-6 sm:p-8">
            <div class="text-center space-y-2 mb-6">
                <span class="inline-flex w-14 h-14 rounded-2xl bg-ds-danger text-white items-center justify-center text-xl shadow-lg shadow-ds-danger/20">
                    <i aria-hidden="true" class="fa-solid fa-ban"></i>
                </span>
                <h1 class="text-2xl font-extrabold text-ds-ink tracking-tight">{{ __('identity.blocked.title') }}</h1>
            </div>

            <div role="alert" class="p-4 rounded-2xl border border-ds-red-soft bg-ds-red-soft/40">
                <p class="text-xs font-extrabold text-ds-ink">{{ __('identity.blocked.title') }}</p>
                <p class="mt-1 text-xs text-ds-muted leading-relaxed">{{ __('identity.blocked.message') }}</p>
            </div>

            <div class="pt-4 flex flex-col gap-2">
                <button type="button"
                    wire:click="logout"
                    wire:loading.attr="disabled"
                    class="w-full inline-flex items-center justify-center gap-2 px-7 py-3 bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-sm rounded-xl transition-all shadow-xs disabled:opacity-60">
                    <span wire:loading.remove wire:target="logout">{{ __('identity.blocked.logout') }}</span>
                    <span wire:loading wire:target="logout">{{ __('ui.actions.loading') }}</span>
                </button>
            </div>
        </div>
    </div>
</div>
