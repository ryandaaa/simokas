@props([
    'value' => null,
    'date' => null,
    'format' => 'wib',
    'showIcon' => false,
])

@php
    $datetime = $value ?? $date;
    $carbonWib = \App\Support\Clock::toWib($datetime);

    $formatted = match ($format) {
        'date' => $carbonWib->locale('id')->translatedFormat('j M Y'),
        'time' => $carbonWib->locale('id')->translatedFormat('H.i') . ' WIB',
        'relative' => $carbonWib->locale('id')->diffForHumans(),
        default => \App\Support\Clock::formatWib($datetime),
    };
@endphp

<time datetime="{{ $carbonWib->toIso8601String() }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 text-sm text-slate']) }}>
    @if ($showIcon)
        <x-ui.icon name="jam" class="w-4 h-4 shrink-0 text-pebble" />
    @endif
    <span>{{ $formatted }}</span>
</time>
