@props([
    'id' => null,
    'name' => null,
    'type' => 'text',
    'label' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'disabled' => false,
    'loading' => false,
])

@php
    $inputId = $id ?? ($name ? 'input-' . $name : 'input-' . uniqid());
    $errorMessage = $error;
    if (!$errorMessage && $name && isset($errors) && $errors->has($name)) {
        $errorMessage = $errors->first($name);
    }
    $hasError = !empty($errorMessage);

    $describedBy = [];
    if ($hint) {
        $describedBy[] = $inputId . '-hint';
    }
    if ($hasError) {
        $describedBy[] = $inputId . '-error';
    }
    $ariaDescribedBy = !empty($describedBy) ? implode(' ', $describedBy) : null;
@endphp

@if ($loading)
    <div class="w-full space-y-1.5">
        <x-ui.skeleton variant="text" class="w-24 h-4" />
        <x-ui.skeleton variant="button" class="w-full h-11" />
    </div>
@else
    <div class="w-full">
        @if ($label)
            <label for="{{ $inputId }}" class="block text-base font-medium text-charcoal mb-1.5">
                {{ $label }}
                @if ($required)
                    <span class="text-danger ml-0.5" aria-hidden="true">*</span>
                    <span class="sr-only">(wajib diisi)</span>
                @endif
            </label>
        @endif

        <div class="relative">
            <input type="{{ $type }}"
                   id="{{ $inputId }}"
                   @if ($name) name="{{ $name }}" @endif
                   @if ($ariaDescribedBy) aria-describedby="{{ $ariaDescribedBy }}" @endif
                   aria-invalid="{{ $hasError ? 'true' : 'false' }}"
                   @if ($required) required aria-required="true" @endif
                   @if ($disabled) disabled @endif
                   {{ $attributes->merge([
                       'class' => 'w-full h-11 min-h-[44px] px-4 py-2.5 rounded-[10px] text-base bg-surface text-forest border placeholder:text-pebble transition-colors focus:outline-none focus:ring-0 shadow-none focus:shadow-none ' .
                           ($hasError
                               ? 'border-danger focus:border-danger'
                               : 'border-pebble focus:border-forest') .
                           ($disabled ? ' opacity-60 bg-fog text-pebble cursor-not-allowed' : ''),
                   ]) }} />
        </div>

        @if ($hint)
            <p id="{{ $inputId }}-hint" class="text-xs text-pebble mt-1.5">
                {{ $hint }}
            </p>
        @endif

        @if ($hasError)
            <p id="{{ $inputId }}-error" class="text-xs text-danger font-medium mt-1.5 flex items-center gap-1" role="alert">
                <x-ui.icon name="segitiga-peringatan" class="w-3.5 h-3.5 shrink-0" />
                <span>{{ $errorMessage }}</span>
            </p>
        @endif
    </div>
@endif
