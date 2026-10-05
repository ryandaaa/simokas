<x-layouts.app :title="__('ui.errors.404_title')">
    <div class="min-h-[60vh] flex items-center justify-center py-12">
        <div class="max-w-md w-full text-center px-4">
            <div class="w-16 h-16 rounded-full bg-primary/10 text-primary flex items-center justify-center mx-auto mb-4">
                <x-ui.icon name="kaca-pembesar" class="w-8 h-8" />
            </div>

            <p class="text-xs font-bold text-primary uppercase tracking-wider mb-1">Galat 404</p>
            <h1 class="text-2xl font-bold text-text mb-2">{{ __('ui.errors.404_title') }}</h1>
            <p class="text-sm text-muted mb-6">{{ __('ui.errors.404_message') }}</p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <x-ui.button :href="url('/')" variant="primary" icon="rumah">
                    {{ __('ui.layout.home') }}
                </x-ui.button>
            </div>
        </div>
    </div>
</x-layouts.app>
