@props([
    'title' => null,
    'navItems' => [],
    'headerActions' => null,
    'notifications' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50 text-slate-900 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title . ' — ' . config('app.name', 'SIMOKAS') : config('app.name', 'SIMOKAS') }}</title>

    <link rel="preload" href="{{ Vite::asset('resources/fonts/satoshi/Satoshi-Regular.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ Vite::asset('resources/fonts/satoshi/Satoshi-Bold.woff2') }}" as="font" type="font/woff2" crossorigin>

    {{-- ClassiAds design system: display + body fonts, icon set (dipakai halaman dashboard) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full flex flex-col ds-sans bg-slate-50 text-slate-900" x-data="{ mobileMenuOpen: false }">
    {{-- Skip-link untuk aksesibilitas keyboard --}}
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:px-6 focus:py-2.5 focus:bg-yellow-400 focus:text-slate-900 focus:rounded-xl focus:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-green-600">
        {{ __('ui.layout.skip_link') }}
    </a>

    {{-- Bilah atas --}}
    <header class="sticky top-0 z-40 bg-white/90 backdrop-blur-md border-b border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20 gap-4">
                {{-- Brand/Logo --}}
                <div class="flex items-center">
                    <a href="{{ url('/') }}" class="flex items-center gap-3 focus:outline-none focus-visible:ring-2 focus-visible:ring-green-600 rounded-xl px-1 py-1 select-none">
                        <span class="w-10 h-10 rounded-xl bg-gradient-to-tr from-green-600 to-emerald-500 flex items-center justify-center text-white text-lg shadow-md shadow-emerald-500/20">
                            <i aria-hidden="true" class="fa-solid fa-shield-halved"></i>
                        </span>
                        <span class="text-2xl font-extrabold tracking-tight text-slate-900">SIMO<span class="text-green-600">KAS</span></span>
                    </a>
                </div>

                {{-- Navigasi Desktop (tengah) --}}
                @if (!empty($navItems))
                    <nav class="hidden md:flex items-center justify-center gap-8 text-sm font-semibold text-slate-600 flex-1 px-8" aria-label="Navigasi Utama">
                            @foreach ($navItems as $item)
                                @php
                                    $isActive = !empty($item['active']);
                                @endphp
                                <a href="{{ $item['url'] ?? '#' }}"
                                   @class([
                                       'transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-green-600 rounded-lg',
                                       'text-green-600 hover:text-green-700' => $isActive,
                                       'hover:text-green-600' => !$isActive,
                                   ])
                                   @if ($isActive) aria-current="page" @endif>
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            @endforeach
                        </nav>
                    @endif

                {{-- Header Actions / User Slot --}}
                <div class="flex items-center gap-2">
                    <button type="button"
                            @click="mobileMenuOpen = !mobileMenuOpen"
                            class="md:hidden p-2 rounded-full text-slate-600 hover:text-green-600 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-green-600"
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
                ['label' => __('ui.layout.history'), 'route' => 'history'],
                ['label' => __('ui.layout.queue'), 'route' => 'admin.queue.index'],
                ['label' => __('ui.layout.admin_jobs'), 'route' => 'admin.jobs.index'],
                ['label' => __('ui.layout.inspectors'), 'route' => 'admin.inspectors.index'],
                ['label' => __('ui.layout.admin_listings'), 'route' => 'admin.listings.index'],
                ['label' => __('ui.layout.templates'), 'route' => 'admin.templates.index'],
                ['label' => __('ui.layout.settings'), 'route' => 'admin.settings'],
                ['label' => __('ui.layout.audit_logs'), 'route' => 'admin.audit-logs.index'],
                ['label' => __('ui.layout.users'), 'route' => 'admin.users.index'],
            ];
        } elseif ($currentUser->isInspector()) {
            $isApprovedInspector = app(\App\Domains\Inspector\Contracts\InspectorEligibility::class)->canViewAndTakeJobs($currentUser);

            if ($isApprovedInspector) {
                $roleNavLinks = [
                    ['label' => __('ui.layout.dashboard'), 'route' => 'dashboard'],
                    ['label' => __('ui.layout.history'), 'route' => 'history'],
                    ['label' => __('ui.layout.open_jobs'), 'route' => 'jobs.open'],
                    ['label' => __('ui.layout.my_jobs'), 'route' => 'jobs.inspector'],
                    ['label' => __('ui.layout.earnings'), 'route' => 'inspector.earnings'],
                    ['label' => __('ui.layout.conversations'), 'route' => 'conversations.index'],
                    ['label' => __('ui.layout.notifications'), 'route' => 'notifications.index'],
                ];
            } else {
                $roleNavLinks = [
                    ['label' => __('ui.layout.dashboard'), 'route' => 'dashboard'],
                    ['label' => __('ui.layout.history'), 'route' => 'history'],
                    ['label' => __('ui.layout.onboarding'), 'route' => 'inspector.onboarding'],
                ];
            }
        } elseif ($currentUser->isUser()) {
            $roleNavLinks = [
                ['label' => __('ui.layout.dashboard'), 'route' => 'dashboard'],
                ['label' => __('ui.layout.history'), 'route' => 'history'],
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
                                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold text-ds-ink hover:bg-ds-canvas transition-colors focus:outline-none focus:ring-2 focus:ring-ds-blue"
                                        aria-haspopup="true"
                                        :aria-expanded="accountMenuOpen.toString()"
                                        aria-label="{{ __('ui.layout.account_menu') }}">
                                    <span class="max-w-28 truncate">{{ auth()->user()->name }}</span>
                                    <x-ui.icon name="chevron-down" class="w-4 h-4" />
                                </button>
                                <div x-show="accountMenuOpen"
                                     x-cloak
                                     x-transition
                                     class="absolute right-0 mt-2 w-56 max-h-[80vh] overflow-y-auto rounded-2xl bg-white border border-ds-line/80 shadow-xl py-1.5 z-50">
                                    @foreach ($roleNavLinks as $item)
                                        @if (\Illuminate\Support\Facades\Route::has($item['route']))
                                            @php
                                                $isActive = request()->routeIs($item['route']);
                                            @endphp
                                            <a href="{{ route($item['route']) }}"
                                               @class([
                                                   'block px-4 py-2 text-sm transition-colors rounded-lg mx-1',
                                                   'bg-ds-blue-soft text-ds-blue-ink font-bold' => $isActive,
                                                   'text-ds-muted hover:bg-ds-canvas hover:text-ds-ink' => !$isActive,
                                               ])
                                               @if ($isActive) aria-current="page" @endif>
                                                {{ $item['label'] }}
                                            </a>
                                        @endif
                                    @endforeach

                                    <div class="border-t border-ds-line/60 my-1"></div>

                                    <form method="POST" action="{{ route('identity.logout') }}">
                                        @csrf
                                        <button type="submit"
                                                class="block w-full text-left px-4 py-2 text-sm text-ds-muted hover:bg-ds-canvas hover:text-ds-ink transition-colors">
                                            {{ __('Keluar') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @else
                            <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-semibold text-slate-700 hover:text-green-600 transition-colors">
                                {{ __('Masuk') }}
                            </a>
                            <a href="{{ route('identity.register') }}" class="inline-flex items-center gap-2 bg-slate-900 hover:bg-green-600 text-white px-5 py-2.5 rounded-full text-sm font-semibold shadow-md hover:shadow-lg transition-all">
                                <i aria-hidden="true" class="fa-solid fa-circle-plus text-yellow-400"></i>
                                <span>{{ __('Daftar') }}</span>
                            </a>
                        @endauth
                    @endif
                </div>
            </div>
        </div>

        {{-- Dropdown Menu Mobile --}}
        @if (!empty($navItems) || !empty($roleNavLinks))
            <div x-show="mobileMenuOpen" x-cloak class="md:hidden bg-white border-t border-slate-100 px-5 py-3 flex flex-col gap-2">
                @if (!empty($navItems))
                    @foreach ($navItems as $item)
                        @php
                            $isActive = !empty($item['active']);
                        @endphp
                        <a href="{{ $item['url'] ?? '#' }}"
                           @class([
                               'py-2 px-3 rounded-xl text-sm font-semibold transition-colors',
                               'bg-slate-900 text-white' => $isActive,
                               'text-slate-600 hover:text-green-600' => !$isActive,
                           ])>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                @endif

                @if (!empty($roleNavLinks))
                    <div class="border-t border-slate-100 pt-2 mt-1 flex flex-col gap-1">
                        @foreach ($roleNavLinks as $item)
                            @if (\Illuminate\Support\Facades\Route::has($item['route']))
                                @php
                                    $isActive = request()->routeIs($item['route']);
                                @endphp
                                <a href="{{ route($item['route']) }}"
                                   @class([
                                       'py-2 px-3 rounded-xl text-sm font-semibold transition-colors',
                                       'bg-slate-900 text-white' => $isActive,
                                       'text-slate-600 hover:text-green-600' => !$isActive,
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

    {{-- Footer --}}
    <footer class="mt-auto bg-slate-900 text-slate-400 text-xs border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-8">
                <div class="space-y-3 col-span-2 md:col-span-1">
                    <a href="{{ url('/') }}" class="flex items-center gap-2 text-white font-extrabold text-lg">
                        <i aria-hidden="true" class="fa-solid fa-shield-halved text-emerald-500"></i> SIMOKAS
                    </a>
                    <p class="text-slate-400 leading-relaxed">
                        Platform jual beli kendaraan bekas dengan inspeksi independen di Kota Pekanbaru. Netral 100% — kami tidak menjual kendaraan.
                    </p>
                </div>
                <div>
                    <h4 class="text-white font-bold mb-3">NAVIGASI</h4>
                    <ul class="space-y-2">
                        <li><a class="hover:text-white transition-colors" href="{{ route('catalog.index') }}">Kendaraan</a></li>
                        <li><a class="hover:text-white transition-colors" href="{{ route('catalog.how-it-works') }}">Cara Kerja</a></li>
                        <li><a class="hover:text-white transition-colors" href="{{ route('catalog.help') }}">Bantuan</a></li>
                        <li><a class="hover:text-white transition-colors" href="{{ route('login') }}">Masuk</a></li>
                        <li><a class="hover:text-white transition-colors" href="{{ route('identity.register') }}">Daftar</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-bold mb-3">LAYANAN</h4>
                    <ul class="space-y-2">
                        <li><a class="hover:text-white transition-colors" href="{{ route('catalog.index', ['type' => 'car']) }}">Inspeksi Mobil — Rp 50.000</a></li>
                        <li><a class="hover:text-white transition-colors" href="{{ route('catalog.index', ['type' => 'motorcycle']) }}">Inspeksi Motor — Rp 15.000</a></li>
                        <li><a class="hover:text-white transition-colors" href="{{ route('identity.register') }}">Jadi Inspektor</a></li>
                        <li><a class="hover:text-white transition-colors" href="{{ route('dashboard') }}">Dashboard</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-bold mb-3">WILAYAH &amp; LEGAL</h4>
                    <p class="text-slate-400 leading-relaxed">
                        Tampan · Sukajadi · Marpoyan Damai · Bukit Raya · Rumbai · Payung Sekaki<br/>
                        Kota Pekanbaru, Riau
                    </p>
                    <nav class="flex flex-col gap-2 mt-3" aria-label="Tautan Hukum">
                        <a href="{{ route('legal.terms') }}" class="hover:text-white text-left transition-colors">
                            {{ __('legal.footer_terms') }}
                        </a>
                        <a href="{{ route('legal.privacy') }}" class="hover:text-white text-left transition-colors">
                            {{ __('legal.footer_privacy') }}
                        </a>
                    </nav>
                </div>
            </div>
            <div class="pt-8 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
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
        <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-slate-100 pb-safe" aria-label="Navigasi Seluler">
            <div class="grid grid-flow-col auto-cols-fr items-center h-16 px-2">
                @foreach ($bottomNavItems as $item)
                    @php
                        $isActive = !empty($item['active']);
                    @endphp
                    <a href="{{ $item['url'] ?? '#' }}"
                       @class([
                           'flex flex-col items-center justify-center py-1 rounded-xl text-xs font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-green-600 min-h-[44px] transition-colors',
                           'text-green-600' => $isActive,
                           'text-slate-500 hover:text-green-600' => !$isActive,
                       ])
                       @if ($isActive) aria-current="page" @endif>
                        <span class="truncate max-w-[64px]">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </nav>
    @endif

    @livewireScripts
</body>
</html>
