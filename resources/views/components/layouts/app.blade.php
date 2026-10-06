@props([
    'title' => null,
    'navItems' => [],
    'headerActions' => null,
    'notifications' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-bg text-text antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title . ' — ' . config('app.name', 'SIMOKAS') : config('app.name', 'SIMOKAS') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full flex flex-col font-sans bg-paper text-charcoal" x-data="{ mobileMenuOpen: false }">
    {{-- Skip-link untuk aksesibilitas keyboard --}}
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:px-6 focus:py-2.5 focus:bg-lime focus:text-forest focus:rounded-full focus:shadow-md focus:outline-none focus:ring-2 focus:ring-forest">
        {{ __('ui.layout.skip_link') }}
    </a>

    {{-- Bilah atas (Desktop & Header Mobile ala Wise) --}}
    <header class="sticky top-0 z-40 bg-surface border-b border-fog">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 gap-4">
                {{-- Brand/Logo --}}
                <div class="flex items-center gap-6">
                    <a href="{{ url('/') }}" class="text-sm font-black text-forest tracking-[0.2em] focus:outline-none focus:ring-2 focus:ring-forest rounded-full px-2 py-1 select-none">
                        {{ config('app.name', 'SIMOKAS') }}
                    </a>

                    {{-- Navigasi Desktop (bilah atas berbentuk pil) --}}
                    @if (!empty($navItems))
                        <nav class="hidden md:flex items-center gap-1 p-1 bg-fog rounded-full" aria-label="Navigasi Utama">
                            @foreach ($navItems as $item)
                                @php
                                    $isActive = !empty($item['active']);
                                @endphp
                                <a href="{{ $item['url'] ?? '#' }}"
                                   @class([
                                       'flex items-center gap-2 px-4 py-1.5 rounded-full text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-forest',
                                       'bg-lime text-forest font-bold' => $isActive,
                                       'text-charcoal hover:bg-linen' => !$isActive,
                                   ])
                                   @if ($isActive) aria-current="page" @endif>
                                    @if (!empty($item['icon']))
                                        <x-ui.icon :name="$item['icon']" class="w-4 h-4" />
                                    @endif
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            @endforeach
                        </nav>
                    @endif
                </div>

                {{-- Header Actions / User Slot --}}
                <div class="flex items-center gap-2">
                    <button type="button"
                            @click="mobileMenuOpen = !mobileMenuOpen"
                            class="md:hidden p-2 rounded-full text-forest hover:bg-fog focus:outline-none focus:ring-2 focus:ring-forest"
                            aria-label="Menu navigasi">
                        <x-ui.icon name="menu" class="w-6 h-6" />
                    </button>

@php
    $currentUser = auth()->user();
    $roleNavLinks = [];

    if ($currentUser) {
        if ($currentUser->isAdmin()) {
            $roleNavLinks = [
                ['label' => __('ui.layout.dashboard'), 'route' => 'dashboard'],
                ['label' => __('ui.layout.queue'), 'route' => 'admin.queue.index'],
                ['label' => __('ui.layout.admin_jobs'), 'route' => 'admin.jobs.index'],
                ['label' => __('ui.layout.inspectors'), 'route' => 'admin.inspectors.index'],
                ['label' => __('ui.layout.admin_listings'), 'route' => 'admin.listings.index'],
                ['label' => __('ui.layout.templates'), 'route' => 'admin.templates.index'],
                ['label' => __('ui.layout.settings'), 'route' => 'admin.settings'],
                ['label' => __('ui.layout.audit_logs'), 'route' => 'admin.audit-logs.index'],
            ];
        } elseif ($currentUser->isInspector()) {
            $isApprovedInspector = app(\App\Domains\Inspector\Contracts\InspectorEligibility::class)->canViewAndTakeJobs($currentUser);

            if ($isApprovedInspector) {
                $roleNavLinks = [
                    ['label' => __('ui.layout.dashboard'), 'route' => 'dashboard'],
                    ['label' => __('ui.layout.open_jobs'), 'route' => 'jobs.open'],
                    ['label' => __('ui.layout.my_jobs'), 'route' => 'jobs.inspector'],
                    ['label' => __('ui.layout.earnings'), 'route' => 'inspector.earnings'],
                    ['label' => __('ui.layout.conversations'), 'route' => 'conversations.index'],
                    ['label' => __('ui.layout.notifications'), 'route' => 'notifications.index'],
                ];
            } else {
                $roleNavLinks = [
                    ['label' => __('ui.layout.dashboard'), 'route' => 'dashboard'],
                    ['label' => __('ui.layout.onboarding'), 'route' => 'inspector.onboarding'],
                ];
            }
        } elseif ($currentUser->isUser()) {
            $roleNavLinks = [
                ['label' => __('ui.layout.dashboard'), 'route' => 'dashboard'],
                ['label' => __('ui.layout.my_listings'), 'route' => 'listings.mine'],
                ['label' => __('ui.layout.create_listing'), 'route' => 'listings.create'],
                ['label' => __('ui.layout.my_inspections'), 'route' => 'jobs.mine'],
                ['label' => __('ui.layout.transactions'), 'route' => 'transactions.index'],
                ['label' => __('ui.layout.conversations'), 'route' => 'conversations.index'],
                ['label' => __('ui.layout.notifications'), 'route' => 'notifications.index'],
            ];
        }
    }
@endphp

                    @if ($headerActions)
                        {{ $headerActions }}
                    @else
                        @auth
                            <div class="relative" x-data="{ accountMenuOpen: false }" @click.outside="accountMenuOpen = false" @keydown.escape.window="accountMenuOpen = false">
                                <button type="button"
                                        @click="accountMenuOpen = !accountMenuOpen"
                                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium text-forest hover:bg-linen transition-colors focus:outline-none focus:ring-2 focus:ring-forest"
                                        aria-haspopup="true"
                                        :aria-expanded="accountMenuOpen.toString()"
                                        aria-label="{{ __('ui.layout.account_menu') }}">
                                    <span class="max-w-28 truncate">{{ auth()->user()->name }}</span>
                                    <x-ui.icon name="chevron-down" class="w-4 h-4" />
                                </button>
                                <div x-show="accountMenuOpen"
                                     x-cloak
                                     x-transition
                                     class="absolute right-0 mt-2 w-56 max-h-[80vh] overflow-y-auto rounded-xl bg-surface border border-fog shadow-lg py-1.5 z-50">
                                    @foreach ($roleNavLinks as $item)
                                        @if (\Illuminate\Support\Facades\Route::has($item['route']))
                                            @php
                                                $isActive = request()->routeIs($item['route']);
                                            @endphp
                                            <a href="{{ route($item['route']) }}"
                                               @class([
                                                   'block px-4 py-2 text-sm transition-colors rounded-lg mx-1',
                                                   'bg-linen text-forest font-bold' => $isActive,
                                                   'text-charcoal hover:bg-fog' => !$isActive,
                                               ])
                                               @if ($isActive) aria-current="page" @endif>
                                                {{ $item['label'] }}
                                            </a>
                                        @endif
                                    @endforeach

                                    <div class="border-t border-fog my-1"></div>

                                    <form method="POST" action="{{ route('identity.logout') }}">
                                        @csrf
                                        <button type="submit"
                                                class="block w-full text-left px-4 py-2 text-sm text-charcoal hover:bg-fog transition-colors">
                                            {{ __('Keluar') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @else
                            <a href="{{ route('login') }}" class="px-3 py-1.5 rounded-full text-sm font-medium text-forest hover:bg-linen transition-colors">
                                {{ __('Masuk') }}
                            </a>
                            <x-ui.button :href="route('identity.register')" variant="secondary" class="!min-h-[36px] !px-4 !py-1.5 !text-sm">
                                {{ __('Daftar') }}
                            </x-ui.button>
                        @endauth
                    @endif
                </div>
            </div>
        </div>

        {{-- Dropdown Menu Mobile --}}
        @if (!empty($navItems) || !empty($roleNavLinks))
            <div x-show="mobileMenuOpen" x-cloak class="md:hidden bg-surface border-t border-fog px-5 py-3 flex flex-col gap-2">
                @if (!empty($navItems))
                    @foreach ($navItems as $item)
                        @php
                            $isActive = !empty($item['active']);
                        @endphp
                        <a href="{{ $item['url'] ?? '#' }}"
                           @class([
                               'py-2 px-3 rounded-full text-sm font-medium transition-colors',
                               'bg-lime text-forest font-bold' => $isActive,
                               'text-forest hover:bg-linen' => !$isActive,
                           ])>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                @endif

                @if (!empty($roleNavLinks))
                    <div class="border-t border-fog pt-2 mt-1 flex flex-col gap-1">
                        @foreach ($roleNavLinks as $item)
                            @if (\Illuminate\Support\Facades\Route::has($item['route']))
                                @php
                                    $isActive = request()->routeIs($item['route']);
                                @endphp
                                <a href="{{ route($item['route']) }}"
                                   @class([
                                       'py-2 px-3 rounded-full text-sm font-medium transition-colors',
                                       'bg-lime text-forest font-bold' => $isActive,
                                       'text-charcoal hover:bg-linen' => !$isActive,
                                   ])
                                   @if ($isActive) aria-current="page" @endif>
                                    {{ $item['label'] }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </header>

    {{-- Region aria-live untuk pembaca layar --}}
    <div id="notifications-live-region" aria-live="polite" aria-atomic="true" class="sr-only">
        {{ $notifications ?? '' }}
    </div>

    {{-- Notifikasi Visual --}}
    @if (isset($notifications))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
            {{ $notifications }}
        </div>
    @endif

    {{-- Slot Konten Utama --}}
    <main id="main-content" class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 focus:outline-none" tabindex="-1">
        {{ $slot }}
    </main>

    {{-- Footer Gelap Forest Ink ala Wise --}}
    <footer class="mt-auto bg-forest text-paper pb-24 md:pb-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 grid grid-cols-2 md:grid-cols-4 gap-x-6 gap-y-10">
            <div class="col-span-2 md:col-span-1">
                <p class="text-sm font-black text-lime tracking-[0.2em]">SIMOKAS</p>
                <p class="text-sm text-paper/70 mt-3 max-w-xs leading-relaxed">
                    Platform jual beli kendaraan bekas dengan inspeksi independen di Kota Pekanbaru. Netral 100% — kami tidak menjual kendaraan.
                </p>
            </div>
            <div>
                <p class="text-xs font-bold text-lime mb-3 tracking-[0.15em]">NAVIGASI</p>
                <div class="flex flex-col gap-2.5 text-sm">
                    <a class="text-paper/80 hover:text-lime transition-colors" href="{{ route('catalog.index') }}">Kendaraan</a>
                    <a class="text-paper/80 hover:text-lime transition-colors" href="{{ route('catalog.how-it-works') }}">Cara Kerja</a>
                    <a class="text-paper/80 hover:text-lime transition-colors" href="{{ route('catalog.help') }}">Bantuan</a>
                    <a class="text-paper/80 hover:text-lime transition-colors" href="{{ route('login') }}">Masuk</a>
                    <a class="text-paper/80 hover:text-lime transition-colors" href="{{ route('identity.register') }}">Daftar</a>
                </div>
            </div>
            <div>
                <p class="text-xs font-bold text-lime mb-3 tracking-[0.15em]">LAYANAN</p>
                <div class="flex flex-col gap-2.5 text-sm">
                    <a class="text-paper/80 hover:text-lime transition-colors" href="{{ route('catalog.index', ['type' => 'car']) }}">Inspeksi Mobil — Rp 50.000</a>
                    <a class="text-paper/80 hover:text-lime transition-colors" href="{{ route('catalog.index', ['type' => 'motorcycle']) }}">Inspeksi Motor — Rp 15.000</a>
                    <a class="text-paper/80 hover:text-lime transition-colors" href="{{ route('identity.register') }}">Jadi Inspektor</a>
                    <a class="text-paper/80 hover:text-lime transition-colors" href="{{ route('dashboard') }}">Dashboard</a>
                </div>
            </div>
            <div>
                <p class="text-xs font-bold text-lime mb-3 tracking-[0.15em]">WILAYAH & LEGAL</p>
                <p class="text-sm text-paper/70 leading-relaxed">
                    Tampan · Sukajadi · Marpoyan Damai · Bukit Raya · Rumbai · Payung Sekaki<br/>
                    Kota Pekanbaru, Riau
                </p>
                <nav class="flex flex-col gap-2.5 text-sm mt-3" aria-label="Tautan Hukum">
                    <a href="{{ route('legal.terms') }}" class="text-paper/80 hover:text-lime text-left transition-colors">
                        {{ __('legal.footer_terms') }}
                    </a>
                    <a href="{{ route('legal.privacy') }}" class="text-paper/80 hover:text-lime text-left transition-colors">
                        {{ __('legal.footer_privacy') }}
                    </a>
                </nav>
            </div>
        </div>

        <div class="border-t border-paper/15">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-paper/50">
                <span>&copy; {{ date('Y') }} {{ config('app.name', 'SIMOKAS') }} — {{ __('legal.footer_rights') }}</span>
                <span>Seluruh waktu ditampilkan dalam WIB</span>
            </div>
        </div>
    </footer>

    {{-- Menu bawah di HP (maksimal 5 item) --}}
    @if (!empty($navItems))
        @php
            $bottomNavItems = array_slice($navItems, 0, 5);
        @endphp
        <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-surface border-t border-fog pb-safe" aria-label="Navigasi Seluler">
            <div class="grid grid-flow-col auto-cols-fr items-center h-16 px-2">
                @foreach ($bottomNavItems as $item)
                    @php
                        $isActive = !empty($item['active']);
                    @endphp
                    <a href="{{ $item['url'] ?? '#' }}"
                       @class([
                           'flex flex-col items-center justify-center py-1 rounded-full text-xs font-medium focus:outline-none focus:ring-2 focus:ring-forest min-h-[44px] transition-colors',
                           'text-forest font-bold bg-linen' => $isActive,
                           'text-slate hover:text-forest' => !$isActive,
                       ])
                       @if ($isActive) aria-current="page" @endif>
                        @if (!empty($item['icon']))
                            <x-ui.icon :name="$item['icon']" class="w-5 h-5 mb-0.5" />
                        @endif
                        <span class="truncate max-w-[64px]">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </nav>
    @endif

    @livewireScripts
</body>
</html>
