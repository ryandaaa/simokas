{{-- Location picker: Leaflet map + manual coordinate fallback.
     Its parent Livewire component provides the Vite bundle through @assets;
     the global layout intentionally does not load the map bundle. --}}
<div class="space-y-2" data-location-picker>
    <div class="flex flex-wrap items-center gap-2">
        <button type="button"
                data-locate-me
                data-msg-locating="{{ __('listing.fields.locating') }}"
                data-msg-unsupported="{{ __('listing.fields.location_unsupported') }}"
                data-msg-denied="{{ __('listing.fields.location_denied') }}"
                data-msg-unavailable="{{ __('listing.fields.location_unavailable') }}"
                data-msg-timeout="{{ __('listing.fields.location_timeout') }}"
                class="inline-flex items-center justify-center gap-2 min-h-[44px] px-5 py-2.5 rounded-full text-sm font-bold bg-white text-ds-ink border border-ds-line hover:border-ds-blue hover:text-ds-blue transition-colors">
            <i aria-hidden="true" class="fa-solid fa-location-crosshairs"></i>
            <span>{{ __('listing.fields.use_my_location') }}</span>
        </button>
        <p data-location-status hidden class="text-xs text-ds-muted" role="status"></p>
    </div>

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
