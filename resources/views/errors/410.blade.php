<x-layouts.app :title="__('ui.errors.410_title')">
    <div class="min-h-[60vh] flex items-center justify-center py-12">
        <div class="max-w-md w-full text-center px-4">
            <div class="w-16 h-16 rounded-full bg-warning/10 text-warning flex items-center justify-center mx-auto mb-4">
                <x-ui.icon name="jam" class="w-8 h-8" />
            </div>

            <p class="text-xs font-bold text-warning uppercase tracking-wider mb-1">Galat 410</p>
            <h1 class="text-2xl font-bold text-text mb-2">{{ __('ui.errors.410_title') }}</h1>
            <p class="text-sm text-muted mb-6">{{ __('ui.errors.410_message') }}</p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <x-ui.button :href="\Illuminate\Support\Facades\Route::has('jobs.mine') ? route('jobs.mine') : url('/')" variant="primary">
                    {{ __('ui.errors.410_button_mine') }}
                </x-ui.button>
                <x-ui.button :href="url('/')" variant="secondary" icon="rumah">
                    {{ __('ui.errors.410_button_home') }}
                </x-ui.button>
            </div>
        </div>
    </div>
</x-layouts.app>
