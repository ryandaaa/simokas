<x-layouts.app :title="__('ui.errors.503_title')">
    <div class="ds-sans min-h-[60vh] flex items-center justify-center py-12">
        <div class="max-w-md w-full text-center px-4">
            <div class="w-16 h-16 rounded-2xl bg-ds-blue-soft text-ds-blue-ink flex items-center justify-center mx-auto mb-4 text-2xl">
                <i aria-hidden="true" class="fa-regular fa-clock"></i>
            </div>

            <p class="text-xs font-extrabold text-ds-blue uppercase tracking-wider mb-1">{{ __('ui.errors.code', ['code' => 503]) }}</p>
            <h1 class="text-2xl font-extrabold text-ds-ink mb-2">{{ __('ui.errors.503_title') }}</h1>
            <p class="text-sm text-ds-muted mb-6">{{ __('ui.errors.503_message') }}</p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="{{ url('/') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md">
                    <i aria-hidden="true" class="fa-solid fa-rotate-right text-xs"></i>
                    {{ __('ui.actions.retry') }}
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
