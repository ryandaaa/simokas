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
             class="listing-map h-72 w-full overflow-hidden rounded-2xl border border-ds-line/80 bg-ds-soft sm:h-80"
             role="region"
             aria-label="{{ __('listing.fields.map_label') }}"
             tabindex="0"></div>
    </div>

    <p class="text-xs text-ds-muted">
        {{ __('listing.fields.map_attribution') }}
        (<a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener" class="underline">OpenStreetMap</a>)
    </p>

    <div data-map-error hidden>
        <div role="alert" class="p-4 rounded-2xl border border-ds-amber-soft bg-ds-amber-soft/40 text-xs font-semibold text-ds-ink">
            <span class="font-extrabold">{{ __('listing.fields.map_tile_error_title') }}</span>
            <span class="text-ds-muted">{{ __('listing.fields.map_tile_error') }}</span>
        </div>
    </div>

    <noscript>
        <div role="alert" class="p-4 rounded-2xl border border-ds-amber-soft bg-ds-amber-soft/40 text-xs font-semibold text-ds-ink">
            {{ __('listing.fields.map_no_js') }}
        </div>
    </noscript>
</div>
