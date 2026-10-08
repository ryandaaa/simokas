@props([
    'label' => null,
    'jobId' => null,
    'timestamp' => null,
    'photoUrl' => null,
    'permissionDenied' => false,
    'loading' => false,
    'itemId' => null,
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
        <x-ui.skeleton variant="title" class="w-1/3 animate-pulse" />
        <x-ui.skeleton variant="image" class="aspect-[4/3] w-full animate-pulse" />
    </div>
@elseif ($permissionDenied)
    <div class="space-y-3">
        @if ($displayLabel)
            <label class="block text-sm font-semibold text-text">{{ $displayLabel }}</label>
        @endif
        <x-ui.alert variant="warning"
                    :title="__('ui.camera.permission_denied')"
                    :message="__('result.photos.permission_denied')" />
    </div>
@elseif ($photoUrl)
    {{-- Keadaan Pratinjau Foto Awal (Berasal dari URL yang sudah ada) --}}
    <div {{ $attributes->merge(['class' => 'space-y-3']) }}
         x-data="cameraCapture({
             jobId: '{{ $jobId }}',
             initialPhotoUrl: '{{ $photoUrl }}',
             formattedTime: '{{ $formattedTime }}',
             hasInitialPermissionDenied: false,
             itemId: '{{ $itemId }}'
         })">
        @if ($displayLabel)
            <label class="block text-base font-bold text-obs">{{ $displayLabel }}</label>
        @endif

        {{-- Pratinjau Gambar --}}
        <div x-show="previewUrl && !isStreaming" class="space-y-3">
            <div class="relative aspect-[4/3] w-full rounded-[10px] overflow-hidden border border-fog bg-fog/20">
                <img :src="previewUrl"
                     src="{{ $photoUrl }}"
                     alt="{{ $displayLabel }}"
                     class="w-full h-full object-cover" />

                {{-- Watermark / Cap Waktu & Job ID --}}
                <div class="absolute bottom-0 inset-x-0 bg-forest/80 backdrop-blur-xs text-paper p-3 text-xs flex justify-between items-center">
                    @if ($jobId)
                        <span class="font-mono font-medium">{{ __('ui.camera.job_id', ['id' => $jobId]) }}</span>
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
                             class="flex-1 min-h-[44px]"
                             @click="retake()"
                             {{ $attributes->whereStartsWith('wire:retake') }}>
                    {{ __('ui.camera.retake') }}
                </x-ui.button>

                <x-ui.button type="button"
                             variant="primary"
                             icon="centang"
                             class="flex-1 min-h-[44px]"
                             @click="usePhoto()"
                             ::loading="isProcessing"
                             {{ $attributes->whereStartsWith('wire:use') }}>
                    {{ __('ui.camera.use_this') }}
                </x-ui.button>
            </div>
        </div>

        {{-- Fallback jika pengguna menekan ulangi pada URL awal --}}
        <div x-show="!previewUrl && !isStreaming && !permissionDenied" x-cloak class="relative aspect-[4/3] w-full rounded-[10px] border-2 border-dashed border-pebble/40 bg-fog/20 flex flex-col items-center justify-center p-6 text-center">
            <div class="w-16 h-16 rounded-full bg-fog text-forest flex items-center justify-center mb-3">
                <x-ui.icon name="kamera" class="w-8 h-8" />
            </div>

            <p class="text-sm font-medium text-obs mb-1">
                {{ __('ui.camera.instruction') }}
            </p>

            <p class="text-xs text-slate mb-4">
                Hanya menerima tangkapan langsung dari kamera demi keabsahan inspeksi.
            </p>

            <div class="flex flex-wrap items-center justify-center gap-3">
                <x-ui.button type="button"
                             variant="secondary"
                             icon="kamera"
                             class="min-h-[44px]"
                             @click="startCamera()">
                    {{ __('result.photos.open_camera') }}
                </x-ui.button>

                <label class="cursor-pointer">
                    <input type="file"
                           accept="image/*"
                           capture="environment"
                           class="sr-only"
                           x-ref="fallbackInput"
                           @change="handleFileInput($event)"
                           {{ $attributes->whereStartsWith('wire:model') }} />
                    <span class="inline-flex items-center justify-center gap-2 min-h-[44px] px-6 py-2.5 rounded-full text-base font-medium bg-ds-blue text-white hover:bg-ds-blue-ink transition-colors">
                        <x-ui.icon name="kamera" class="w-4 h-4" />
                        <span>{{ __('ui.actions.take_photo') }}</span>
                    </span>
                </label>
            </div>
        </div>

        {{-- Live Stream Video --}}
        <div x-show="isStreaming" x-cloak class="relative aspect-[4/3] w-full rounded-xl overflow-hidden bg-text flex flex-col justify-between">
            <video x-ref="video" playsinline autoplay muted class="w-full h-full object-cover"></video>

            <div class="absolute bottom-0 inset-x-0 bg-text/80 p-3 flex items-center justify-between gap-3">
                <x-ui.button type="button" variant="ghost" class="text-surface hover:bg-surface/20 min-h-[44px]" @click="stopCamera()">
                    {{ __('ui.actions.cancel') }}
                </x-ui.button>

                <x-ui.button type="button" variant="primary" icon="kamera" class="min-h-[44px]" @click="captureFromVideo()">
                    {{ __('result.photos.capture') }}
                </x-ui.button>
            </div>
        </div>

        {{-- Permission Denied Alert --}}
        <div x-show="permissionDenied" x-cloak class="space-y-3">
            <x-ui.alert variant="warning"
                        :title="__('ui.camera.permission_denied')"
                        :message="__('result.photos.permission_denied')" />
        </div>
    </div>
@else
    {{-- Keadaan Siap Ambil Foto (Kerangka Kamera Siap Pakai) --}}
    <div {{ $attributes->merge(['class' => 'space-y-3']) }}
         x-data="cameraCapture({
             jobId: '{{ $jobId }}',
             initialPhotoUrl: null,
             formattedTime: '{{ $formattedTime }}',
             hasInitialPermissionDenied: false,
             itemId: '{{ $itemId }}'
         })">
        @if ($displayLabel)
            <label class="block text-base font-bold text-obs">{{ $displayLabel }}</label>
        @endif

        {{-- Keadaan Siap Ambil / Tombol Pemicu Kamera --}}
        <div x-show="!previewUrl && !isStreaming && !permissionDenied" class="relative aspect-[4/3] w-full rounded-[10px] border-2 border-dashed border-pebble/40 bg-fog/20 flex flex-col items-center justify-center p-6 text-center">
            <div class="w-16 h-16 rounded-full bg-fog text-forest flex items-center justify-center mb-3">
                <x-ui.icon name="kamera" class="w-8 h-8" />
            </div>

            <p class="text-sm font-medium text-obs mb-1">
                {{ __('ui.camera.instruction') }}
            </p>

            <p class="text-xs text-slate mb-4">
                Hanya menerima tangkapan langsung dari kamera demi keabsahan inspeksi.
            </p>

            <div class="flex flex-wrap items-center justify-center gap-3">
                <x-ui.button type="button"
                             variant="secondary"
                             icon="kamera"
                             class="min-h-[44px]"
                             @click="startCamera()">
                    {{ __('result.photos.open_camera') }}
                </x-ui.button>

                {{-- Input kamera tersembunyi dengan tombol pemicu bawaan HP --}}
                <label class="cursor-pointer">
                    <input type="file"
                           accept="image/*"
                           capture="environment"
                           class="sr-only"
                           x-ref="fallbackInput"
                           @change="handleFileInput($event)"
                           {{ $attributes->whereStartsWith('wire:model') }} />
                    <span class="inline-flex items-center justify-center gap-2 min-h-[44px] px-6 py-2.5 rounded-full text-base font-medium bg-ds-blue text-white hover:bg-ds-blue-ink transition-colors">
                        <x-ui.icon name="kamera" class="w-4 h-4" />
                        <span>{{ __('ui.actions.take_photo') }}</span>
                    </span>
                </label>
            </div>
        </div>

        {{-- Live Stream Video dari Browser Camera --}}
        <div x-show="isStreaming" x-cloak class="relative aspect-[4/3] w-full rounded-[10px] overflow-hidden bg-forest flex flex-col justify-between">
            <video x-ref="video" playsinline autoplay muted class="w-full h-full object-cover"></video>

            <div class="absolute bottom-0 inset-x-0 bg-forest/80 p-3 flex items-center justify-between gap-3">
                <x-ui.button type="button" variant="ghost" class="text-paper hover:bg-paper/20 min-h-[44px]" @click="stopCamera()">
                    {{ __('ui.actions.cancel') }}
                </x-ui.button>

                <x-ui.button type="button" variant="primary" icon="kamera" class="min-h-[44px]" @click="captureFromVideo()">
                    {{ __('result.photos.capture') }}
                </x-ui.button>
            </div>
        </div>

        {{-- Keadaan Pratinjau Setelah Tangkapan Kamera Berhasil --}}
        <div x-show="previewUrl && !isStreaming" x-cloak class="space-y-3">
            <div class="relative aspect-[4/3] w-full rounded-[10px] overflow-hidden border border-fog bg-fog/20">
                <img :src="previewUrl"
                     alt="{{ $displayLabel }}"
                     class="w-full h-full object-cover" />

                {{-- Watermark / Cap Waktu & Job ID --}}
                <div class="absolute bottom-0 inset-x-0 bg-forest/80 backdrop-blur-xs text-paper p-3 text-xs flex justify-between items-center">
                    @if ($jobId)
                        <span class="font-mono font-medium">{{ __('ui.camera.job_id', ['id' => $jobId]) }}</span>
                    @endif
                    <span x-text="formattedTime"></span>
                </div>
            </div>

            {{-- Tombol Aksi: Ulangi dan Pakai Foto Ini --}}
            <div class="flex items-center gap-3">
                <x-ui.button type="button"
                             variant="secondary"
                             icon="panah-putar"
                             class="flex-1 min-h-[44px]"
                             @click="retake()"
                             {{ $attributes->whereStartsWith('wire:retake') }}>
                    {{ __('ui.camera.retake') }}
                </x-ui.button>

                <x-ui.button type="button"
                             variant="primary"
                             icon="centang"
                             class="flex-1 min-h-[44px]"
                             @click="usePhoto()"
                             ::loading="isProcessing"
                             {{ $attributes->whereStartsWith('wire:use') }}>
                    {{ __('ui.camera.use_this') }}
                </x-ui.button>
            </div>
        </div>

        {{-- Izin Kamera Ditolak --}}
        <div x-show="permissionDenied" x-cloak class="space-y-3">
            <x-ui.alert variant="warning"
                        :title="__('ui.camera.permission_denied')"
                        :message="__('result.photos.permission_denied')" />
        </div>

        {{-- Galat --}}
        <div x-show="error" x-cloak>
            <x-ui.alert variant="danger" x-text="error" />
        </div>
    </div>
@endif
