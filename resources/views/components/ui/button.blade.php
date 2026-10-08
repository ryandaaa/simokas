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
    $baseClasses = 'inline-flex items-center justify-center gap-2 min-h-[44px] px-6 py-2.5 rounded-full text-base font-medium tracking-tight transition-colors duration-150 select-none';

    $isDisabled = $disabled || $loading;

    $variantClasses = match ($variant) {
        'secondary' => $isDisabled
            ? 'bg-fog text-pebble border border-transparent'
            : 'bg-surface text-forest border border-forest hover:bg-linen active:bg-linen/80',
        'danger' => $isDisabled
            ? 'bg-fog text-pebble border border-transparent'
            : 'bg-alarm text-paper hover:bg-alarm/90 active:bg-alarm/80',
        'ghost' => $isDisabled
            ? 'bg-transparent text-pebble'
            : 'bg-transparent text-forest hover:bg-linen active:bg-linen/80',
        default => $isDisabled
            ? 'bg-fog text-pebble border border-transparent'
            : 'bg-lime text-forest hover:bg-lime-hover active:bg-lime-hover/90',
    };

    $stateClasses = $isDisabled ? 'cursor-not-allowed pointer-events-none' : '';
    $classes = "{$baseClasses} {$variantClasses} {$stateClasses}";
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
