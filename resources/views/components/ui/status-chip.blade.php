@props([
    'label',
    'variant' => 'muted',
    'icon' => null,
    'iconVariant' => 'solid',
])

@php
    $variantClasses = match ($variant) {
        'success' => 'bg-success/10 text-success border-success/30',
        'warning' => 'bg-warning/10 text-warning border-warning/30',
        'danger' => 'bg-danger/10 text-danger border-danger/30',
        'primary' => 'bg-primary/10 text-primary border-primary/30',
        default => 'bg-muted/15 text-muted border-muted/30',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border select-none ' . $variantClasses]) }}>
    @if ($icon)
        <x-ui.icon :name="$icon" :variant="$iconVariant" class="w-3.5 h-3.5 shrink-0" />
    @endif
    <span>{{ $label }}</span>
</span>
