<div class="max-w-4xl mx-auto py-6 space-y-6">
    <x-ui.card :title="__('identity.dashboard.welcome', ['name' => $user->name])" :subtitle="__('identity.dashboard.admin_title')">
        <div class="space-y-4">
            <x-ui.alert variant="info" :message="__('identity.dashboard.notice')" />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div class="p-4 rounded-xl border border-muted/20 bg-bg">
                    <p class="text-xs font-medium text-muted uppercase tracking-wider mb-1">
                        {{ __('identity.dashboard.role_label') }}
                    </p>
                    <div class="mt-1">
                        <x-ui.status-chip
                            :label="$user->role->label()"
                            :variant="$user->role->color()"
                            :icon="$user->role->icon()"
                        />
                    </div>
                </div>

                <div class="p-4 rounded-xl border border-muted/20 bg-bg">
                    <p class="text-xs font-medium text-muted uppercase tracking-wider mb-1">
                        {{ __('identity.dashboard.status_label') }}
                    </p>
                    <div class="mt-1">
                        <x-ui.status-chip
                            :label="$user->status->label()"
                            :variant="$user->status->color()"
                            :icon="$user->status->icon()"
                        />
                    </div>
                </div>
            </div>

            <div class="pt-4 flex flex-wrap justify-end gap-3">
                <x-ui.button
                    :href="route('admin.settings')"
                    variant="primary"
                >
                    {{ __('admin.settings.title') }}
                </x-ui.button>

                <x-ui.button
                    :href="route('identity.account')"
                    variant="secondary"
                >
                    {{ __('identity.account.title') }}
                </x-ui.button>

                <x-ui.button
                    wire:click="logout"
                    variant="secondary"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="logout">{{ __('identity.dashboard.logout') }}</span>
                    <span wire:loading wire:target="logout">{{ __('ui.actions.loading') }}</span>
                </x-ui.button>
            </div>
        </div>
    </x-ui.card>
</div>
