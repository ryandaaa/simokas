@props([
    'id' => null,
    'name' => null,
    'label' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'form' => null,
    'submitOnChange' => false,
    'fullWidth' => true,
    'inlineLabel' => null,
    'dropup' => false,
])

@php
    $selectId = $id ?? ($name ? 'select-' . $name : 'select-' . uniqid());

    $normalizedOptions = [];
    foreach ($options as $val => $opt) {
        if (is_array($opt)) {
            $normalizedOptions[(string) ($opt['value'] ?? $val)] = (string) ($opt['label'] ?? $val);
        } else {
            $normalizedOptions[(string) $val] = (string) $opt;
        }
    }

    $currentVal = (string) ($selected ?? array_key_first($normalizedOptions) ?? '');
    $currentLabel = $normalizedOptions[$currentVal] ?? ($placeholder ?? (reset($normalizedOptions) ?: ''));
@endphp

<div x-data="{
        open: false,
        value: @js($currentVal),
        label: @js($currentLabel),
        select(val, lbl) {
            this.value = val;
            this.label = lbl;
            this.open = false;
            @if ($submitOnChange)
                $nextTick(() => {
                    @if ($form)
                        document.getElementById(@js($form))?.requestSubmit();
                    @else
                        $el.closest('form')?.requestSubmit();
                    @endif
                });
            @endif
        }
    }"
    class="relative {{ $fullWidth ? 'w-full' : 'inline-block' }}"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
>
    @if ($label)
        <label for="{{ $selectId }}-btn" class="block text-xs font-bold text-ds-ink mb-1.5">
            {{ $label }}
        </label>
    @endif

    {{-- Input tersembunyi untuk integrasi form --}}
    <input type="hidden"
           id="{{ $selectId }}"
           @if ($name) name="{{ $name }}" @endif
           @if ($form) form="{{ $form }}" @endif
           value="{{ $currentVal }}"
           :value="value"
    />

    {{-- Tombol Trigger Kustom (Bukan Select Bawaan OS) --}}
    <button type="button"
            id="{{ $selectId }}-btn"
            @click="open = !open"
            class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line hover:border-ds-blue focus:outline-none focus:ring-2 focus:ring-ds-blue/20 transition-all flex items-center justify-between gap-3 shadow-xs select-none"
            aria-haspopup="listbox"
            :aria-expanded="open.toString()">
        <span class="flex items-center gap-2 truncate">
            @if ($inlineLabel)
                <span class="text-xs font-semibold text-ds-muted shrink-0">{{ $inlineLabel }}:</span>
            @endif
            <span class="truncate font-semibold text-ds-ink text-xs sm:text-sm" x-text="label"></span>
        </span>
        <i aria-hidden="true"
           class="fa-solid fa-chevron-down text-xs text-ds-faint transition-transform duration-200 shrink-0"
           :class="{ 'rotate-180 text-ds-blue': open }">
        </i>
    </button>

    {{-- Menu Dropdown Mengambang Kustom (Custom CSS / Design System) --}}
    <div x-show="open"
         x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 {{ $dropup ? 'translate-y-1' : '-translate-y-1' }} scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 {{ $dropup ? 'translate-y-1' : '-translate-y-1' }} scale-95"
         class="absolute z-50 {{ $dropup ? 'bottom-full mb-1.5' : 'top-full mt-1.5' }} w-full bg-white border border-ds-line/80 rounded-2xl shadow-xl py-1.5 overflow-hidden text-sm max-h-60 overflow-y-auto"
         role="listbox">
        @if ($placeholder && !isset($normalizedOptions['']))
            <button type="button"
                    @click="select('', @js($placeholder))"
                    class="w-full text-left px-4 py-2.5 flex items-center justify-between text-xs font-semibold hover:bg-ds-blue-soft hover:text-ds-blue transition-colors text-ds-muted"
                    :class="{ 'bg-ds-blue-soft text-ds-blue font-bold': value === '' }">
                <span>{{ $placeholder }}</span>
                <i x-show="value === ''" aria-hidden="true" class="fa-solid fa-check text-ds-blue text-xs"></i>
            </button>
        @endif

        @foreach ($normalizedOptions as $optVal => $optText)
            <button type="button"
                    @click="select(@js((string) $optVal), @js($optText))"
                    class="w-full text-left px-4 py-2.5 flex items-center justify-between text-xs font-semibold hover:bg-ds-blue-soft hover:text-ds-blue transition-colors text-ds-ink"
                    :class="{ 'bg-ds-blue-soft text-ds-blue font-bold': value === @js((string) $optVal) }">
                <span>{{ $optText }}</span>
                <i x-show="value === @js((string) $optVal)" aria-hidden="true" class="fa-solid fa-check text-ds-blue text-xs"></i>
            </button>
        @endforeach
    </div>
</div>
