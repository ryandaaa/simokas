@props([
    'date' => null,
    'compact' => false,
])

@php
    $formattedDate = null;
    if ($date instanceof \Carbon\CarbonInterface) {
        $formattedDate = $date->translatedFormat('d M Y');
    } elseif (is_string($date) && !empty($date)) {
        $formattedDate = $date;
    }
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-linen text-forest border border-forest/20 select-none']) }}>
    <x-ui.icon name="perisai-centang" class="w-3.5 h-3.5 shrink-0 text-forest" variant="solid" />
    <span>{{ __('ui.badges.inspected') }}</span>
    @if ($formattedDate && !$compact)
        <span class="text-forest/70 font-normal border-l border-forest/20 pl-1.5 ml-0.5">
            {{ $formattedDate }}
        </span>
    @endif
</span>
