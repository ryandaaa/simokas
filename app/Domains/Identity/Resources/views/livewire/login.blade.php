<div class="max-w-md mx-auto py-6">
    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs overflow-hidden">
        <div class="p-6 sm:p-8">
            <div class="text-center space-y-2 mb-6">
                <span class="inline-flex w-14 h-14 rounded-2xl bg-ds-blue text-white items-center justify-center text-xl shadow-lg shadow-ds-blue/20">
                    <i aria-hidden="true" class="fa-solid fa-right-to-bracket"></i>
                </span>
                <h1 class="text-2xl font-extrabold text-ds-ink tracking-tight">{{ __('identity.login.title') }}</h1>
                <p class="text-sm text-ds-muted">{{ __('identity.login.subtitle') }}</p>
            </div>

            @if (session('status'))
                <div role="status" class="mb-4 p-4 rounded-2xl border border-ds-green-soft bg-ds-green-soft/40 text-xs font-semibold text-ds-ink">
                    {{ session('status') }}
                </div>
            @endif

            <form wire:submit.prevent="login" class="space-y-4">
                <div class="w-full">
                    <label for="email" class="block text-xs font-bold text-ds-ink mb-1.5">
                        {{ __('identity.login.email') }}
                        <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                        <span class="sr-only">(wajib diisi)</span>
                    </label>
                    <input type="email" id="email" name="email" wire:model="email" required
                        class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                    @error('email')
                        <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="w-full">
                    <label for="password" class="block text-xs font-bold text-ds-ink mb-1.5">
                        {{ __('identity.login.password') }}
                        <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                        <span class="sr-only">(wajib diisi)</span>
                    </label>
                    <input type="password" id="password" name="password" wire:model="password" required
                        class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                    @error('password')
                        <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between">
                    <label for="remember" class="inline-flex items-center gap-2 text-sm text-ds-muted cursor-pointer">
                        <input type="checkbox" id="remember" name="remember" wire:model="remember"
                            class="w-4 h-4 rounded border-ds-line text-ds-blue focus:ring-ds-blue/30">
                        {{ __('identity.login.remember') }}
                    </label>

                    <a href="{{ route('identity.forgot-password') }}" class="text-sm font-bold text-ds-blue hover:text-ds-blue-ink focus:outline-none focus:ring-2 focus:ring-ds-blue rounded">
                        {{ __('identity.login.forgot_password') }}
                    </a>
                </div>

                <div class="pt-2">
                    <button type="submit" wire:loading.attr="disabled"
                        class="w-full inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md disabled:opacity-60">
                        <span wire:loading.remove wire:target="login">{{ __('identity.login.submit') }}</span>
                        <span wire:loading wire:target="login">{{ __('ui.actions.loading') }}</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="px-6 sm:px-8 py-4 bg-ds-canvas/80 border-t border-ds-line/60">
            <p class="text-sm text-center text-ds-muted">
                {{ __('identity.login.no_account') }}
                <a href="{{ route('identity.register') }}" class="font-bold text-ds-blue hover:text-ds-blue-ink focus:outline-none focus:ring-2 focus:ring-ds-blue rounded">
                    {{ __('identity.login.register_here') }}
                </a>
            </p>
        </div>
    </div>
</div>
