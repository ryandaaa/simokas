<div class="max-w-md mx-auto py-6">
    <x-ui.card :title="__('identity.reset_password.title')" :subtitle="__('identity.reset_password.subtitle')">
        @if ($errorMessage)
            <div class="mb-4">
                <x-ui.alert variant="danger" :message="$errorMessage">
                    <div class="mt-2">
                        <a href="{{ route('identity.forgot-password') }}" class="text-sm font-semibold text-primary underline focus:outline-none focus:ring-2 focus:ring-accent rounded">
                            {{ __('identity.reset_password.request_new_link') }}
                        </a>
                    </div>
                </x-ui.alert>
            </div>
        @endif

        <form wire:submit.prevent="resetPassword" class="space-y-4">
            <x-ui.input
                id="email"
                name="email"
                type="email"
                :label="__('identity.register.email')"
                wire:model="email"
                :error="$errors->first('email')"
                required
            />

            <x-ui.input
                id="password"
                name="password"
                type="password"
                :label="__('identity.reset_password.password')"
                :hint="__('identity.reset_password.password_hint')"
                wire:model="password"
                :error="$errors->first('password')"
                required
            />

            <x-ui.input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                :label="__('identity.reset_password.password_confirmation')"
                wire:model="password_confirmation"
                :error="$errors->first('password_confirmation')"
                required
            />

            <div class="pt-2">
                <x-ui.button
                    type="submit"
                    variant="primary"
                    class="w-full justify-center"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="resetPassword">{{ __('identity.reset_password.submit') }}</span>
                    <span wire:loading wire:target="resetPassword">{{ __('ui.actions.loading') }}</span>
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
