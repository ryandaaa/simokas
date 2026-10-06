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
<body class="min-h-full flex flex-col font-sans bg-bg text-text">
    {{-- Skip-link untuk aksesibilitas keyboard --}}
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-primary focus:text-surface focus:rounded-lg focus:shadow-md focus:outline-none focus:ring-2 focus:ring-accent">
        {{ __('ui.layout.skip_link') }}
    </a>

    {{-- Bilah atas (Desktop & Header Mobile) --}}
    <header class="sticky top-0 z-40 bg-surface border-b border-muted/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                {{-- Brand/Logo --}}
                <div class="flex items-center gap-6">
                    <a href="{{ url('/') }}" class="flex items-center gap-2 text-primary font-bold text-xl tracking-tight focus:outline-none focus:ring-2 focus:ring-accent rounded-lg px-1">
                        <x-ui.icon name="perisai-centang" class="w-7 h-7 text-accent" />
                        <span>{{ config('app.name', 'SIMOKAS') }}</span>
                    </a>

                    {{-- Navigasi Desktop (bilah atas) --}}
                    @if (!empty($navItems))
                        <nav class="hidden md:flex items-center gap-1" aria-label="Navigasi Utama">
                            @foreach ($navItems as $item)
                                @php
                                    $isActive = !empty($item['active']);
                                @endphp
                                <a href="{{ $item['url'] ?? '#' }}"
                                   @class([
                                       'flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-accent',
                                       'bg-primary/10 text-primary font-semibold' => $isActive,
                                       'text-muted hover:text-text hover:bg-muted/10' => !$isActive,
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
                    {{ $headerActions ?? '' }}
                </div>
            </div>
        </div>
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
    <main id="main-content" class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 focus:outline-none" tabindex="-1">
        {{ $slot }}
    </main>

    {{-- Footer Bersama --}}
    <footer class="mt-auto border-t border-muted/20 bg-surface text-muted text-sm pb-24 md:pb-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <x-ui.icon name="perisai-centang" class="w-5 h-5 text-accent" />
                <span class="font-semibold text-text">{{ config('app.name', 'SIMOKAS') }}</span>
                <span>&copy; {{ date('Y') }} {{ __('legal.footer_rights') }}</span>
            </div>
            <nav class="flex items-center gap-6" aria-label="Tautan Hukum">
                <a href="{{ route('legal.privacy') }}" class="text-muted hover:text-text focus:outline-none focus:ring-2 focus:ring-accent rounded transition-colors">
                    {{ __('legal.footer_privacy') }}
                </a>
                <a href="{{ route('legal.terms') }}" class="text-muted hover:text-text focus:outline-none focus:ring-2 focus:ring-accent rounded transition-colors">
                    {{ __('legal.footer_terms') }}
                </a>
            </nav>
        </div>
    </footer>

    {{-- Menu bawah di HP (maksimal 5 item) --}}
    @if (!empty($navItems))
        @php
            $bottomNavItems = array_slice($navItems, 0, 5);
        @endphp
        <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-surface border-t border-muted/20 pb-safe" aria-label="Navigasi Seluler">
            <div class="grid grid-flow-col auto-cols-fr items-center h-16 px-2">
                @foreach ($bottomNavItems as $item)
                    @php
                        $isActive = !empty($item['active']);
                    @endphp
                    <a href="{{ $item['url'] ?? '#' }}"
                       @class([
                           'flex flex-col items-center justify-center py-1 rounded-lg text-xs font-medium focus:outline-none focus:ring-2 focus:ring-accent min-h-[44px]',
                           'text-primary font-semibold' => $isActive,
                           'text-muted hover:text-text' => !$isActive,
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
