@php
    use App\Domains\Identity\Enums\UserRole;
@endphp

<div class="space-y-8 ds-sans">
    {{-- Header ala ClassiAds: eyebrow + H2 + sub, aksi di kanan (inspektor punya kepala ringkas sendiri) --}}
    @if ($role !== UserRole::Inspector)
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div class="space-y-2">
            <span class="text-ds-blue text-xs font-extrabold uppercase tracking-wider">{{ __('dashboard.title') }}</span>
            <h1 class="text-3xl font-extrabold text-ds-ink tracking-tight">{{ __('dashboard.welcome', ['name' => auth()->user()->name]) }}</h1>
            <p class="text-sm text-ds-muted">{{ __('dashboard.subtitle') }}</p>
        </div>
        @if (\Illuminate\Support\Facades\Route::has('history'))
            <a href="{{ route('history') }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs hover:shadow-md">
                <i aria-hidden="true" class="fa-regular fa-clock text-xs"></i>
                <span>{{ __('dashboard.view_history') }}</span>
            </a>
        @endif
    </div>
    @endif

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
            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6 text-sm font-semibold text-ds-ink">
                {{ __('dashboard.unknown_role') }}
            </div>
    @endswitch
</div>
