<div class="max-w-md mx-auto py-6">
    <x-ui.card :title="__('identity.login.title')" :subtitle="__('identity.login.subtitle')">
        @if (session('status'))
            <div class="mb-4">
                <x-ui.alert variant="success" :message="session('status')" />
            </div>
        @endif

        <form wire:submit.prevent="login" class="space-y-4">
            <x-ui.input
                id="email"
                name="email"
                type="email"
                :label="__('identity.login.email')"
                wire:model="email"
                :error="$errors->first('email')"
                required
            />

            <x-ui.input
                id="password"
                name="password"
                type="password"
                :label="__('identity.login.password')"
                wire:model="password"
                :error="$errors->first('password')"
                required
            />

            <div class="flex items-center justify-between">
                <x-ui.checkbox
                    id="remember"
                    name="remember"
                    :label="__('identity.login.remember')"
                    wire:model="remember"
                />

                <a href="{{ route('identity.forgot-password') }}" class="text-sm font-semibold text-primary underline focus:outline-none focus:ring-2 focus:ring-accent rounded">
                    {{ __('identity.login.forgot_password') }}
                </a>
            </div>

            <div class="pt-2">
                <x-ui.button
                    type="submit"
                    variant="primary"
                    class="w-full justify-center"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="login">{{ __('identity.login.submit') }}</span>
                    <span wire:loading wire:target="login">{{ __('ui.actions.loading') }}</span>
                </x-ui.button>
            </div>
        </form>

        <x-slot:footer>
            <p class="text-sm text-center text-muted">
                {{ __('identity.login.no_account') }}
                <a href="{{ route('identity.register') }}" class="font-semibold text-primary underline focus:outline-none focus:ring-2 focus:ring-accent rounded">
                    {{ __('identity.login.register_here') }}
                </a>
            </p>
        </x-slot:footer>
    </x-ui.card>
</div>
