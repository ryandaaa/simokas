<div class="max-w-3xl mx-auto py-6 space-y-6">
    {{-- Header navigasi kembali --}}
    <div class="flex items-center justify-between">
        <x-ui.button
            :href="route('dashboard')"
            variant="ghost"
            icon="chevron-left"
        >
            {{ __('identity.account.back_to_dashboard') }}
        </x-ui.button>
    </div>

    {{-- Bagian 1: Ubah Profil --}}
    <x-ui.card
        :title="__('identity.account.profile_section_title')"
        :subtitle="__('identity.account.profile_section_subtitle')"
    >
        @if (session('status_profile'))
            <div class="mb-4">
                <x-ui.alert variant="success" :message="session('status_profile')" />
            </div>
        @endif

        <form wire:submit.prevent="updateProfile" class="space-y-4">
            <x-ui.input
                id="name"
                name="name"
                type="text"
                :label="__('identity.account.name')"
                :hint="__('identity.account.name_hint')"
                wire:model="name"
                :error="$errors->first('name')"
                required
            />

            <x-ui.input
                id="email"
                name="email"
                type="email"
                :label="__('identity.account.email')"
                :hint="__('identity.account.email_hint')"
                wire:model="email"
                :value="$email"
                disabled
                required
            />

            <x-ui.input
                id="phone"
                name="phone"
                type="tel"
                :label="__('identity.account.phone')"
                :hint="__('identity.account.phone_hint')"
                wire:model="phone"
                :error="$errors->first('phone')"
                required
            />

            <div class="pt-2 flex justify-end">
                <x-ui.button
                    type="submit"
                    variant="primary"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="updateProfile">{{ __('identity.account.profile_submit') }}</span>
                    <span wire:loading wire:target="updateProfile">{{ __('ui.actions.loading') }}</span>
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>

    {{-- Bagian 2: Ganti Password --}}
    <x-ui.card
        :title="__('identity.account.password_section_title')"
        :subtitle="__('identity.account.password_section_subtitle')"
    >
        @if (session('status_password'))
            <div class="mb-4">
                <x-ui.alert variant="success" :message="session('status_password')" />
            </div>
        @endif

        <form wire:submit.prevent="changePassword" class="space-y-4">
            <x-ui.input
                id="current_password"
                name="current_password"
                type="password"
                :label="__('identity.account.current_password')"
                :hint="__('identity.account.current_password_hint')"
                wire:model="current_password"
                :error="$errors->first('current_password')"
                required
            />

            <x-ui.input
                id="password"
                name="password"
                type="password"
                :label="__('identity.account.password')"
                :hint="__('identity.account.password_hint')"
                wire:model="password"
                :error="$errors->first('password')"
                required
            />

            <x-ui.input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                :label="__('identity.account.password_confirmation')"
                wire:model="password_confirmation"
                :error="$errors->first('password_confirmation')"
                required
            />

            <div class="pt-2 flex justify-end">
                <x-ui.button
                    type="submit"
                    variant="primary"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="changePassword">{{ __('identity.account.password_submit') }}</span>
                    <span wire:loading wire:target="changePassword">{{ __('ui.actions.loading') }}</span>
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>
</div>
