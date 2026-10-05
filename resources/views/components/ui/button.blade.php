@props([
    'variant' => 'primary',
    'type' => 'button',
    'href' => null,
    'disabled' => false,
    'loading' => false,
    'icon' => null,
    'iconRight' => null,
])

@php
    $baseClasses = 'inline-flex items-center justify-center gap-2 min-h-[44px] px-4 py-2.5 rounded-lg text-sm font-semibold transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 select-none';

    $variantClasses = match ($variant) {
        'secondary' => 'bg-surface text-text border border-muted/30 hover:bg-muted/10 focus:ring-accent active:bg-muted/20',
        'danger' => 'bg-danger text-surface hover:bg-danger/90 focus:ring-danger active:bg-danger/80',
        'ghost' => 'bg-transparent text-text hover:bg-muted/10 focus:ring-accent active:bg-muted/20',
        default => 'bg-primary text-surface hover:bg-primary/90 focus:ring-accent active:bg-primary/80',
    };

    $disabledClasses = ($disabled || $loading) ? 'opacity-50 cursor-not-allowed pointer-events-none' : '';
    $classes = "{$baseClasses} {$variantClasses} {$disabledClasses}";
@endphp

@if ($href)
    <a href="{{ $disabled ? '#' : $href }}"
       {{ $attributes->merge(['class' => $classes]) }}
       @if ($disabled) aria-disabled="true" tabindex="-1" @endif>
        @if ($loading)
            <x-ui.icon name="panah-putar" class="w-4 h-4 animate-spin" />
        @elseif ($icon)
            <x-ui.icon :name="$icon" class="w-4 h-4" />
        @endif

        <span>{{ $slot }}</span>

        @if ($iconRight && !$loading)
            <x-ui.icon :name="$iconRight" class="w-4 h-4" />
        @endif
    </a>
@else
    <button type="{{ $type }}"
            {{ $attributes->merge([
                'class' => $classes,
                'disabled' => $disabled || $loading,
                'wire:loading.attr' => 'disabled',
            ]) }}>
        @if ($loading)
            <x-ui.icon name="panah-putar" class="w-4 h-4 animate-spin" />
        @elseif ($icon)
            <x-ui.icon :name="$icon" class="w-4 h-4" />
        @endif

        <span>{{ $slot }}</span>

        @if ($iconRight && !$loading)
            <x-ui.icon :name="$iconRight" class="w-4 h-4" />
        @endif
    </button>
@endif
