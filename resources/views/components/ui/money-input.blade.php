@props([
    'id' => null,
    'name' => null,
    'label' => null,
    'value' => null,
    'placeholder' => '0',
    'form' => null,
    'required' => false,
    'disabled' => false,
    'hint' => null,
    'error' => null,
])

@php
    $inputId = $id ?? ($name ? 'money-' . $name : 'money-' . uniqid());
    $cleanInitial = $value !== null && $value !== '' ? preg_replace('/\D/', '', (string) $value) : '';
    $initialRaw = $cleanInitial !== '' ? (string) (int) $cleanInitial : '';
    $initialDisplay = $initialRaw !== '' ? number_format((int) $initialRaw, 0, ',', '.') : '';
@endphp

<div x-data="{
        raw: @js($initialRaw),
        display: @js($initialDisplay),
        format(val) {
            if (!val && val !== 0 && val !== '0') return '';
            const clean = val.toString().replace(/\D/g, '');
            if (!clean) return '';
            return new Intl.NumberFormat('id-ID').format(clean);
        },
        update(e) {
            const clean = e.target.value.replace(/\D/g, '');
            this.raw = clean;
            this.display = this.format(clean);
            e.target.value = this.display;
        }
    }"
    class="w-full">
    @if ($label)
        <label for="{{ $inputId }}-display" class="block text-xs font-bold text-ds-ink mb-1.5">
            {{ $label }}
            @if ($required)
                <span class="text-ds-danger ml-0.5" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    {{-- Input tersembunyi dengan angka murni untuk submit formulir --}}
    <input type="hidden"
           id="{{ $inputId }}"
           @if ($name) name="{{ $name }}" @endif
           @if ($form) form="{{ $form }}" @endif
           value="{{ $initialRaw }}"
           :value="raw"
    />

    {{-- Input teks visual yang diformat dengan titik pemisah ribuan --}}
    <div class="relative">
        <input type="text"
               id="{{ $inputId }}-display"
               inputmode="numeric"
               autocomplete="one-time-code"
               autocorrect="off"
               autocapitalize="off"
               spellcheck="false"
               data-lpignore="true"
               data-form-type="other"
               readonly
               onfocus="this.removeAttribute('readonly');"
               onblur="this.setAttribute('readonly', 'readonly');"
               placeholder="{{ $placeholder }}"
               :value="display"
               value="{{ $initialDisplay }}"
               @input="update($event)"
               @if ($disabled) disabled @endif
               class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors disabled:opacity-50 disabled:bg-ds-soft"
        />
    </div>

    @if ($hint)
        <p class="text-[11px] text-ds-muted mt-1">{{ $hint }}</p>
    @endif
    @if ($error)
        <p class="text-[11px] text-ds-danger mt-1">{{ $error }}</p>
    @endif
</div>
