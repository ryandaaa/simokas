<x-layouts.app :title="__('ui.errors.429_title')">
    <div class="ds-sans min-h-[60vh] flex items-center justify-center py-12">
        <div class="max-w-md w-full text-center px-4">
            <div class="w-16 h-16 rounded-2xl bg-ds-amber-soft text-ds-amber-ink flex items-center justify-center mx-auto mb-4 text-2xl">
                <i aria-hidden="true" class="fa-solid fa-hourglass-half"></i>
            </div>

            <p class="text-xs font-extrabold text-ds-amber-ink uppercase tracking-wider mb-1">{{ __('ui.errors.code', ['code' => 429]) }}</p>
            <h1 class="text-2xl font-extrabold text-ds-ink mb-2">{{ __('ui.errors.429_title') }}</h1>
            <p class="text-sm text-ds-muted mb-6">{{ __('ui.errors.429_message') }}</p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="{{ url('/') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md">
                    <i aria-hidden="true" class="fa-solid fa-house text-xs"></i>
                    {{ __('ui.layout.home') }}
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
