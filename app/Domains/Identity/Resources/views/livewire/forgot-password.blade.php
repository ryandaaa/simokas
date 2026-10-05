<div class="max-w-md mx-auto py-6">
    <x-ui.card :title="__('identity.forgot_password.title')" :subtitle="__('identity.forgot_password.subtitle')">
        @if ($status)
            <div class="mb-4">
                <x-ui.alert variant="info" :message="$status" />
            </div>
        @endif

        <form wire:submit.prevent="sendResetLink" class="space-y-4">
            <x-ui.input
                id="email"
                name="email"
                type="email"
                :label="__('identity.forgot_password.email')"
                wire:model="email"
                :error="$errors->first('email')"
                required
            />

            <div class="pt-2">
                <x-ui.button
                    type="submit"
                    variant="primary"
                    class="w-full justify-center"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="sendResetLink">{{ __('identity.forgot_password.submit') }}</span>
                    <span wire:loading wire:target="sendResetLink">{{ __('ui.actions.loading') }}</span>
                </x-ui.button>
            </div>
        </form>

        <x-slot:footer>
            <p class="text-sm text-center text-muted">
                <a href="{{ route('login') }}" class="font-semibold text-primary underline focus:outline-none focus:ring-2 focus:ring-accent rounded">
                    {{ __('identity.forgot_password.back_to_login') }}
                </a>
            </p>
        </x-slot:footer>
    </x-ui.card>
</div>
