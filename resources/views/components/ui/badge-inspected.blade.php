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

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-accent/15 text-accent border border-accent/30 select-none']) }}>
    <x-ui.icon name="perisai-centang" class="w-3.5 h-3.5 shrink-0" variant="solid" />
    <span>{{ __('ui.badges.inspected') }}</span>
    @if ($formattedDate && !$compact)
        <span class="text-accent/80 font-normal border-l border-accent/40 pl-1.5 ml-0.5">
            {{ $formattedDate }}
        </span>
    @endif
</span>
