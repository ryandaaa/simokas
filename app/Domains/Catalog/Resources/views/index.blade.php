<x-layouts.app
    :title="__('catalog.title')"
    :navItems="[
        ['label' => __('home.nav_home'), 'url' => route('home'), 'icon' => 'home', 'active' => false],
        ['label' => __('home.nav_search'), 'url' => route('catalog.index'), 'icon' => 'search', 'active' => true],
        ['label' => __('home.nav_how'), 'url' => route('catalog.how-it-works'), 'icon' => 'document-text', 'active' => false],
        ['label' => __('home.nav_help'), 'url' => route('catalog.help'), 'icon' => 'information-circle', 'active' => false],
    ]"
>
    <div class="ds-sans space-y-0 pb-12">
        {{-- Centered Section Title + Sort --}}
        <div class="text-center max-w-2xl mx-auto mb-10 space-y-3">
            <h1 class="text-3xl font-extrabold text-ds-ink tracking-tight">{{ __('catalog.heading') }}</h1>
            <p class="text-sm text-ds-muted">{{ __('catalog.subheading') }}</p>

            <div class="pt-2 flex items-center justify-center gap-2">
                <span class="text-xs font-semibold text-ds-muted">{{ __('catalog.sort_label') }}:</span>
                <select id="filter-sort" name="sort" form="catalog-filter" onchange="document.getElementById('catalog-filter').requestSubmit()"
                    class="bg-white border border-ds-line text-ds-ink text-xs font-semibold rounded-xl px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-ds-blue/20 shadow-xs">
                    @foreach ($sortOptions as $value => $label)
                        <option value="{{ $value }}" @selected($filter->sort === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <p class="text-sm text-ds-muted text-center mb-6" aria-live="polite" role="status">
            {{ trans_choice('catalog.results', $listings->total(), ['count' => number_format($listings->total(), 0, ',', '.')]) }}
        </p>

        <div class="grid lg:grid-cols-12 gap-8">
            {{-- Left Sidebar: Filters + Banner --}}
            <aside class="lg:col-span-3 space-y-6">
                <div class="bg-white rounded-2xl p-5 border border-ds-line/80 shadow-xs space-y-4">
                    <h3 class="text-xs font-extrabold text-ds-faint uppercase tracking-wider">{{ __('catalog.filter_title') }}</h3>

                    <form id="catalog-filter" method="GET" action="{{ route('catalog.index') }}" class="space-y-4">
                        <div class="w-full">
                            <label for="filter-q" class="block text-xs font-bold text-ds-ink mb-1.5">{{ __('catalog.search_label') }}</label>
                            <input type="text" id="filter-q" name="q" value="{{ $filter->keyword }}" maxlength="100" placeholder="{{ __('catalog.search_placeholder') }}"
                                class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                        </div>

                        <div class="w-full">
                            <label for="filter-type" class="block text-xs font-bold text-ds-ink mb-1.5">{{ __('catalog.type_label') }}</label>
                            <select id="filter-type" name="type"
                                class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                                <option value="">{{ __('catalog.type_all') }}</option>
                                @foreach ($typeOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($filter->type === (string) $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="w-full">
                            <label for="filter-brand" class="block text-xs font-bold text-ds-ink mb-1.5">{{ __('catalog.brand_label') }}</label>
                            <input type="text" id="filter-brand" name="brand" value="{{ $filter->brand }}" maxlength="100" placeholder="{{ __('catalog.brand_placeholder') }}"
                                class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                        </div>

                        <div class="w-full">
                            <label for="filter-area" class="block text-xs font-bold text-ds-ink mb-1.5">{{ __('catalog.area_label') }}</label>
                            <input type="text" id="filter-area" name="area" value="{{ $filter->area }}" maxlength="100" placeholder="{{ __('catalog.area_placeholder') }}"
                                class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="w-full">
                                <label for="filter-price-min" class="block text-xs font-bold text-ds-ink mb-1.5">{{ __('catalog.price_min_label') }}</label>
                                <input type="number" id="filter-price-min" name="price_min" value="{{ $filter->priceMin }}" min="0" inputmode="numeric"
                                    class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                            </div>
                            <div class="w-full">
                                <label for="filter-price-max" class="block text-xs font-bold text-ds-ink mb-1.5">{{ __('catalog.price_max_label') }}</label>
                                <input type="number" id="filter-price-max" name="price_max" value="{{ $filter->priceMax }}" min="0" inputmode="numeric"
                                    class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="w-full">
                                <label for="filter-year-min" class="block text-xs font-bold text-ds-ink mb-1.5">{{ __('catalog.year_min_label') }}</label>
                                <input type="number" id="filter-year-min" name="year_min" value="{{ $filter->yearMin }}" min="1900" inputmode="numeric"
                                    class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                            </div>
                            <div class="w-full">
                                <label for="filter-year-max" class="block text-xs font-bold text-ds-ink mb-1.5">{{ __('catalog.year_max_label') }}</label>
                                <input type="number" id="filter-year-max" name="year_max" value="{{ $filter->yearMax }}" min="1900" inputmode="numeric"
                                    class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                            </div>
                        </div>

                        <div class="w-full">
                            <label for="filter-inspected" class="block text-xs font-bold text-ds-ink mb-1.5">{{ __('catalog.inspected_label') }}</label>
                            <select id="filter-inspected" name="inspected"
                                class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                                @foreach ($inspectedOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($filter->inspected === (string) $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="w-full py-2 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-extrabold text-xs rounded-xl transition-all">
                            {{ __('catalog.apply') }}
                        </button>
                        <a href="{{ route('catalog.index') }}" class="block w-full py-2 bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs text-center">
                            {{ __('catalog.reset') }}
                        </a>
                    </form>

                    <hr class="border-ds-line/60">

                    {{-- Banner Card in Sidebar --}}
                    <div class="bg-gradient-to-br from-ds-ink to-ds-indigo text-white rounded-2xl p-4 text-center space-y-3">
                        <div class="w-10 h-10 mx-auto rounded-full bg-ds-blue/20 text-ds-blue flex items-center justify-center text-lg">
                            <i aria-hidden="true" class="fa-solid fa-bullhorn"></i>
                        </div>
                        <h4 class="text-xs font-bold">{{ __('catalog.sidebar_sell_title') }}</h4>
                        <p class="text-[11px] text-ds-soft">{{ __('catalog.sidebar_sell_description') }}</p>
                        <a href="{{ route('listings.create') }}" class="block w-full py-2 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-extrabold text-xs rounded-xl transition-all">
                            {{ __('catalog.sidebar_sell_action') }}
                        </a>
                    </div>
                </div>
            </aside>

            {{-- Right Cards Grid --}}
            <div class="lg:col-span-9">
                @if ($listings->isEmpty())
                    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-8 text-center max-w-2xl mx-auto">
                        <div class="w-14 h-14 mx-auto rounded-2xl bg-ds-soft text-ds-muted flex items-center justify-center text-xl mb-4">
                            <i aria-hidden="true" class="fa-solid fa-magnifying-glass"></i>
                        </div>
                        <h3 class="text-lg font-extrabold text-ds-ink">{{ __('catalog.empty_title') }}</h3>
                        <p class="text-xs text-ds-muted mt-2 leading-relaxed">{{ __('catalog.empty_description') }}</p>
                        <div class="flex justify-center mt-6">
                            <a href="{{ route('catalog.index') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md">
                                <span>{{ __('catalog.reset') }}</span>
                                <i aria-hidden="true" class="fa-solid fa-arrow-right text-xs"></i>
                            </a>
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-6">
                        @foreach ($cards as $card)
                            <article class="group bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col justify-between relative">
                                <a href="{{ route('catalog.show', ['listing' => $card->id]) }}" class="absolute inset-0 z-10" aria-label="{{ $card->title }}">
                                    <span class="sr-only">{{ $card->title }}</span>
                                </a>
                                <div>
                                    <div class="relative aspect-4/3 overflow-hidden bg-ds-soft">
                                        @if ($card->imageUrl)
                                            <img src="{{ $card->imageUrl }}" alt="{{ $card->imageAlt }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-ds-faint text-3xl">
                                                <i aria-hidden="true" class="fa-solid fa-car"></i>
                                            </div>
                                        @endif
                                        @if ($card->inspected)
                                            <span class="absolute top-3 left-3 bg-ds-green text-white text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider">Sudah Diinspeksi</span>
                                        @else
                                            <span class="absolute top-3 left-3 bg-ds-soft text-ds-muted text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider">Tersedia</span>
                                        @endif
                                    </div>
                                    <div class="p-4 space-y-2">
                                        <h3 class="font-bold text-ds-ink text-sm group-hover:text-ds-blue transition-colors line-clamp-1">{{ $card->title }}</h3>
                                        <p class="text-xs text-ds-muted line-clamp-1">{{ $card->year }} · {{ $card->mileage ? number_format($card->mileage, 0, ',', '.').' km' : '-' }}</p>
                                        <div class="pt-2 flex items-center justify-between">
                                            <span class="text-base font-extrabold text-ds-ink">{{ \App\Support\Money::format($card->price) }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="px-4 py-3 bg-ds-canvas/80 border-t border-ds-line/60 flex items-center justify-between text-xs text-ds-muted font-medium">
                                    <span class="flex items-center gap-1">
                                        <i aria-hidden="true" class="fa-solid fa-location-dot text-ds-blue text-[11px]"></i> {{ $card->area }}
                                    </span>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-10">
                        <x-ui.pagination :paginator="$listings" />
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
