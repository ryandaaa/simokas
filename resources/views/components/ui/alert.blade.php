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
        'success' => 'bg-success/10 text-success border-success/30',
        'warning' => 'bg-warning/10 text-warning border-warning/30',
        'danger' => 'bg-danger/10 text-danger border-danger/30',
        default => 'bg-primary/10 text-primary border-primary/30',
    };

    $role = in_array($variant, ['danger', 'warning']) ? 'alert' : 'status';
@endphp

<div role="{{ $role }}"
     @if ($dismissible) x-data="{ show: true }" x-show="show" x-transition.opacity @endif
     {{ $attributes->merge(['class' => 'p-4 rounded-xl border flex items-start gap-3 text-sm ' . $variantClasses]) }}>
    <x-ui.icon :name="$resolvedIcon" variant="solid" class="w-5 h-5 shrink-0 mt-0.5" />

    <div class="flex-1">
        @if ($title)
            <h4 class="font-semibold text-text mb-0.5">{{ $title }}</h4>
        @endif

        <div class="text-text/90">
            @if ($message)
                <p>{{ $message }}</p>
            @endif
            {{ $slot }}
        </div>

        @if ($retryLabel || $retryHref || $retryAction)
            <div class="mt-3">
                @if ($retryHref)
                    <x-ui.button :href="$retryHref" variant="secondary" class="text-xs py-1 px-3 min-h-[36px]" icon="panah-putar">
                        {{ $retryLabel ?? __('ui.actions.retry') }}
                    </x-ui.button>
                @elseif ($retryAction)
                    <button type="button"
                            wire:click="{{ $retryAction }}"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-surface border border-muted/30 text-text hover:bg-muted/10 transition-colors focus:outline-none focus:ring-2 focus:ring-accent">
                        <x-ui.icon name="panah-putar" class="w-3.5 h-3.5" />
                        <span>{{ $retryLabel ?? __('ui.actions.retry') }}</span>
                    </button>
                @endif
            </div>
        @endif
    </div>

    @if ($dismissible)
        <button type="button"
                @click="show = false"
                class="shrink-0 p-1 text-muted hover:text-text rounded-md focus:outline-none focus:ring-2 focus:ring-accent"
                aria-label="{{ __('ui.actions.close') }}">
            <x-ui.icon name="silang" class="w-4 h-4" />
        </button>
    @endif
</div>
