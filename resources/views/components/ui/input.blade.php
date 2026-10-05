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
            <label for="{{ $inputId }}" class="block text-sm font-medium text-text mb-1.5">
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
                       'class' => 'w-full min-h-[44px] px-3.5 py-2.5 rounded-lg text-sm bg-surface text-text border transition-colors focus:outline-none focus:ring-2 ' .
                           ($hasError
                               ? 'border-danger focus:ring-danger/20 focus:border-danger'
                               : 'border-muted/30 focus:ring-accent focus:border-accent') .
                           ($disabled ? ' opacity-60 bg-muted/10 cursor-not-allowed' : ''),
                   ]) }} />
        </div>

        @if ($hint)
            <p id="{{ $inputId }}-hint" class="text-xs text-muted mt-1.5">
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
