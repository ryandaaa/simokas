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

    <div class="ds-sans space-y-6 pb-12">
        <a href="{{ route('catalog.index') }}" class="min-h-[44px] inline-flex items-center gap-1 text-xs font-bold text-ds-blue hover:text-ds-blue-ink">
            <i aria-hidden="true" class="fa-solid fa-chevron-left"></i>
            {{ __('catalog.detail.back_to_list') }}
        </a>

        @if ($detail->isSold)
            <div role="alert" class="p-4 rounded-2xl border border-ds-amber-soft bg-ds-amber-soft/40 flex items-start gap-3 text-sm">
                <i aria-hidden="true" class="fa-solid fa-triangle-exclamation text-ds-amber-ink mt-0.5"></i>
                <p class="font-bold text-ds-ink">{{ __('catalog.detail.sold_notice') }}</p>
            </div>
        @endif

        @if (! $detail->isPubliclyVisible)
            <div role="alert" class="p-4 rounded-2xl border border-ds-amber-soft bg-ds-amber-soft/40 flex items-start gap-3 text-sm">
                <i aria-hidden="true" class="fa-solid fa-triangle-exclamation text-ds-amber-ink mt-0.5"></i>
                <p class="font-bold text-ds-ink">{{ __('catalog.detail.not_public_notice') }}</p>
            </div>
        @endif

        <div>
            <div class="flex flex-wrap items-center gap-2">
                @php
                    $statusPill = match ($detail->status->color()) {
                        'success' => 'bg-ds-green-soft text-ds-green-ink',
                        'warning' => 'bg-ds-amber-soft text-ds-amber-ink',
                        'danger' => 'bg-ds-red-soft text-ds-red-ink',
                        default => 'bg-ds-blue-soft text-ds-blue-ink',
                    };
                @endphp
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold {{ $statusPill }}">
                    {{ $detail->status->label() }}
                </span>
                @if ($detail->showInspectedBadge())
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-ds-green text-white">
                        <i aria-hidden="true" class="fa-solid fa-shield-halved"></i>
                        <span>{{ __('ui.badges.inspected') }}</span>
                        @if ($detail->lastInspectedAt)
                            <span class="font-normal opacity-90 border-l border-white/30 pl-1.5 ml-0.5">
                                {{ $detail->lastInspectedAt instanceof \Carbon\CarbonInterface ? $detail->lastInspectedAt->translatedFormat('d M Y') : $detail->lastInspectedAt }}
                            </span>
                        @endif
                    </span>
                @endif
            </div>
            <h1 class="mt-3 text-2xl sm:text-3xl font-extrabold text-ds-ink tracking-tight">{{ $detail->title }}</h1>
            <p class="mt-1 text-sm text-ds-muted">
                {{ $detail->vehicleType->label() }} • {{ $detail->year }} • {{ $detail->areaName }}
            </p>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <section aria-label="{{ __('catalog.detail.gallery_title') }}">
                @if (count($detail->photos) === 0)
                    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-8 text-center">
                        <div class="w-14 h-14 mx-auto rounded-2xl bg-ds-soft text-ds-muted flex items-center justify-center text-xl mb-4">
                            <i aria-hidden="true" class="fa-solid fa-image"></i>
                        </div>
                        <h3 class="text-base font-extrabold text-ds-ink">{{ __('catalog.detail.gallery_empty_title') }}</h3>
                        <p class="text-xs text-ds-muted mt-2">{{ __('catalog.detail.gallery_empty_description') }}</p>
                    </div>
                @else
                    <div
                        x-data="{ active: 0, total: {{ count($detail->photos) }} }"
                        @keydown.arrow-right.prevent="active = Math.min(active + 1, total - 1)"
                        @keydown.arrow-left.prevent="active = Math.max(active - 1, 0)"
                        tabindex="0"
                        role="region"
                        aria-label="{{ __('catalog.detail.gallery_title') }}"
                        class="space-y-3"
                    >
                        <div class="relative aspect-4/3 w-full overflow-hidden rounded-2xl border border-ds-line/80 bg-ds-soft shadow-xs">
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

                        <p class="text-xs text-ds-muted font-semibold" aria-live="polite" role="status">
                            {{ __('catalog.detail.photo_counter', ['current' => 1, 'total' => count($detail->photos)]) }}
                            <span class="sr-only">(<span x-text="active + 1"></span> / {{ count($detail->photos) }})</span>
                        </p>

                        <div class="grid grid-cols-4 gap-2" role="list">
                            @foreach ($detail->photos as $index => $photo)
                                <button
                                    type="button"
                                    role="listitem"
                                    @click="active = {{ $index }}"
                                    :aria-current="active === {{ $index }} ? 'true' : 'false'"
                                    :class="active === {{ $index }} ? 'ring-2 ring-ds-blue' : 'opacity-70 hover:opacity-100'"
                                    class="min-h-[44px] overflow-hidden rounded-xl border border-ds-line/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-ds-blue"
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

            <div class="space-y-6">
                <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                    <p class="text-3xl font-extrabold text-ds-ink tracking-tight">
                        {{ \App\Support\Money::format($detail->price) }}
                    </p>
                    <p class="mt-1 text-sm text-ds-muted">
                        {{ $detail->negotiable ? __('catalog.detail.negotiable_yes') : __('catalog.detail.negotiable_no') }}
                    </p>
                </div>

                @if (count($actions) > 0 || (!empty($hasActiveJob) && !empty($canRequestInspection)))
                    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                        <h2 class="text-sm font-extrabold text-ds-ink">{{ __('catalog.detail.actions_title') }}</h2>
                        <div class="mt-4 flex flex-col gap-2">
                            @foreach ($actions as $action)
                                @php
                                    $actionIcon = match ($action['icon'] ?? null) {
                                        'search' => 'fa-magnifying-glass',
                                        'tag' => 'fa-tag',
                                        'document-text' => 'fa-file-lines',
                                        default => 'fa-arrow-right',
                                    };
                                    $actionBtn = $action['variant'] === 'primary'
                                        ? 'bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink shadow-md'
                                        : 'bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink shadow-xs';
                                @endphp
                                <a href="{{ $action['url'] }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 font-bold text-sm rounded-xl transition-all {{ $actionBtn }}">
                                    <i aria-hidden="true" class="fa-solid {{ $actionIcon }} text-xs"></i>
                                    {{ $action['label'] }}
                                </a>
                            @endforeach

                            @if (!empty($showChatLogin) && Route::has('login'))
                                <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-sm rounded-xl transition-all shadow-xs">
                                    {{ __('catalog.detail.action_chat_seller') }}
                                </a>
                            @elseif (!empty($canChatWithSeller) && Route::has('conversations.start'))
                                <form method="POST" action="{{ route('conversations.start') }}">
                                    @csrf
                                    <input type="hidden" name="context_type" value="listing">
                                    <input type="hidden" name="context_id" value="{{ $detail->id }}">
                                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-7 py-3 bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-sm rounded-xl transition-all shadow-xs">
                                        {{ __('catalog.detail.action_chat_seller') }}
                                    </button>
                                </form>
                            @endif

                            @if (!empty($hasActiveJob) && !empty($canRequestInspection))
                                <p class="text-xs text-ds-muted">
                                    {{ __('catalog.detail.active_inspection_notice') }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                    <h2 class="text-sm font-extrabold text-ds-ink">{{ __('catalog.detail.specs_title') }}</h2>
                    <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                        <div>
                            <dt class="text-ds-muted">{{ __('catalog.detail.type') }}</dt>
                            <dd class="font-bold text-ds-ink">{{ $detail->vehicleType->label() }}</dd>
                        </div>
                        <div>
                            <dt class="text-ds-muted">{{ __('catalog.detail.brand') }}</dt>
                            <dd class="font-bold text-ds-ink">{{ $detail->brand }}</dd>
                        </div>
                        <div>
                            <dt class="text-ds-muted">{{ __('catalog.detail.model') }}</dt>
                            <dd class="font-bold text-ds-ink">{{ $detail->model }}</dd>
                        </div>
                        <div>
                            <dt class="text-ds-muted">{{ __('catalog.detail.year') }}</dt>
                            <dd class="font-bold text-ds-ink">{{ $detail->year }}</dd>
                        </div>
                        <div>
                            <dt class="text-ds-muted">{{ __('catalog.detail.mileage') }}</dt>
                            <dd class="font-bold text-ds-ink">{{ number_format($detail->odometerKm, 0, ',', '.') }} km</dd>
                        </div>
                        <div>
                            <dt class="text-ds-muted">{{ __('catalog.detail.transmission') }}</dt>
                            <dd class="font-bold text-ds-ink">{{ $detail->transmission->label() }}</dd>
                        </div>
                        <div>
                            <dt class="text-ds-muted">{{ __('catalog.detail.fuel') }}</dt>
                            <dd class="font-bold text-ds-ink">{{ $detail->fuel->label() }}</dd>
                        </div>
                        <div>
                            <dt class="text-ds-muted">{{ __('catalog.detail.color') }}</dt>
                            <dd class="font-bold text-ds-ink">{{ $detail->color }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        @if ($detail->description)
            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                <h2 class="text-sm font-extrabold text-ds-ink">{{ __('catalog.detail.description_title') }}</h2>
                <p class="mt-2 whitespace-pre-line text-sm text-ds-ink leading-relaxed">{{ $detail->description }}</p>
            </div>
        @endif

        <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
            <h2 class="text-sm font-extrabold text-ds-ink">{{ __('catalog.detail.map_title') }}</h2>
            @if ($detail->areaName)
                <p class="mt-1 text-sm text-ds-muted">{{ $detail->areaName }}</p>
            @endif
            <p class="mt-1 text-sm text-ds-muted">{{ __('catalog.detail.map_approximate_note') }}</p>

            @if ($detail->approximate)
                <div class="mt-3 space-y-2">
                    <div
                        data-catalog-map
                        data-lat="{{ $detail->approximate->lat }}"
                        data-lng="{{ $detail->approximate->lng }}"
                        class="h-72 w-full overflow-hidden rounded-2xl border border-ds-line/80 bg-ds-soft sm:h-80"
                        role="region"
                        aria-label="{{ __('catalog.detail.map_title') }}"
                        tabindex="0"
                    ></div>
                    <p class="text-xs text-ds-muted">
                        {{ __('catalog.detail.map_attribution') }}
                        (<a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener" class="underline">OpenStreetMap</a>)
                    </p>
                    <div data-map-error hidden>
                        <div role="alert" class="p-4 rounded-2xl border border-ds-amber-soft bg-ds-amber-soft/40 text-xs font-semibold text-ds-ink">
                            {{ __('catalog.detail.map_tile_error') }}
                        </div>
                    </div>
                    <noscript>
                        <div role="alert" class="p-4 rounded-2xl border border-ds-amber-soft bg-ds-amber-soft/40 text-xs font-semibold text-ds-ink">
                            {{ __('catalog.detail.map_no_js') }}
                        </div>
                    </noscript>
                </div>
            @endif
        </div>

        @if ($detail->canSeePrivate)
            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6">
                <h2 class="text-sm font-extrabold text-ds-ink">{{ __('catalog.detail.private_title') }}</h2>
                <p class="mt-1 text-sm text-ds-muted">{{ __('catalog.detail.private_hint') }}</p>
                <dl class="mt-4 space-y-3 text-sm">
                    @if ($detail->addressFull)
                        <div>
                            <dt class="text-ds-muted">{{ __('catalog.detail.address_full') }}</dt>
                            <dd class="font-bold text-ds-ink">{{ $detail->addressFull }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-ds-muted">{{ __('catalog.detail.inspection_place') }}</dt>
                        <dd class="font-bold text-ds-ink">{{ $detail->inspectionPlace }}</dd>
                    </div>
                    @if ($detail->chassisNumber)
                        <div>
                            <dt class="text-ds-muted">{{ __('catalog.detail.chassis_number') }}</dt>
                            <dd class="font-bold text-ds-ink">{{ $detail->chassisNumber }}</dd>
                        </div>
                    @endif
                    @if ($detail->exactLat !== null && $detail->exactLng !== null)
                        <div>
                            <dt class="text-ds-muted">{{ __('catalog.detail.coordinates') }}</dt>
                            <dd class="font-bold text-ds-ink">{{ $detail->exactLat }}, {{ $detail->exactLng }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        @endif
    </div>
</x-layouts.app>
