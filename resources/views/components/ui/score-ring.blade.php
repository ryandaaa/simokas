@props([
    'score' => 0,
    'max' => 100,
    'recommendation' => null,
    'label' => null,
    'loading' => false,
])

@php
    $numScore = is_numeric($score) ? (float) $score : 0;
    $percentage = min(100, max(0, ($numScore / max(1, $max)) * 100));

    // Lingkaran SVG: radius 40, keliling 2 * pi * 40 = 251.327
    $circumference = 251.327;
    $dashoffset = round($circumference - ($circumference * ($percentage / 100)), 2);

    $recKey = $recommendation;
    if (!$recKey) {
        if ($percentage >= 80) {
            $recKey = 'recommended';
        } elseif ($percentage >= 60) {
            $recKey = 'caution';
        } else {
            $recKey = 'not_recommended';
        }
    }

    $variant = match ($recKey) {
        'recommended' => 'success',
        'caution' => 'warning',
        'not_recommended' => 'danger',
        default => 'primary',
    };

    $recLabel = match ($recKey) {
        'recommended' => __('ui.score_ring.recommendations.recommended'),
        'caution' => __('ui.score_ring.recommendations.caution'),
        'not_recommended' => __('ui.score_ring.recommendations.not_recommended'),
        default => (string) $recommendation,
    };

    $recIcon = match ($variant) {
        'success' => 'centang',
        'warning' => 'segitiga-peringatan',
        'danger' => 'silang',
        default => 'centang',
    };

    $strokeColorClass = match ($variant) {
        'success' => 'text-success',
        'warning' => 'text-warning',
        'danger' => 'text-danger',
        default => 'text-primary',
    };
@endphp

@if ($loading)
    <div class="flex flex-col items-center justify-center p-4 space-y-3">
        <x-ui.skeleton variant="circle" class="w-28 h-28" />
        <x-ui.skeleton variant="badge" class="w-24" />
    </div>
@else
    <div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center text-center p-4']) }}>
        <div class="relative w-28 h-28 flex items-center justify-center">
            <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100" aria-hidden="true">
                {{-- Lingkaran Latar Belakang --}}
                <circle cx="50" cy="50" r="40"
                        stroke-width="8"
                        class="text-muted/20"
                        stroke="currentColor"
                        fill="transparent" />

                {{-- Lingkaran Nilai Kemajuan --}}
                <circle cx="50" cy="50" r="40"
                        stroke-width="8"
                        stroke-dasharray="251.327"
                        stroke-dashoffset="{{ $dashoffset }}"
                        stroke-linecap="round"
                        class="{{ $strokeColorClass }} transition-all duration-500 ease-out"
                        stroke="currentColor"
                        fill="transparent" />
            </svg>

            {{-- Angka Skor di Tengah --}}
            <div class="absolute inset-0 flex flex-col items-center justify-center select-none">
                <span class="text-2xl font-bold text-text">{{ round($numScore) }}</span>
                <span class="text-[10px] text-muted font-medium uppercase tracking-wider">
                    {{ $label ?? __('ui.score_ring.score') }}
                </span>
            </div>
        </div>

        {{-- Lencana Rekomendasi --}}
        @if ($recLabel)
            <div class="mt-3">
                <x-ui.status-chip :label="$recLabel" :variant="$variant" :icon="$recIcon" />
            </div>
        @endif
    </div>
@endif
