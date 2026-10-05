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
    <div class="bg-surface border border-muted/20 rounded-xl overflow-hidden animate-pulse">
        <x-ui.skeleton variant="image" class="aspect-[4/3] w-full rounded-none" />
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
    <article {{ $attributes->merge(['class' => 'bg-surface border border-muted/20 rounded-xl overflow-hidden hover:border-muted/40 hover:shadow-sm transition-all duration-150 flex flex-col']) }}>
        <a href="{{ $href ?? '#' }}" class="group block focus:outline-none focus:ring-2 focus:ring-accent rounded-xl">
            {{-- Foto Utama --}}
            <div class="relative aspect-[4/3] w-full bg-muted/10 overflow-hidden">
                @if ($imageUrl)
                    <img src="{{ $imageUrl }}"
                         alt="{{ $imageAlt ?? $title }}"
                         loading="lazy"
                         width="400"
                         height="300"
                         class="w-full h-full object-cover group-hover:scale-102 transition-transform duration-200" />
                @else
                    <div class="w-full h-full flex flex-col items-center justify-center text-muted/50 bg-muted/5">
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
                    <h3 class="font-semibold text-text text-base line-clamp-2 group-hover:text-primary transition-colors">
                        {{ $title }}
                    </h3>

                    <div class="mt-2">
                        <x-ui.money :amount="$price" class="text-lg font-bold text-primary" />
                    </div>
                </div>

                {{-- Spesifikasi Ringkas: Tahun, Jarak, Area --}}
                <div class="mt-3 pt-3 border-t border-muted/15 flex items-center justify-between text-xs text-muted">
                    <div class="flex items-center gap-2">
                        @if ($year)
                            <span>{{ $year }}</span>
                        @endif
                        @if ($year && $mileage)
                            <span class="text-muted/40">•</span>
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
