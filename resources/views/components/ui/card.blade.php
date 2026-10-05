@props([
    'title' => null,
    'subtitle' => null,
    'header' => null,
    'footer' => null,
    'clickable' => false,
    'href' => null,
    'padding' => 'p-5 sm:p-6',
    'loading' => false,
])

@php
    $isClickable = $clickable || !empty($href);
    $baseClasses = 'bg-surface border border-muted/20 rounded-xl transition-all duration-150 overflow-hidden';
    $interactiveClasses = $isClickable ? 'hover:border-muted/40 hover:shadow-sm cursor-pointer' : '';
    $classes = "{$baseClasses} {$interactiveClasses}";
@endphp

@if ($loading)
    <div {{ $attributes->merge(['class' => $baseClasses . ' p-5 sm:p-6 space-y-4']) }}>
        <x-ui.skeleton variant="title" class="w-1/3" />
        <x-ui.skeleton variant="text" class="w-full" />
        <x-ui.skeleton variant="text" class="w-4/5" />
    </div>
@elseif ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes . ' block']) }}>
        @if ($header || $title)
            <div class="px-5 py-4 border-b border-muted/15 flex items-center justify-between">
                @if ($header)
                    {{ $header }}
                @else
                    <div>
                        <h3 class="text-base font-semibold text-text">{{ $title }}</h3>
                        @if ($subtitle)
                            <p class="text-xs text-muted mt-0.5">{{ $subtitle }}</p>
                        @endif
                    </div>
                @endif
            </div>
        @endif

        <div class="{{ $padding }}">
            {{ $slot }}
        </div>

        @if ($footer)
            <div class="px-5 py-3.5 bg-bg/50 border-t border-muted/15">
                {{ $footer }}
            </div>
        @endif
    </a>
@else
    <div {{ $attributes->merge(['class' => $classes]) }}>
        @if ($header || $title)
            <div class="px-5 py-4 border-b border-muted/15 flex items-center justify-between">
                @if ($header)
                    {{ $header }}
                @else
                    <div>
                        <h3 class="text-base font-semibold text-text">{{ $title }}</h3>
                        @if ($subtitle)
                            <p class="text-xs text-muted mt-0.5">{{ $subtitle }}</p>
                        @endif
                    </div>
                @endif
            </div>
        @endif

        <div class="{{ $padding }}">
            {{ $slot }}
        </div>

        @if ($footer)
            <div class="px-5 py-3.5 bg-bg/50 border-t border-muted/15">
                {{ $footer }}
            </div>
        @endif
    </div>
@endif
