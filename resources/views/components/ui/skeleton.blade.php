@props([
    'variant' => 'text',
])

@php
    $variantClasses = match ($variant) {
        'title' => 'h-6 w-3/4 rounded-md',
        'card' => 'h-48 w-full rounded-xl',
        'avatar' => 'h-10 w-10 rounded-full shrink-0',
        'button' => 'h-11 w-28 rounded-lg',
        'image' => 'aspect-video w-full rounded-xl',
        'badge' => 'h-6 w-20 rounded-full',
        'circle' => 'h-12 w-12 rounded-full shrink-0',
        default => 'h-4 w-full rounded-md',
    };
@endphp

<div {{ $attributes->merge(['class' => 'animate-pulse bg-muted/20 ' . $variantClasses]) }} aria-hidden="true"></div>
