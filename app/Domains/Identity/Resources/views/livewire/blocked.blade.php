<div class="max-w-md mx-auto py-12">
    <x-ui.card :title="__('identity.blocked.title')">
        <div class="space-y-4">
            <x-ui.alert variant="danger" :title="__('identity.blocked.title')" :message="__('identity.blocked.message')" />

            <div class="pt-4 flex flex-col gap-2">
                <x-ui.button
                    wire:click="logout"
                    variant="secondary"
                    class="w-full justify-center"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="logout">{{ __('identity.blocked.logout') }}</span>
                    <span wire:loading wire:target="logout">{{ __('ui.actions.loading') }}</span>
                </x-ui.button>
            </div>
        </div>
    </x-ui.card>
</div>
