{{-- Location picker: Leaflet map (loaded only on this page) + manual coordinate fallback.
     The map bundle is built by Vite from resources/js/listing-map.js and is NOT
     part of the global layout, per UI_GUIDELINES section 10. --}}
@vite('resources/js/listing-map.js')

<div class="space-y-2">
    <div wire:ignore>
        <div id="listing-map"
             data-listing-map
             data-lat="{{ $lat ?? '' }}"
             data-lng="{{ $lng ?? '' }}"
             data-centre-lat="{{ $mapCentre['lat'] }}"
             data-centre-lng="{{ $mapCentre['lng'] }}"
             class="listing-map h-72 w-full overflow-hidden rounded-lg border border-muted/20 bg-muted/10 sm:h-80"
             role="application"
             aria-label="{{ __('listing.fields.map_label') }}"
             tabindex="0"></div>
    </div>

    <p class="text-xs text-muted">
        {{ __('listing.fields.map_attribution') }}
        (<a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener" class="underline">OpenStreetMap</a>)
    </p>

    <div data-map-error hidden>
        <x-ui.alert variant="warning" :title="__('listing.fields.map_tile_error_title')" :message="__('listing.fields.map_tile_error')" />
    </div>

    <noscript>
        <x-ui.alert variant="warning" :message="__('listing.fields.map_no_js')" />
    </noscript>
</div>
