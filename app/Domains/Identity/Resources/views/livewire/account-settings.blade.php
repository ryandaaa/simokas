<div class="ds-sans max-w-3xl mx-auto py-6 space-y-8 pb-12">
    {{-- Header navigasi kembali --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 text-xs font-bold text-ds-blue hover:text-ds-blue-ink">
            <i aria-hidden="true" class="fa-solid fa-chevron-left"></i>
            {{ __('identity.account.back_to_dashboard') }}
        </a>
    </div>

    <div class="space-y-2">
        <span class="text-ds-blue text-xs font-extrabold uppercase tracking-wider">{{ __('identity.account.title') }}</span>
        <h1 class="text-3xl font-extrabold text-ds-ink tracking-tight">{{ __('identity.account.title') }}</h1>
        <p class="text-sm text-ds-muted">{{ __('identity.account.subtitle') }}</p>
    </div>

    {{-- Bagian 1: Ubah Profil --}}
    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
        <h2 class="text-lg font-extrabold text-ds-ink">{{ __('identity.account.profile_section_title') }}</h2>
        <p class="text-xs text-ds-muted mt-1 mb-5">{{ __('identity.account.profile_section_subtitle') }}</p>
        @if (session('status_profile'))
            <div role="status" class="mb-4 p-4 rounded-2xl border border-ds-green-soft bg-ds-green-soft/40 text-xs font-semibold text-ds-ink">
                {{ session('status_profile') }}
            </div>
        @endif

        <form wire:submit.prevent="updateProfile" class="space-y-4">
            <div class="w-full">
                <label for="name" class="block text-xs font-bold text-ds-ink mb-1.5">
                    {{ __('identity.account.name') }}
                    <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                    <span class="sr-only">(wajib diisi)</span>
                </label>
                <input type="text" id="name" name="name" wire:model="name" required
                    class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                <p class="text-xs text-ds-faint mt-1.5">{{ __('identity.account.name_hint') }}</p>
                @error('name')
                    <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="w-full">
                <label for="email" class="block text-xs font-bold text-ds-ink mb-1.5">
                    {{ __('identity.account.email') }}
                    <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                    <span class="sr-only">(wajib diisi)</span>
                </label>
                <input type="email" id="email" name="email" value="{{ $email }}" disabled required
                    class="w-full h-11 px-4 rounded-xl text-sm bg-ds-canvas text-ds-muted border border-ds-line">
                <p class="text-xs text-ds-faint mt-1.5">{{ __('identity.account.email_hint') }}</p>
            </div>

            <div class="w-full">
                <label for="phone" class="block text-xs font-bold text-ds-ink mb-1.5">
                    {{ __('identity.account.phone') }}
                    <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                    <span class="sr-only">(wajib diisi)</span>
                </label>
                <input type="tel" id="phone" name="phone" wire:model="phone" required
                    class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                <p class="text-xs text-ds-faint mt-1.5">{{ __('identity.account.phone_hint') }}</p>
                @error('phone')
                    <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="pt-2 flex justify-end">
                <button type="submit" wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md disabled:opacity-60">
                    <span wire:loading.remove wire:target="updateProfile">{{ __('identity.account.profile_submit') }}</span>
                    <span wire:loading wire:target="updateProfile">{{ __('ui.actions.loading') }}</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Bagian 2: Ganti Password --}}
    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
        <h2 class="text-lg font-extrabold text-ds-ink">{{ __('identity.account.password_section_title') }}</h2>
        <p class="text-xs text-ds-muted mt-1 mb-5">{{ __('identity.account.password_section_subtitle') }}</p>
        @if (session('status_password'))
            <div role="status" class="mb-4 p-4 rounded-2xl border border-ds-green-soft bg-ds-green-soft/40 text-xs font-semibold text-ds-ink">
                {{ session('status_password') }}
            </div>
        @endif

        <form wire:submit.prevent="changePassword" class="space-y-4">
            <div class="w-full">
                <label for="current_password" class="block text-xs font-bold text-ds-ink mb-1.5">
                    {{ __('identity.account.current_password') }}
                    <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                    <span class="sr-only">(wajib diisi)</span>
                </label>
                <input type="password" id="current_password" name="current_password" wire:model="current_password" required
                    class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                <p class="text-xs text-ds-faint mt-1.5">{{ __('identity.account.current_password_hint') }}</p>
                @error('current_password')
                    <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="w-full">
                <label for="password" class="block text-xs font-bold text-ds-ink mb-1.5">
                    {{ __('identity.account.password') }}
                    <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                    <span class="sr-only">(wajib diisi)</span>
                </label>
                <input type="password" id="password" name="password" wire:model="password" required
                    class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                <p class="text-xs text-ds-faint mt-1.5">{{ __('identity.account.password_hint') }}</p>
                @error('password')
                    <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="w-full">
                <label for="password_confirmation" class="block text-xs font-bold text-ds-ink mb-1.5">
                    {{ __('identity.account.password_confirmation') }}
                    <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
                    <span class="sr-only">(wajib diisi)</span>
                </label>
                <input type="password" id="password_confirmation" name="password_confirmation" wire:model="password_confirmation" required
                    class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                @error('password_confirmation')
                    <p class="text-xs text-ds-danger mt-1.5" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="pt-2 flex justify-end">
                <button type="submit" wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md disabled:opacity-60">
                    <span wire:loading.remove wire:target="changePassword">{{ __('identity.account.password_submit') }}</span>
                    <span wire:loading wire:target="changePassword">{{ __('ui.actions.loading') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>
