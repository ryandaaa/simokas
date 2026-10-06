@props([
    'amount' => null,
    'value' => null,
])

@php
    $raw = $amount ?? $value ?? 0;
    $intAmount = is_numeric($raw) ? (int) $raw : 0;
    $formatted = \App\Support\Money::format($intAmount);
@endphp

<span {{ $attributes->merge(['class' => 'font-bold tracking-tight text-forest']) }}>{{ $formatted }}</span>
