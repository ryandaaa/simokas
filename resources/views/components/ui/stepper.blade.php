@props([
    'steps' => [],
    'current' => 0,
    'orientation' => 'horizontal',
])

@php
    $normalizedSteps = [];
    foreach ($steps as $index => $step) {
        if (is_array($step)) {
            $normalizedSteps[] = [
                'label' => $step['label'] ?? '',
                'description' => $step['description'] ?? null,
                'status' => $step['status'] ?? ($index < $current ? 'completed' : ($index === $current ? 'current' : 'upcoming')),
            ];
        } else {
            $normalizedSteps[] = [
                'label' => (string) $step,
                'description' => null,
                'status' => $index < $current ? 'completed' : ($index === $current ? 'current' : 'upcoming'),
            ];
        }
    }
    $totalSteps = count($normalizedSteps);
@endphp

<nav aria-label="Tahapan Proses" {{ $attributes->merge(['class' => 'w-full py-2']) }}>
    <ol @class([
        'flex items-center w-full' => $orientation === 'horizontal',
        'space-y-4' => $orientation === 'vertical',
    ])>
        @foreach ($normalizedSteps as $idx => $step)
            @php
                $isLast = $idx === $totalSteps - 1;
                $status = $step['status'];
            @endphp

            @if ($orientation === 'horizontal')
                <li @class([
                    'flex items-center',
                    'flex-1' => !$isLast,
                ])>
                    <div class="flex flex-col items-center">
                        {{-- Bulatan Status --}}
                        <div @class([
                            'w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-colors select-none',
                            'bg-success text-surface' => $status === 'completed',
                            'bg-primary text-surface ring-4 ring-primary/20 font-bold' => $status === 'current',
                            'bg-danger text-surface' => $status === 'failed',
                            'bg-muted/20 text-muted' => $status === 'upcoming',
                        ])
                        @if ($status === 'current') aria-current="step" @endif>
                            @if ($status === 'completed')
                                <x-ui.icon name="centang" class="w-4 h-4" variant="solid" />
                            @elseif ($status === 'failed')
                                <x-ui.icon name="silang" class="w-4 h-4" variant="solid" />
                            @else
                                <span>{{ $idx + 1 }}</span>
                            @endif
                        </div>

                        {{-- Label --}}
                        <span @class([
                            'text-xs mt-1.5 text-center font-medium max-w-[80px] truncate',
                            'text-primary font-semibold' => $status === 'current',
                            'text-text' => $status === 'completed',
                            'text-danger' => $status === 'failed',
                            'text-muted' => $status === 'upcoming',
                        ])>
                            {{ $step['label'] }}
                        </span>
                    </div>

                    {{-- Garis Penghubung --}}
                    @if (!$isLast)
                        <div @class([
                            'h-0.5 flex-1 mx-2 -mt-5',
                            'bg-success' => $status === 'completed',
                            'bg-muted/20' => $status !== 'completed',
                        ]) aria-hidden="true"></div>
                    @endif
                </li>
            @else
                <li class="flex items-start gap-3">
                    <div @class([
                        'w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0 select-none',
                        'bg-success text-surface' => $status === 'completed',
                        'bg-primary text-surface ring-4 ring-primary/20' => $status === 'current',
                        'bg-danger text-surface' => $status === 'failed',
                        'bg-muted/20 text-muted' => $status === 'upcoming',
                    ])
                    @if ($status === 'current') aria-current="step" @endif>
                        @if ($status === 'completed')
                            <x-ui.icon name="centang" class="w-4 h-4" variant="solid" />
                        @elseif ($status === 'failed')
                            <x-ui.icon name="silang" class="w-4 h-4" variant="solid" />
                        @else
                            <span>{{ $idx + 1 }}</span>
                        @endif
                    </div>

                    <div class="pt-0.5">
                        <p @class([
                            'text-sm font-medium',
                            'text-primary font-semibold' => $status === 'current',
                            'text-text' => $status === 'completed',
                            'text-danger' => $status === 'failed',
                            'text-muted' => $status === 'upcoming',
                        ])>{{ $step['label'] }}</p>

                        @if ($step['description'])
                            <p class="text-xs text-muted mt-0.5">{{ $step['description'] }}</p>
                        @endif
                    </div>
                </li>
            @endif
        @endforeach
    </ol>
</nav>
