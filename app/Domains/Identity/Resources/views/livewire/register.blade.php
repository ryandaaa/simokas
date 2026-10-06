<div class="max-w-md mx-auto py-6">
    <x-ui.card :title="__('identity.register.title')" :subtitle="__('identity.register.subtitle')">
        @if ($errors->has('email') && str_contains($errors->first('email'), __('identity.register.duplicate_email')))
            <div class="mb-4">
                <x-ui.alert variant="warning" :title="__('identity.register.duplicate_email')">
                    <div class="mt-2 flex gap-4">
                        <a href="{{ route('login') }}" class="text-sm font-semibold text-primary underline focus:outline-none focus:ring-2 focus:ring-accent rounded">
                            {{ __('identity.register.duplicate_email_login') }}
                        </a>
                        <a href="{{ route('identity.forgot-password') }}" class="text-sm font-semibold text-primary underline focus:outline-none focus:ring-2 focus:ring-accent rounded">
                            {{ __('identity.register.duplicate_email_reset') }}
                        </a>
                    </div>
                </x-ui.alert>
            </div>
        @endif

        <form wire:submit.prevent="register" class="space-y-4">
            <x-ui.select
                id="account_type"
                name="account_type"
                :label="__('identity.register.account_type')"
                wire:model="account_type"
                :options="[
                    'user' => __('identity.register.account_type_user'),
                    'inspector' => __('identity.register.account_type_inspector'),
                ]"
                :error="$errors->first('account_type')"
                required
            />

            <x-ui.input
                id="name"
                name="name"
                type="text"
                :label="__('identity.register.name')"
                :hint="__('identity.register.name_hint')"
                wire:model="name"
                :error="$errors->first('name')"
                required
            />

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
                id="phone"
                name="phone"
                type="tel"
                :label="__('identity.register.phone')"
                :hint="__('identity.register.phone_hint')"
                wire:model="phone"
                :error="$errors->first('phone')"
                required
            />

            <x-ui.input
                id="password"
                name="password"
                type="password"
                :label="__('identity.register.password')"
                :hint="__('identity.register.password_hint')"
                wire:model="password"
                :error="$errors->first('password')"
                required
            />

            <x-ui.input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                :label="__('identity.register.password_confirmation')"
                wire:model="password_confirmation"
                :error="$errors->first('password_confirmation')"
                required
            />

            <x-ui.checkbox
                id="accepted_terms"
                name="accepted_terms"
                wire:model="accepted_terms"
                :error="$errors->first('accepted_terms')"
                required
            >
                <span class="font-medium text-text">
                    {{ __('identity.register.terms_prefix') }}
                    <a href="{{ route('legal.terms') }}" class="text-primary underline focus:outline-none focus:ring-2 focus:ring-accent rounded">{{ __('legal.footer_terms') }}</a>
                    {{ __('identity.register.terms_and') }}
                    <a href="{{ route('legal.privacy') }}" class="text-primary underline focus:outline-none focus:ring-2 focus:ring-accent rounded">{{ __('legal.footer_privacy') }}</a>
                    <span class="text-danger ml-0.5" aria-hidden="true">*</span>
                    <span class="sr-only">(wajib)</span>
                </span>
            </x-ui.checkbox>

            <div class="pt-2">
                <x-ui.button
                    type="submit"
                    variant="primary"
                    class="w-full justify-center"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="register">{{ __('identity.register.submit') }}</span>
                    <span wire:loading wire:target="register">{{ __('ui.actions.loading') }}</span>
                </x-ui.button>
            </div>
        </form>

        <x-slot:footer>
            <p class="text-sm text-center text-muted">
                {{ __('identity.register.already_registered') }}
                <a href="{{ route('login') }}" class="font-semibold text-primary underline focus:outline-none focus:ring-2 focus:ring-accent rounded">
                    {{ __('identity.register.login_here') }}
                </a>
            </p>
        </x-slot:footer>
    </x-ui.card>
</div>
