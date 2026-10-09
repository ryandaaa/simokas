@props([
    'value',
    'label',
    'href' => null,
])

@php
    $baseClasses = 'bg-surface border border-fog rounded-[10px] p-4 flex flex-col justify-between transition-all duration-150 min-h-[88px] min-w-[130px] sm:min-w-[140px] select-none';
    $isClickable = ! empty($href);
    $interactiveClasses = $isClickable ? 'hover:border-pebble active:bg-fog/30 cursor-pointer' : '';
    $classes = "{$baseClasses} {$interactiveClasses}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes . ' block']) }}>
        <p class="text-2xl sm:text-3xl font-extrabold text-obs tracking-tight">{{ $value }}</p>
        <p class="text-xs font-medium text-slate mt-1">{{ $label }}</p>
    </a>
@else
    <div {{ $attributes->merge(['class' => $classes]) }}>
        <p class="text-2xl sm:text-3xl font-extrabold text-obs tracking-tight">{{ $value }}</p>
        <p class="text-xs font-medium text-slate mt-1">{{ $label }}</p>
    </div>
@endif
