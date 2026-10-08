@props([
    'title',
    'price',
    'year' => null,
    'mileage' => null,
    'area' => null,
    'imageUrl' => null,
    'imageAlt' => null,
    'inspected' => false,
    'inspectedDate' => null,
    'statusLabel' => null,
    'statusVariant' => 'muted',
    'statusIcon' => null,
    'href' => null,
    'loading' => false,
])

@if ($loading)
    <div class="bg-surface border border-fog rounded-[10px] overflow-hidden animate-pulse">
        <x-ui.skeleton variant="image" class="aspect-[16/10] w-full rounded-none" />
        <div class="p-4 space-y-3">
            <x-ui.skeleton variant="title" class="w-3/4" />
            <x-ui.skeleton variant="text" class="w-1/2" />
            <div class="flex gap-2 pt-2">
                <x-ui.skeleton variant="badge" class="w-16" />
                <x-ui.skeleton variant="badge" class="w-20" />
            </div>
        </div>
    </div>
@else
    <article {{ $attributes->merge(['class' => 'bg-surface border border-fog rounded-[10px] overflow-hidden hover:border-pebble transition-all duration-150 flex flex-col']) }}>
        <a href="{{ $href ?? '#' }}" class="group block rounded-[10px]">
            {{-- Foto Utama --}}
            <div class="relative aspect-[16/10] w-full bg-fog overflow-hidden">
                @if ($imageUrl)
                    <img src="{{ $imageUrl }}"
                         alt="{{ $imageAlt ?? $title }}"
                         loading="lazy"
                         width="400"
                         height="250"
                         class="w-full h-full object-cover group-hover:scale-102 transition-transform duration-200" />
                @else
                    <div class="w-full h-full flex flex-col items-center justify-center text-pebble bg-fog">
                        <x-ui.icon name="foto" class="w-12 h-12 stroke-[1.2]" />
                    </div>
                @endif

                {{-- Badges / Status Chips di atas foto --}}
                <div class="absolute top-2.5 left-2.5 flex flex-wrap gap-1.5 items-center">
                    @if ($inspected)
                        <x-ui.badge-inspected :date="$inspectedDate" :compact="true" />
                    @endif

                    @if ($statusLabel)
                        <x-ui.status-chip :label="$statusLabel" :variant="$statusVariant" :icon="$statusIcon" />
                    @endif
                </div>
            </div>

            {{-- Informasi Kendaraan --}}
            <div class="p-4 flex-1 flex flex-col justify-between">
                <div>
                    <h3 class="font-bold text-obs text-base line-clamp-2 group-hover:text-forest transition-colors">
                        {{ $title }}
                    </h3>

                    <div class="mt-2">
                        <x-ui.money :amount="$price" class="text-lg font-bold text-forest" />
                    </div>
                </div>

                {{-- Spesifikasi Ringkas: Tahun, Jarak, Area --}}
                <div class="mt-3 pt-3 border-t border-fog flex items-center justify-between text-xs text-slate">
                    <div class="flex items-center gap-2">
                        @if ($year)
                            <span>{{ $year }}</span>
                        @endif
                        @if ($year && $mileage)
                            <span class="text-pebble">•</span>
                        @endif
                        @if ($mileage !== null)
                            <span>{{ number_format((int) $mileage, 0, ',', '.') }} km</span>
                        @endif
                    </div>

                    @if ($area)
                        <span class="truncate max-w-[120px] text-right">{{ $area }}</span>
                    @endif
                </div>
            </div>
        </a>
    </article>
@endif
