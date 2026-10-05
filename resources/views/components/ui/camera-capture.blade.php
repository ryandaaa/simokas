@props([
    'label' => null,
    'jobId' => null,
    'timestamp' => null,
    'photoUrl' => null,
    'permissionDenied' => false,
    'loading' => false,
])

@php
    $displayLabel = $label ?? __('ui.camera.title');
    $formattedTime = null;
    if ($timestamp instanceof \Carbon\CarbonInterface) {
        $formattedTime = \App\Support\Clock::formatWib($timestamp);
    } elseif (is_string($timestamp) && !empty($timestamp)) {
        $formattedTime = $timestamp;
    }
@endphp

@if ($loading)
    <div class="space-y-3">
        <x-ui.skeleton variant="title" class="w-1/3" />
        <x-ui.skeleton variant="image" class="aspect-[4/3] w-full" />
    </div>
@elseif ($permissionDenied)
    <div class="space-y-3">
        @if ($displayLabel)
            <label class="block text-sm font-semibold text-text">{{ $displayLabel }}</label>
        @endif
        <x-ui.alert variant="warning"
                    :title="__('ui.camera.permission_denied')"
                    message="Buka pengaturan peramban Anda, izinkan akses kamera untuk situs ini, lalu segarkan halaman." />
    </div>
@elseif ($photoUrl)
    {{-- Keadaan Pratinjau Foto --}}
    <div {{ $attributes->merge(['class' => 'space-y-3']) }}>
        @if ($displayLabel)
            <label class="block text-sm font-semibold text-text">{{ $displayLabel }}</label>
        @endif

        <div class="relative aspect-[4/3] w-full rounded-xl overflow-hidden border border-muted/20 bg-muted/10">
            <img src="{{ $photoUrl }}"
                 alt="{{ $displayLabel }}"
                 class="w-full h-full object-cover" />

            {{-- Watermark / Cap Waktu & Job ID --}}
            <div class="absolute bottom-0 inset-x-0 bg-text/60 backdrop-blur-xs text-surface p-3 text-xs flex justify-between items-center">
                @if ($jobId)
                    <span class="font-mono">{{ __('ui.camera.job_id', ['id' => $jobId]) }}</span>
                @endif
                @if ($formattedTime)
                    <span>{{ $formattedTime }}</span>
                @endif
            </div>
        </div>

        {{-- Tombol Aksi: Ulangi dan Pakai Foto Ini --}}
        <div class="flex items-center gap-3">
            <x-ui.button type="button"
                         variant="secondary"
                         icon="panah-putar"
                         class="flex-1"
                         {{ $attributes->whereStartsWith('wire:retake') }}>
                {{ __('ui.camera.retake') }}
            </x-ui.button>

            <x-ui.button type="button"
                         variant="primary"
                         icon="centang"
                         class="flex-1"
                         {{ $attributes->whereStartsWith('wire:use') }}>
                {{ __('ui.camera.use_this') }}
            </x-ui.button>
        </div>
    </div>
@else
    {{-- Keadaan Siap Ambil Foto (Kerangka Kamera) --}}
    <div {{ $attributes->merge(['class' => 'space-y-3']) }}>
        @if ($displayLabel)
            <label class="block text-sm font-semibold text-text">{{ $displayLabel }}</label>
        @endif

        <div class="relative aspect-[4/3] w-full rounded-xl border-2 border-dashed border-muted/30 bg-muted/5 flex flex-col items-center justify-center p-6 text-center">
            <div class="w-16 h-16 rounded-full bg-primary/10 text-primary flex items-center justify-center mb-3">
                <x-ui.icon name="kamera" class="w-8 h-8" />
            </div>

            <p class="text-sm font-medium text-text mb-1">
                {{ __('ui.camera.instruction') }}
            </p>

            <p class="text-xs text-muted mb-4">
                Hanya menerima tangkapan langsung dari kamera demi keabsahan inspeksi.
            </p>

            {{-- Input kamera tersembunyi dengan tombol pemicu --}}
            <label class="cursor-pointer">
                <input type="file"
                       accept="image/*"
                       capture="environment"
                       class="sr-only"
                       {{ $attributes->whereStartsWith('wire:model') }} />
                <span class="inline-flex items-center justify-center gap-2 min-h-[44px] px-5 py-2.5 rounded-lg text-sm font-semibold bg-primary text-surface hover:bg-primary/90 transition-colors focus-within:ring-2 focus-within:ring-accent">
                    <x-ui.icon name="kamera" class="w-4 h-4" />
                    <span>{{ __('ui.actions.take_photo') }}</span>
                </span>
            </label>
        </div>
    </div>
@endif
