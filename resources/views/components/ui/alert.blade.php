@props([
    'variant' => 'info',
    'title' => null,
    'message' => null,
    'icon' => null,
    'dismissible' => false,
    'retryLabel' => null,
    'retryHref' => null,
    'retryAction' => null,
])

@php
    $resolvedIcon = $icon ?? match ($variant) {
        'success' => 'lingkaran-centang',
        'warning' => 'segitiga-peringatan',
        'danger' => 'lingkaran-silang',
        default => 'information-circle',
    };

    $variantClasses = match ($variant) {
        'success' => 'bg-linen text-forest border-forest/30',
        'warning' => 'bg-warning/10 text-warning border-warning/30',
        'danger' => 'bg-alarm/10 text-alarm border-alarm/30',
        default => 'bg-signal/10 text-signal border-signal/30',
    };

    $role = in_array($variant, ['danger', 'warning']) ? 'alert' : 'status';
@endphp

<div role="{{ $role }}"
     @if ($dismissible) x-data="{ show: true }" x-show="show" x-transition.opacity @endif
     {{ $attributes->merge(['class' => 'p-4 rounded-[10px] border flex items-start gap-3 text-sm ' . $variantClasses]) }}>
    <x-ui.icon :name="$resolvedIcon" variant="solid" class="w-5 h-5 shrink-0 mt-0.5" />

    <div class="flex-1">
        @if ($title)
            <h4 class="font-bold text-obs mb-0.5">{{ $title }}</h4>
        @endif

        <div class="text-charcoal leading-relaxed">
            @if ($message)
                <p>{{ $message }}</p>
            @endif
            {{ $slot }}
        </div>

        @if ($retryLabel || $retryHref || $retryAction)
            <div class="mt-3">
                @if ($retryHref)
                    <x-ui.button :href="$retryHref" variant="secondary" class="!min-h-[36px] !px-4 !py-1.5 !text-sm" icon="panah-putar">
                        {{ $retryLabel ?? __('ui.actions.retry') }}
                    </x-ui.button>
                @elseif ($retryAction)
                    <x-ui.button type="button"
                                 variant="secondary"
                                 wire:click="{{ $retryAction }}"
                                 class="!min-h-[36px] !px-4 !py-1.5 !text-sm"
                                 icon="panah-putar">
                        {{ $retryLabel ?? __('ui.actions.retry') }}
                    </x-ui.button>
                @endif
            </div>
        @endif
    </div>

    @if ($dismissible)
        <button type="button"
                @click="show = false"
                class="shrink-0 p-1 text-ds-muted hover:text-ds-ink rounded-md"
                aria-label="{{ __('ui.actions.close') }}">
            <x-ui.icon name="silang" class="w-4 h-4" />
        </button>
    @endif
</div>
