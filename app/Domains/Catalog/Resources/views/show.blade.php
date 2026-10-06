<x-layouts.app
    :title="$detail->title"
    :navItems="[
        ['label' => __('home.nav_home'), 'url' => route('home'), 'icon' => 'home', 'active' => false],
        ['label' => __('home.nav_search'), 'url' => route('catalog.index'), 'icon' => 'search', 'active' => true],
        ['label' => __('home.nav_how'), 'url' => route('catalog.how-it-works'), 'icon' => 'document-text', 'active' => false],
        ['label' => __('home.nav_help'), 'url' => route('catalog.help'), 'icon' => 'information-circle', 'active' => false],
    ]"
>
    @vite('resources/js/catalog-map.js')

    <div class="space-y-4">
        <x-ui.button :href="route('catalog.index')" variant="ghost" icon="chevron-left">
            {{ __('catalog.detail.back_to_list') }}
        </x-ui.button>

        @if ($detail->isSold)
            <x-ui.alert variant="warning" :title="__('catalog.detail.sold_notice')" />
        @endif

        @if (! $detail->isPubliclyVisible)
            <x-ui.alert variant="warning" :title="__('catalog.detail.not_public_notice')" />
        @endif

        <div>
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.status-chip
                    :label="$detail->status->label()"
                    :variant="$detail->status->color()"
                    :icon="$detail->status->icon()"
                />
                @if ($detail->showInspectedBadge())
                    <x-ui.badge-inspected :date="$detail->lastInspectedAt" />
                @endif
            </div>
            <h1 class="mt-2 text-xl font-bold text-text sm:text-2xl">{{ $detail->title }}</h1>
            <p class="mt-1 text-sm text-muted">
                {{ $detail->vehicleType->label() }} • {{ $detail->year }} • {{ $detail->areaName }}
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <section aria-label="{{ __('catalog.detail.gallery_title') }}">
                @if (count($detail->photos) === 0)
                    <x-ui.empty-state
                        icon="photo"
                        :title="__('catalog.detail.gallery_empty_title')"
                        :description="__('catalog.detail.gallery_empty_description')"
                    />
                @else
                    <div
                        x-data="{ active: 0, total: {{ count($detail->photos) }} }"
                        @keydown.arrow-right.prevent="active = Math.min(active + 1, total - 1)"
                        @keydown.arrow-left.prevent="active = Math.max(active - 1, 0)"
                        class="space-y-3"
                    >
                        <div class="relative aspect-[4/3] w-full overflow-hidden rounded-xl border border-muted/20 bg-muted/10">
                            @foreach ($detail->photos as $index => $photo)
                                <img
                                    x-show="active === {{ $index }}"
                                    src="{{ $photo->url }}"
                                    alt="{{ $photo->alt }}"
                                    @if ($index > 0) loading="lazy" @else loading="eager" @endif
                                    width="800"
                                    height="600"
                                    class="h-full w-full object-cover"
                                />
                            @endforeach
                        </div>

                        <p class="text-sm text-muted" aria-live="polite" role="status">
                            {{ __('catalog.detail.photo_counter', ['current' => 1, 'total' => count($detail->photos)]) }}
                        </p>

                        <div class="grid grid-cols-4 gap-2" role="list">
                            @foreach ($detail->photos as $index => $photo)
                                <button
                                    type="button"
                                    role="listitem"
                                    @click="active = {{ $index }}"
                                    :aria-current="active === {{ $index }} ? 'true' : 'false'"
                                    :class="active === {{ $index }} ? 'ring-2 ring-accent' : 'opacity-70 hover:opacity-100'"
                                    class="min-h-[44px] overflow-hidden rounded-lg border border-muted/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                                    aria-label="{{ $photo->alt }}"
                                >
                                    <img
                                        src="{{ $photo->url }}"
                                        alt="{{ $photo->alt }}"
                                        loading="lazy"
                                        width="200"
                                        height="150"
                                        class="h-16 w-full object-cover sm:h-20"
                                    />
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>

            <div class="space-y-4">
                <x-ui.card>
                    <div class="flex items-baseline justify-between gap-2">
                        <x-ui.money :amount="$detail->price" class="text-2xl font-bold text-primary" />
                    </div>
                    <p class="mt-1 text-sm text-muted">
                        {{ $detail->negotiable ? __('catalog.detail.negotiable_yes') : __('catalog.detail.negotiable_no') }}
                    </p>
                </x-ui.card>

                @if (count($actions) > 0 || (!empty($hasActiveJob) && !empty($canRequestInspection)))
                    <x-ui.card>
                        <h2 class="text-sm font-semibold text-text">{{ __('catalog.detail.actions_title') }}</h2>
                        <div class="mt-3 flex flex-col gap-2">
                            @foreach ($actions as $action)
                                <x-ui.button :href="$action['url']" :variant="$action['variant']" :icon="$action['icon']">
                                    {{ $action['label'] }}
                                </x-ui.button>
                            @endforeach

                            @if (!empty($hasActiveJob) && !empty($canRequestInspection))
                                <p class="text-sm text-muted">
                                    {{ __('catalog.detail.active_inspection_notice') }}
                                </p>
                            @endif
                        </div>
                    </x-ui.card>
                @endif

                <x-ui.card>
                    <h2 class="text-sm font-semibold text-text">{{ __('catalog.detail.specs_title') }}</h2>
                    <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                        <div>
                            <dt class="text-muted">{{ __('catalog.detail.type') }}</dt>
                            <dd class="font-medium text-text">{{ $detail->vehicleType->label() }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted">{{ __('catalog.detail.brand') }}</dt>
                            <dd class="font-medium text-text">{{ $detail->brand }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted">{{ __('catalog.detail.model') }}</dt>
                            <dd class="font-medium text-text">{{ $detail->model }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted">{{ __('catalog.detail.year') }}</dt>
                            <dd class="font-medium text-text">{{ $detail->year }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted">{{ __('catalog.detail.mileage') }}</dt>
                            <dd class="font-medium text-text">{{ number_format($detail->odometerKm, 0, ',', '.') }} km</dd>
                        </div>
                        <div>
                            <dt class="text-muted">{{ __('catalog.detail.transmission') }}</dt>
                            <dd class="font-medium text-text">{{ $detail->transmission->label() }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted">{{ __('catalog.detail.fuel') }}</dt>
                            <dd class="font-medium text-text">{{ $detail->fuel->label() }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted">{{ __('catalog.detail.color') }}</dt>
                            <dd class="font-medium text-text">{{ $detail->color }}</dd>
                        </div>
                    </dl>
                </x-ui.card>
            </div>
        </div>

        @if ($detail->description)
            <x-ui.card>
                <h2 class="text-sm font-semibold text-text">{{ __('catalog.detail.description_title') }}</h2>
                <p class="mt-2 whitespace-pre-line text-sm text-text">{{ $detail->description }}</p>
            </x-ui.card>
        @endif

        <x-ui.card>
            <h2 class="text-sm font-semibold text-text">{{ __('catalog.detail.map_title') }}</h2>
            @if ($detail->areaName)
                <p class="mt-1 text-sm text-muted">{{ $detail->areaName }}</p>
            @endif
            <p class="mt-1 text-sm text-muted">{{ __('catalog.detail.map_approximate_note') }}</p>

            @if ($detail->approximate)
                <div class="mt-3 space-y-2">
                    <div
                        data-catalog-map
                        data-lat="{{ $detail->approximate->lat }}"
                        data-lng="{{ $detail->approximate->lng }}"
                        class="h-72 w-full overflow-hidden rounded-lg border border-muted/20 bg-muted/10 sm:h-80"
                        role="application"
                        aria-label="{{ __('catalog.detail.map_title') }}"
                        tabindex="0"
                    ></div>
                    <p class="text-xs text-muted">
                        {{ __('catalog.detail.map_attribution') }}
                        (<a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener" class="underline">OpenStreetMap</a>)
                    </p>
                    <div data-map-error hidden>
                        <x-ui.alert variant="warning" :message="__('catalog.detail.map_tile_error')" />
                    </div>
                    <noscript>
                        <x-ui.alert variant="warning" :message="__('catalog.detail.map_no_js')" />
                    </noscript>
                </div>
            @endif
        </x-ui.card>

        @if ($detail->canSeePrivate)
            <x-ui.card>
                <h2 class="text-sm font-semibold text-text">{{ __('catalog.detail.private_title') }}</h2>
                <p class="mt-1 text-sm text-muted">{{ __('catalog.detail.private_hint') }}</p>
                <dl class="mt-3 space-y-3 text-sm">
                    @if ($detail->addressFull)
                        <div>
                            <dt class="text-muted">{{ __('catalog.detail.address_full') }}</dt>
                            <dd class="font-medium text-text">{{ $detail->addressFull }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-muted">{{ __('catalog.detail.inspection_place') }}</dt>
                        <dd class="font-medium text-text">{{ $detail->inspectionPlace }}</dd>
                    </div>
                    @if ($detail->chassisNumber)
                        <div>
                            <dt class="text-muted">{{ __('catalog.detail.chassis_number') }}</dt>
                            <dd class="font-medium text-text">{{ $detail->chassisNumber }}</dd>
                        </div>
                    @endif
                    @if ($detail->exactLat !== null && $detail->exactLng !== null)
                        <div>
                            <dt class="text-muted">{{ __('catalog.detail.coordinates') }}</dt>
                            <dd class="font-medium text-text">{{ $detail->exactLat }}, {{ $detail->exactLng }}</dd>
                        </div>
                    @endif
                </dl>
            </x-ui.card>
        @endif
    </div>
</x-layouts.app>
