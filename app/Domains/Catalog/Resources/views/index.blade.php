@php
    $activeFilterCount = 0;
    if (!empty($filter->keyword)) $activeFilterCount++;
    if (!empty($filter->type)) $activeFilterCount++;
    if (!empty($filter->brand)) $activeFilterCount++;
    if (!empty($filter->area)) $activeFilterCount++;
    if (!empty($filter->priceMin) || !empty($filter->priceMax)) $activeFilterCount++;
    if (!empty($filter->yearMin) || !empty($filter->yearMax)) $activeFilterCount++;
    if (!empty($filter->inspected) && $filter->inspected !== 'all') $activeFilterCount++;
@endphp

<x-layouts.app
    :title="__('catalog.title')"
    :navItems="[
        ['label' => __('home.nav_home'), 'url' => route('home'), 'icon' => 'home', 'active' => false],
        ['label' => __('home.nav_search'), 'url' => route('catalog.index'), 'icon' => 'search', 'active' => true],
        ['label' => __('home.nav_how'), 'url' => route('catalog.how-it-works'), 'icon' => 'document-text', 'active' => false],
        ['label' => __('home.nav_help'), 'url' => route('catalog.help'), 'icon' => 'information-circle', 'active' => false],
    ]"
>
    <div x-data="{ mobileFilterOpen: false }" class="ds-sans space-y-0 pb-12">
        {{-- Centered Section Title + Sort & Filter Toolbar --}}
        <div class="text-center max-w-2xl mx-auto mb-10 space-y-3">
            <h1 class="text-3xl font-extrabold text-ds-ink tracking-tight">{{ __('catalog.heading') }}</h1>
            <p class="text-sm text-ds-muted">{{ __('catalog.subheading') }}</p>

            <div class="pt-2 max-w-sm sm:max-w-md mx-auto w-full flex items-center gap-2">
                <div class="flex-1">
                    <x-ui.custom-select
                        id="filter-sort"
                        name="sort"
                        form="catalog-filter"
                        :options="$sortOptions"
                        :selected="$filter->sort"
                        :submitOnChange="true"
                        :inlineLabel="__('catalog.sort_label')"
                    />
                </div>
                <button type="button"
                        @click="mobileFilterOpen = true"
                        class="lg:hidden h-11 px-4 bg-white border border-ds-line hover:border-ds-blue text-ds-ink rounded-xl font-bold text-xs flex items-center gap-2 shadow-xs transition-colors shrink-0"
                        aria-label="{{ __('catalog.filter_title') }}">
                    <i aria-hidden="true" class="fa-solid fa-sliders text-ds-blue"></i>
                    <span>Filter</span>
                    @if ($activeFilterCount > 0)
                        <span class="w-5 h-5 rounded-full bg-ds-blue text-white text-[10px] flex items-center justify-center font-bold">
                            {{ $activeFilterCount }}
                        </span>
                    @endif
                </button>
            </div>
        </div>

        <p class="text-sm text-ds-muted text-center mb-6" aria-live="polite" role="status">
            {{ trans_choice('catalog.results', $listings->total(), ['count' => number_format($listings->total(), 0, ',', '.')]) }}
        </p>

        <div class="grid lg:grid-cols-12 gap-8 items-start">
            {{-- Left Sidebar: Filters + Banner (Sticky di Desktop, disembunyikan di Mobile) --}}
            <aside class="hidden lg:block lg:col-span-3 space-y-6 lg:sticky lg:top-20 z-10">
                <div class="bg-white rounded-2xl p-5 border border-ds-line/80 shadow-xs space-y-4">
                    <h3 class="text-xs font-extrabold text-ds-faint uppercase tracking-wider">{{ __('catalog.filter_title') }}</h3>

                    <form id="catalog-filter" method="GET" action="{{ route('catalog.index') }}" autocomplete="off" class="space-y-4">
                        <div class="w-full">
                            <label for="filter-q" class="block text-xs font-bold text-ds-ink mb-1.5">{{ __('catalog.search_label') }}</label>
                            <input type="text" id="filter-q" name="q" value="{{ $filter->keyword }}" maxlength="100" autocomplete="off" placeholder="{{ __('catalog.search_placeholder') }}"
                                class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                        </div>

                        <div class="w-full">
                            <x-ui.custom-select
                                id="filter-type"
                                name="type"
                                :label="__('catalog.type_label')"
                                :placeholder="__('catalog.type_all')"
                                :options="$typeOptions"
                                :selected="$filter->type"
                            />
                        </div>

                        <div class="w-full">
                            <label for="filter-brand" class="block text-xs font-bold text-ds-ink mb-1.5">{{ __('catalog.brand_label') }}</label>
                            <input type="text" id="filter-brand" name="brand" value="{{ $filter->brand }}" maxlength="100" autocomplete="off" placeholder="{{ __('catalog.brand_placeholder') }}"
                                class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                        </div>

                        <div class="w-full">
                            <label for="filter-area" class="block text-xs font-bold text-ds-ink mb-1.5">{{ __('catalog.area_label') }}</label>
                            <input type="text" id="filter-area" name="area" value="{{ $filter->area }}" maxlength="100" autocomplete="off" placeholder="{{ __('catalog.area_placeholder') }}"
                                class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="w-full">
                                <x-ui.money-input
                                    id="filter-price-min"
                                    name="price_min"
                                    :label="__('catalog.price_min_label')"
                                    :value="$filter->priceMin"
                                    placeholder="0"
                                />
                            </div>
                            <div class="w-full">
                                <x-ui.money-input
                                    id="filter-price-max"
                                    name="price_max"
                                    :label="__('catalog.price_max_label')"
                                    :value="$filter->priceMax"
                                    placeholder="0"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="w-full">
                                <label for="filter-year-min" class="block text-xs font-bold text-ds-ink mb-1.5">{{ __('catalog.year_min_label') }}</label>
                                <input type="number" id="filter-year-min" name="year_min" value="{{ $filter->yearMin }}" min="1900" max="{{ (int) date('Y') + 1 }}" autocomplete="one-time-code" readonly onfocus="this.removeAttribute('readonly');" onblur="this.setAttribute('readonly', 'readonly');" placeholder="2015" inputmode="numeric"
                                    oninput="if(this.value.length > 4) this.value = this.value.slice(0, 4)"
                                    class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                            </div>
                            <div class="w-full">
                                <label for="filter-year-max" class="block text-xs font-bold text-ds-ink mb-1.5">{{ __('catalog.year_max_label') }}</label>
                                <input type="number" id="filter-year-max" name="year_max" value="{{ $filter->yearMax }}" min="1900" max="{{ (int) date('Y') + 1 }}" autocomplete="one-time-code" readonly onfocus="this.removeAttribute('readonly');" onblur="this.setAttribute('readonly', 'readonly');" placeholder="{{ date('Y') }}" inputmode="numeric"
                                    oninput="if(this.value.length > 4) this.value = this.value.slice(0, 4)"
                                    class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                            </div>
                        </div>

                        <div class="w-full">
                            <x-ui.custom-select
                                id="filter-inspected"
                                name="inspected"
                                :label="__('catalog.inspected_label')"
                                :options="$inspectedOptions"
                                :selected="$filter->inspected"
                            />
                        </div>

                        <button type="submit" class="w-full py-2 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-extrabold text-xs rounded-xl transition-all">
                            {{ __('catalog.apply') }}
                        </button>
                        <a wire:navigate.hover href="{{ route('catalog.index') }}" class="block w-full py-2 bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs text-center">
                            {{ __('catalog.reset') }}
                        </a>
                    </form>

                    <hr class="border-ds-line/60">

                    {{-- Banner Card in Sidebar --}}
                    <div class="bg-ds-ink text-white rounded-2xl p-4 text-center space-y-3">
                        <div class="w-10 h-10 mx-auto rounded-full bg-ds-blue/20 text-ds-blue flex items-center justify-center text-lg">
                            <i aria-hidden="true" class="fa-solid fa-bullhorn"></i>
                        </div>
                        <h4 class="text-xs font-bold">{{ __('catalog.sidebar_sell_title') }}</h4>
                        <p class="text-[11px] text-ds-soft">{{ __('catalog.sidebar_sell_description') }}</p>
                        <a href="{{ route('listings.create') }}" class="block w-full py-2 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-extrabold text-xs rounded-xl transition-all shadow-sm">
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
                            <a wire:navigate.hover href="{{ route('catalog.index') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md">
                                <span>{{ __('catalog.reset') }}</span>
                                <i aria-hidden="true" class="fa-solid fa-arrow-right text-xs"></i>
                            </a>
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-6">
                        @foreach ($cards as $card)
                            <article class="group bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col justify-between relative">
                                <a wire:navigate.hover href="{{ route('catalog.show', ['listing' => $card->id]) }}" class="absolute inset-0 z-10" aria-label="{{ $card->title }}">
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

        {{-- Mobile Filter Bottom Sheet (Hanya Tampil di Layar Ponsel/Android) --}}
        <div x-show="mobileFilterOpen"
             x-cloak
             class="fixed inset-0 z-50 lg:hidden"
             role="dialog"
             aria-modal="true"
             aria-labelledby="mobile-filter-title"
             @keydown.escape.window="mobileFilterOpen = false">

            {{-- Latar Belakang Redup / Backdrop --}}
            <div x-show="mobileFilterOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-ds-ink/60 backdrop-blur-xs transition-opacity"
                 @click="mobileFilterOpen = false"
                 aria-hidden="true"></div>

            {{-- Panel Bottom Sheet (Meluncur dari Bawah) --}}
            <div class="fixed inset-x-0 bottom-0 z-10 flex flex-col justify-end">
                <div x-show="mobileFilterOpen"
                     x-transition:enter="transform transition ease-out duration-300"
                     x-transition:enter-start="translate-y-full"
                     x-transition:enter-end="translate-y-0"
                     x-transition:leave="transform transition ease-in duration-200"
                     x-transition:leave-start="translate-y-0"
                     x-transition:leave-end="translate-y-full"
                     @click.outside="mobileFilterOpen = false"
                     class="w-full bg-white rounded-t-3xl shadow-2xl flex flex-col max-h-[85vh] border-t border-ds-line/80">

                    {{-- Handle Penarik & Judul Sheet --}}
                    <div class="pt-3 pb-3 px-6 border-b border-ds-line/60 relative flex-shrink-0 text-center">
                        <div class="w-10 h-1.5 bg-ds-line rounded-full mx-auto mb-3"></div>
                        <div class="flex items-center justify-between">
                            <div class="text-left">
                                <h3 id="mobile-filter-title" class="text-base font-extrabold text-ds-ink">
                                    {{ __('catalog.filter_title') }}
                                </h3>
                                @if ($activeFilterCount > 0)
                                    <p class="text-[11px] text-ds-muted">
                                        {{ $activeFilterCount }} filter diterapkan
                                    </p>
                                @endif
                            </div>
                            <button type="button"
                                    @click="mobileFilterOpen = false"
                                    class="w-9 h-9 rounded-full bg-ds-soft text-ds-muted hover:text-ds-ink flex items-center justify-center transition-colors"
                                    aria-label="{{ __('ui.actions.close') }}">
                                <i aria-hidden="true" class="fa-solid fa-xmark text-sm"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Formulir Filter Mobile (Dapat Digulir) --}}
                    <form id="catalog-filter-mobile" method="GET" action="{{ route('catalog.index') }}" autocomplete="off" class="flex-1 overflow-y-auto px-6 py-4 space-y-4">
                        <input type="hidden" name="sort" value="{{ $filter->sort }}">

                        <div class="w-full">
                            <label for="filter-q-mobile" class="block text-xs font-bold text-ds-ink mb-1.5">{{ __('catalog.search_label') }}</label>
                            <input type="text" id="filter-q-mobile" name="q" value="{{ $filter->keyword }}" maxlength="100" autocomplete="off" placeholder="{{ __('catalog.search_placeholder') }}"
                                class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                        </div>

                        <div class="w-full">
                            <x-ui.custom-select
                                id="filter-type-mobile"
                                name="type"
                                :label="__('catalog.type_label')"
                                :placeholder="__('catalog.type_all')"
                                :options="$typeOptions"
                                :selected="$filter->type"
                            />
                        </div>

                        <div class="w-full">
                            <label for="filter-brand-mobile" class="block text-xs font-bold text-ds-ink mb-1.5">{{ __('catalog.brand_label') }}</label>
                            <input type="text" id="filter-brand-mobile" name="brand" value="{{ $filter->brand }}" maxlength="100" autocomplete="off" placeholder="{{ __('catalog.brand_placeholder') }}"
                                class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                        </div>

                        <div class="w-full">
                            <label for="filter-area-mobile" class="block text-xs font-bold text-ds-ink mb-1.5">{{ __('catalog.area_label') }}</label>
                            <input type="text" id="filter-area-mobile" name="area" value="{{ $filter->area }}" maxlength="100" autocomplete="off" placeholder="{{ __('catalog.area_placeholder') }}"
                                class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="w-full">
                                <x-ui.money-input
                                    id="filter-price-min-mobile"
                                    name="price_min"
                                    :label="__('catalog.price_min_label')"
                                    :value="$filter->priceMin"
                                    placeholder="0"
                                />
                            </div>
                            <div class="w-full">
                                <x-ui.money-input
                                    id="filter-price-max-mobile"
                                    name="price_max"
                                    :label="__('catalog.price_max_label')"
                                    :value="$filter->priceMax"
                                    placeholder="0"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="w-full">
                                <label for="filter-year-min-mobile" class="block text-xs font-bold text-ds-ink mb-1.5">{{ __('catalog.year_min_label') }}</label>
                                <input type="number" id="filter-year-min-mobile" name="year_min" value="{{ $filter->yearMin }}" min="1900" max="{{ (int) date('Y') + 1 }}" autocomplete="one-time-code" readonly onfocus="this.removeAttribute('readonly');" onblur="this.setAttribute('readonly', 'readonly');" placeholder="2015" inputmode="numeric"
                                    oninput="if(this.value.length > 4) this.value = this.value.slice(0, 4)"
                                    class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                            </div>
                            <div class="w-full">
                                <label for="filter-year-max-mobile" class="block text-xs font-bold text-ds-ink mb-1.5">{{ __('catalog.year_max_label') }}</label>
                                <input type="number" id="filter-year-max-mobile" name="year_max" value="{{ $filter->yearMax }}" min="1900" max="{{ (int) date('Y') + 1 }}" autocomplete="one-time-code" readonly onfocus="this.removeAttribute('readonly');" onblur="this.setAttribute('readonly', 'readonly');" placeholder="{{ date('Y') }}" inputmode="numeric"
                                    oninput="if(this.value.length > 4) this.value = this.value.slice(0, 4)"
                                    class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors">
                            </div>
                        </div>

                        <div class="w-full pb-16">
                            <x-ui.custom-select
                                id="filter-inspected-mobile"
                                name="inspected"
                                :label="__('catalog.inspected_label')"
                                :options="$inspectedOptions"
                                :selected="$filter->inspected"
                                :dropup="true"
                            />
                        </div>
                    </form>

                    {{-- Tombol Aksi Bawah (Sticky) --}}
                    <div class="p-4 bg-ds-canvas border-t border-ds-line/60 flex items-center gap-3 flex-shrink-0">
                        <a wire:navigate.hover href="{{ route('catalog.index') }}" class="w-1/3 py-3 bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-xs rounded-xl transition-all shadow-xs text-center flex items-center justify-center">
                            {{ __('catalog.reset') }}
                        </a>
                        <button type="submit" form="catalog-filter-mobile" class="w-2/3 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-extrabold text-xs rounded-xl transition-all shadow-sm flex items-center justify-center gap-2">
                            <i aria-hidden="true" class="fa-solid fa-check"></i>
                            <span>{{ __('catalog.apply') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
