@props([
    'name' => null,
    'show' => false,
    'title' => null,
    'description' => null,
    'confirmText' => null,
    'confirmVariant' => 'primary',
    'cancelText' => null,
    'maxWidth' => 'max-w-md',
])

@php
    $modalId = $name ?? 'modal-' . uniqid();
    $titleId = $modalId . '-title';
    $descId = $modalId . '-desc';
@endphp

<div x-data="{
        open: @js($show),
        triggerEl: null,
        init() {
            this.$watch('open', value => {
                if (value) {
                    this.triggerEl = document.activeElement;
                } else if (this.triggerEl) {
                    this.$nextTick(() => {
                        if (this.triggerEl && typeof this.triggerEl.focus === 'function') {
                            this.triggerEl.focus();
                        }
                    });
                }
            });
        }
     }"
     x-on:open-modal.window="if ($event.detail === '{{ $modalId }}' || $event.detail?.[0] === '{{ $modalId }}') open = true"
     x-on:close-modal.window="if ($event.detail === '{{ $modalId }}' || $event.detail?.[0] === '{{ $modalId }}') open = false"
     x-on:keydown.escape.window="open = false"
     x-show="open"
     x-trap="open"
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     role="dialog"
     aria-modal="true"
     aria-labelledby="{{ $title ? $titleId : '' }}"
     aria-describedby="{{ $description ? $descId : '' }}">

    {{-- Latar Belakang / Backdrop --}}
    <div x-show="open"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-ds-ink/50 backdrop-blur-xs transition-opacity"
         @click="open = false"
         aria-hidden="true"></div>

    {{-- Wadah Dialog Tengah --}}
    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div x-show="open"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             @click.outside="open = false"
             @click.stop
             class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all w-full {{ $maxWidth }} p-6 sm:p-8 border border-ds-line/80">

            {{-- Tombol Tutup --}}
            <button type="button"
                    @click="open = false"
                    class="absolute top-5 right-5 w-9 h-9 rounded-full bg-ds-soft text-ds-muted hover:text-ds-ink flex items-center justify-center"
                    aria-label="{{ __('ui.actions.close') }}">
                <x-ui.icon name="silang" class="w-5 h-5" />
            </button>

            {{-- Judul dan Deskripsi --}}
            <div class="mb-5 pr-10">
                @if ($title)
                    <h3 id="{{ $titleId }}" class="text-lg font-extrabold text-ds-ink">
                        {{ $title }}
                    </h3>
                @endif

                @if ($description)
                    <p id="{{ $descId }}" class="mt-1.5 text-sm text-ds-muted">
                        {{ $description }}
                    </p>
                @endif
            </div>

            {{-- Slot Isi Modal --}}
            <div class="text-sm text-ds-ink leading-relaxed">
                {{ $slot }}
            </div>

            {{-- Tombol Tindakan / Footer --}}
            @if (isset($footer))
                <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-4 border-t border-ds-line/60">
                    {{ $footer }}
                </div>
            @elseif ($confirmText || $cancelText)
                <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-4 border-t border-ds-line/60">
                    <x-ui.button type="button"
                                 variant="secondary"
                                 @click="open = false">
                        {{ $cancelText ?? __('ui.actions.cancel') }}
                    </x-ui.button>

                    @if ($confirmText)
                        <x-ui.button type="button"
                                     :variant="$confirmVariant"
                                     {{ $attributes->whereStartsWith('wire:') }}>
                            {{ $confirmText }}
                        </x-ui.button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
