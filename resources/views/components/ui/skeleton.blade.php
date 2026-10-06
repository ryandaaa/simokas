@props([
    'variant' => 'text',
])

@php
    $variantClasses = match ($variant) {
        'title' => 'h-6 w-3/4 rounded-[6px]',
        'card' => 'h-48 w-full rounded-[10px]',
        'avatar' => 'h-10 w-10 rounded-full shrink-0',
        'button' => 'h-11 w-28 rounded-full',
        'image' => 'aspect-video w-full rounded-[10px]',
        'badge' => 'h-6 w-20 rounded-full',
        'circle' => 'h-12 w-12 rounded-full shrink-0',
        default => 'h-4 w-full rounded-[6px]',
    };
@endphp

<div {{ $attributes->merge(['class' => 'animate-pulse bg-fog ' . $variantClasses]) }} aria-hidden="true"></div>
