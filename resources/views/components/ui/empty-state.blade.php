@props([
    'icon' => 'dokumen',
    'title' => null,
    'description' => null,
    'actionLabel' => null,
    'actionUrl' => null,
    'actionIcon' => null,
    'actionClick' => null,
])

@php
    $displayTitle = $title ?? __('ui.empty.title');
    $displayDesc = $description ?? __('ui.empty.description');
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center text-center p-8 sm:p-12 bg-surface border border-dashed border-pebble/40 rounded-[10px] my-4']) }}>
    <div class="w-14 h-14 rounded-full bg-fog flex items-center justify-center text-forest mb-4">
        <x-ui.icon :name="$icon" class="w-7 h-7" />
    </div>

    <h3 class="text-title font-bold text-obs mb-1">
        {{ $displayTitle }}
    </h3>

    <p class="text-sm text-slate max-w-sm mb-6">
        {{ $displayDesc }}
    </p>

    @if ($actionLabel && $actionUrl)
        <x-ui.button :href="$actionUrl" variant="primary" :icon="$actionIcon">
            {{ $actionLabel }}
        </x-ui.button>
    @elseif ($actionLabel && $actionClick)
        <x-ui.button type="button" @click="{{ $actionClick }}" variant="primary" :icon="$actionIcon">
            {{ $actionLabel }}
        </x-ui.button>
    @elseif ($slot->isNotEmpty())
        <div>
            {{ $slot }}
        </div>
    @endif
</div>
