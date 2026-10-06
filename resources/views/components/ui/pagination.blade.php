@props([
    'paginator' => null,
    'currentPage' => 1,
    'totalPages' => 1,
    'hasPrevious' => null,
    'hasNext' => null,
    'previousUrl' => null,
    'nextUrl' => null,
    'total' => null,
])

@php
    if ($paginator instanceof \Illuminate\Contracts\Pagination\Paginator) {
        $cur = $paginator->currentPage();
        $totalP = method_exists($paginator, 'lastPage') ? $paginator->lastPage() : $cur + ($paginator->hasMorePages() ? 1 : 0);
        $hasPrev = !$paginator->onFirstPage();
        $hasNext = $paginator->hasMorePages();
        $prevUrl = $paginator->previousPageUrl();
        $nextUrl = $paginator->nextPageUrl();
        $totalItems = method_exists($paginator, 'total') ? $paginator->total() : null;
    } else {
        $cur = (int) $currentPage;
        $totalP = (int) $totalPages;
        $hasPrev = $hasPrevious ?? ($cur > 1);
        $hasNext = $hasNext ?? ($cur < $totalP);
        $prevUrl = $previousUrl;
        $nextUrl = $nextUrl;
        $totalItems = $total;
    }
@endphp

<nav role="navigation" aria-label="Navigasi Halaman" {{ $attributes->merge(['class' => 'flex flex-col sm:flex-row items-center justify-between gap-4 py-3']) }}>
    {{-- Info Hasil --}}
    <div class="text-sm text-slate">
        @if ($totalItems !== null)
            <span>{{ __('ui.pagination.showing', ['default' => 'Menampilkan']) }}</span>
            <span class="font-bold text-obs">{{ $totalItems }}</span>
            <span>{{ __('ui.pagination.results', ['default' => 'hasil']) }}</span>
            <span class="text-pebble">({{ __('ui.stepper.step', ['step' => "{$cur} / {$totalP}"]) }})</span>
        @else
            <span>Halaman <span class="font-bold text-obs">{{ $cur }}</span> dari <span class="font-bold text-obs">{{ $totalP }}</span></span>
        @endif
    </div>

    {{-- Tombol Navigasi --}}
    <div class="flex items-center gap-2">
        <x-ui.button :href="$hasPrev ? $prevUrl : null"
                     variant="secondary"
                     icon="chevron-left"
                     :disabled="!$hasPrev"
                     class="min-h-[44px] px-3.5"
                     aria-label="Halaman Sebelumnya">
            <span>{{ __('pagination.previous') }}</span>
        </x-ui.button>

        <x-ui.button :href="$hasNext ? $nextUrl : null"
                     variant="secondary"
                     iconRight="chevron-right"
                     :disabled="!$hasNext"
                     class="min-h-[44px] px-3.5"
                     aria-label="Halaman Berikutnya">
            <span>{{ __('pagination.next') }}</span>
        </x-ui.button>
    </div>
</nav>
