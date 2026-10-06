@props([
    'label',
    'variant' => 'muted',
    'icon' => null,
    'iconVariant' => 'solid',
])

@php
    $variantClasses = match ($variant) {
        'success' => 'bg-lime text-forest border-lime',
        'warning' => 'bg-warning/10 text-warning border-warning/30',
        'danger' => 'bg-alarm/10 text-alarm border-alarm/30',
        'primary' => 'bg-linen text-forest border-forest/20',
        default => 'bg-fog text-pebble border-fog',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium border select-none ' . $variantClasses]) }}>
    @if ($icon)
        <x-ui.icon :name="$icon" :variant="$iconVariant" class="w-3.5 h-3.5 shrink-0" />
    @endif
    <span>{{ $label }}</span>
</span>
