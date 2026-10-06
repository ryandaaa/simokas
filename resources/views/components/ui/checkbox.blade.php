@props([
    'id' => null,
    'name' => null,
    'label' => null,
    'hint' => null,
    'error' => null,
    'value' => '1',
    'checked' => false,
    'required' => false,
    'disabled' => false,
    'loading' => false,
])

@php
    $checkboxId = $id ?? ($name ? 'checkbox-' . $name : 'checkbox-' . uniqid());
    $errorMessage = $error;
    if (!$errorMessage && $name && isset($errors) && $errors->has($name)) {
        $errorMessage = $errors->first($name);
    }
    $hasError = !empty($errorMessage);

    $describedBy = [];
    if ($hint) {
        $describedBy[] = $checkboxId . '-hint';
    }
    if ($hasError) {
        $describedBy[] = $checkboxId . '-error';
    }
    $ariaDescribedBy = !empty($describedBy) ? implode(' ', $describedBy) : null;
@endphp

@if ($loading)
    <div class="flex items-center gap-3 min-h-[44px]">
        <x-ui.skeleton variant="text" class="w-5 h-5 rounded" />
        <x-ui.skeleton variant="text" class="w-48 h-4" />
    </div>
@else
    <div class="relative">
        <label for="{{ $checkboxId }}" class="flex items-start gap-3 min-h-[44px] py-2 cursor-pointer select-none {{ $disabled ? 'cursor-not-allowed opacity-60' : '' }}">
            <div class="flex items-center h-5 mt-0.5">
                <input type="checkbox"
                       id="{{ $checkboxId }}"
                       @if ($name) name="{{ $name }}" @endif
                       value="{{ $value }}"
                       @checked($checked)
                       @if ($ariaDescribedBy) aria-describedby="{{ $ariaDescribedBy }}" @endif
                       aria-invalid="{{ $hasError ? 'true' : 'false' }}"
                       @if ($required) required aria-required="true" @endif
                       @if ($disabled) disabled @endif
                       {{ $attributes->merge([
                           'class' => 'w-5 h-5 rounded-[4px] border bg-surface text-forest focus:ring-forest focus:ring-offset-2 transition-colors ' .
                               ($hasError ? 'border-danger' : 'border-pebble'),
                       ]) }} />
            </div>

            <div class="text-base">
                @if ($label)
                    <span class="font-medium text-charcoal">
                        {{ $label }}
                        @if ($required)
                            <span class="text-danger ml-0.5" aria-hidden="true">*</span>
                            <span class="sr-only">(wajib)</span>
                        @endif
                    </span>
                @else
                    {{ $slot }}
                @endif

                @if ($hint)
                    <p id="{{ $checkboxId }}-hint" class="text-xs text-pebble mt-0.5">
                        {{ $hint }}
                    </p>
                @endif
            </div>
        </label>

        @if ($hasError)
            <p id="{{ $checkboxId }}-error" class="text-xs text-danger font-medium mt-1 ml-8 flex items-center gap-1" role="alert">
                <x-ui.icon name="segitiga-peringatan" class="w-3.5 h-3.5 shrink-0" />
                <span>{{ $errorMessage }}</span>
            </p>
        @endif
    </div>
@endif
