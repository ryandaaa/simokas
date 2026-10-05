{{-- Simulated public listing snippet: demonstrates the public contract for CAT-2.
     Only area_name (approximate area) is shown. Exact coordinates, full
     address, chassis number, and inspection place must never appear here. --}}
<x-ui.card>
    <p class="text-sm font-semibold text-text">{{ $listing->title }}</p>
    <p class="text-xs text-muted">{{ $listing->area_name }}</p>
</x-ui.card>
