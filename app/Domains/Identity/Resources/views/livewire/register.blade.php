<div class="max-w-md mx-auto py-6">
    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs overflow-hidden">
        <div class="p-6 sm:p-8">
            <div class="text-center space-y-2 mb-6">
                <span class="inline-flex w-14 h-14 rounded-2xl bg-ds-blue text-white items-center justify-center text-xl shadow-lg shadow-ds-blue/20">
                    <i aria-hidden="true" class="fa-solid fa-user-plus"></i>
                </span>
                <h1 class="text-2xl font-extrabold text-ds-ink tracking-tight">{{ __('identity.register.title') }}</h1>
                <p class="text-sm text-ds-muted">{{ __('identity.register.subtitle') }}</p>
            </div>

            @if ($errors->has('email') && str_contains($errors->first('email'), __('identity.register.duplicate_email')))
                <div role="alert" class="mb-4 p-4 rounded-2xl border border-ds-amber-soft bg-ds-amber-soft/40">
                    <p class="text-xs font-extrabold text-ds-ink">{{ __('identity.register.duplicate_email') }}</p>
                    <div class="mt-2 flex gap-4">
                        <a href="{{ route('login') }}" class="text-sm font-bold text-ds-blue hover:text-ds-blue-ink focus:outline-none focus:ring-2 focus:ring-ds-blue rounded">
                            {{ __('identity.register.duplicate_email_login') }}
                        </a>
                        <a href="{{ route('identity.forgot-password') }}" class="text-sm font-bold text-ds-blue hover:text-ds-blue-ink focus:outline-none focus:ring-2 focus:ring-ds-blue rounded">
                            {{ __('identity.register.duplicate_email_reset') }}
                        </a>
                    </div>
                </div>
            @endif

            <form wire:submit.prevent="register" class="space-y-4">
                <div class="w-full">
                    <label for="account_type" class="block text-xs font-bold text-ds-ink mb-1.5">
                        {{ __('identity.register.account_type') }}
                        <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                        <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                    </label>
                    <select id="account_type" name="account_type" wire:model="account_type" required
                        class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                        <option value="user">{{ __('identity.register.account_type_user') }}</option>
                        <option value="inspector">{{ __('identity.register.account_type_inspector') }}</option>
                    </select>
                    @error('account_type')
                        <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="w-full">
                    <label for="name" class="block text-xs font-bold text-ds-ink mb-1.5">
                        {{ __('identity.register.name') }}
                        <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                        <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                    </label>
                    <input type="text" id="name" name="name" wire:model="name" required
                        class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                    <p class="text-xs text-ds-faint mt-1.5">{{ __('identity.register.name_hint') }}</p>
                    @error('name')
                        <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="w-full">
                    <label for="email" class="block text-xs font-bold text-ds-ink mb-1.5">
                        {{ __('identity.register.email') }}
                        <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                        <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                    </label>
                    <input type="email" id="email" name="email" wire:model="email" required
                        class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                    @error('email')
                        <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="w-full">
                    <label for="phone" class="block text-xs font-bold text-ds-ink mb-1.5">
                        {{ __('identity.register.phone') }}
                        <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                        <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                    </label>
                    <input type="tel" id="phone" name="phone" wire:model="phone" required
                        class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                    <p class="text-xs text-ds-faint mt-1.5">{{ __('identity.register.phone_hint') }}</p>
                    @error('phone')
                        <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="w-full">
                    <label for="password" class="block text-xs font-bold text-ds-ink mb-1.5">
                        {{ __('identity.register.password') }}
                        <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                        <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                    </label>
                    <input type="password" id="password" name="password" wire:model="password" required
                        class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                    <p class="text-xs text-ds-faint mt-1.5">{{ __('identity.register.password_hint') }}</p>
                    @error('password')
                        <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="w-full">
                    <label for="password_confirmation" class="block text-xs font-bold text-ds-ink mb-1.5">
                        {{ __('identity.register.password_confirmation') }}
                        <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                        <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                    </label>
                    <input type="password" id="password_confirmation" name="password_confirmation" wire:model="password_confirmation" required
                        class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                    @error('password_confirmation')
                        <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="w-full">
                    <label for="accepted_terms" class="flex items-start gap-2 text-sm text-ds-muted cursor-pointer min-h-[44px]">
                        <input type="checkbox" id="accepted_terms" name="accepted_terms" wire:model="accepted_terms" required
                            class="mt-0.5 w-4 h-4 rounded border-ds-line text-ds-blue focus:ring-ds-blue/30">
                        <span>
                            {{ __('identity.register.terms_prefix') }}
                            <a href="{{ route('legal.terms') }}" class="font-bold text-ds-blue hover:text-ds-blue-ink focus:outline-none focus:ring-2 focus:ring-ds-blue rounded">{{ __('legal.footer_terms') }}</a>
                            {{ __('identity.register.terms_and') }}
                            <a href="{{ route('legal.privacy') }}" class="font-bold text-ds-blue hover:text-ds-blue-ink focus:outline-none focus:ring-2 focus:ring-ds-blue rounded">{{ __('legal.footer_privacy') }}</a>
                            <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                            <span class="sr-only">{{ __("ui.actions.required_mark") }}</span>
                        </span>
                    </label>
                    @error('accepted_terms')
                        <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-2">
                    <button type="submit" wire:loading.attr="disabled"
                        class="w-full inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md disabled:opacity-60">
                        <span wire:loading.remove wire:target="register">{{ __('identity.register.submit') }}</span>
                        <span wire:loading wire:target="register">{{ __('ui.actions.loading') }}</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="px-6 sm:px-8 py-4 bg-ds-canvas/80 border-t border-ds-line/60">
            <p class="text-sm text-center text-ds-muted">
                {{ __('identity.register.already_registered') }}
                <a href="{{ route('login') }}" class="font-bold text-ds-blue hover:text-ds-blue-ink focus:outline-none focus:ring-2 focus:ring-ds-blue rounded">
                    {{ __('identity.register.login_here') }}
                </a>
            </p>
        </div>
    </div>
</div>
