@php
    use App\Domains\Identity\Enums\UserRole;
@endphp

<div class="space-y-6">
    {{-- Header --}}
    <div>
        <h1 class="text-3xl font-bold text-text">{{ __('dashboard.title') }}</h1>
        <p class="text-sm text-muted mt-1">{{ __('dashboard.welcome', ['name' => auth()->user()->name]) }}</p>
    </div>

    {{-- Role-based Dashboard --}}
    @switch($role)
        @case(UserRole::User)
            <livewire:dashboard::buyer-seller-dashboard :user="$user" />
            @break

        @case(UserRole::Inspector)
            <livewire:dashboard::inspector-dashboard :user="$user" />
            @break

        @case(UserRole::Admin)
            <livewire:dashboard::admin-dashboard :user="$user" />
            @break

        @default
            <x-ui.alert variant="danger" :message="__('dashboard.unknown_role')" />
    @endswitch
</div>
