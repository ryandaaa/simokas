@php
    use App\Domains\Identity\Enums\UserRole;
@endphp

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-obs tracking-tight">{{ __('dashboard.title') }}</h1>
            <p class="text-sm text-slate mt-1">{{ __('dashboard.welcome', ['name' => auth()->user()->name]) }}</p>
        </div>
        @if (\Illuminate\Support\Facades\Route::has('history'))
            <x-ui.button :href="route('history')" variant="secondary" icon="dokumen">
                {{ __('dashboard.view_history') }}
            </x-ui.button>
        @endif
    </div>

    {{-- Role-based Dashboard --}}
    @switch($role)
        @case(UserRole::User)
            <livewire:dashboard.buyer-seller-dashboard :user="$user" />
            @break

        @case(UserRole::Inspector)
            <livewire:dashboard.inspector-dashboard :user="$user" />
            @break

        @case(UserRole::Admin)
            <livewire:dashboard.admin-dashboard :user="$user" />
            @break

        @default
            <x-ui.alert variant="danger" :message="__('dashboard.unknown_role')" />
    @endswitch
</div>
