@props([
    'title' => null,
    'subtitle' => null,
    'action' => null,
    'padding' => 'p-5 sm:p-6',
])

<div {{ $attributes->merge(['class' => 'bg-surface border border-slate/25 rounded-2xl overflow-hidden']) }}>
    @if ($title || $subtitle || isset($action))
        <div class="px-5 py-4 border-b border-slate/25 flex items-center justify-between gap-4">
            <div>
                @if ($title)
                    <h3 class="text-[19px] leading-tight font-semibold text-obs tracking-tight">{{ $title }}</h3>
                @endif
                @if ($subtitle)
                    <p class="text-[11px] text-slate mt-1">{{ $subtitle }}</p>
                @endif
            </div>
            @if (isset($action))
                <div class="shrink-0">
                    {{ $action }}
                </div>
            @endif
        </div>
    @endif

    <div class="{{ $padding }}">
        {{ $slot }}
    </div>
</div>
