<x-layouts.app
    :title="__('catalog.title')"
    :navItems="[
        ['label' => __('catalog.nav_catalog'), 'url' => route('catalog.index'), 'icon' => 'search', 'active' => true],
    ]"
>
    <div class="space-y-4">
        <div>
            <h1 class="text-xl font-bold text-text sm:text-2xl">{{ __('catalog.heading') }}</h1>
            <p class="mt-1 text-sm text-muted">{{ __('catalog.subheading') }}</p>
        </div>

        <details open class="rounded-xl border border-muted/20 bg-surface">
            <summary
                class="flex min-h-[44px] cursor-pointer list-none items-center justify-between px-4 py-3 text-sm font-semibold text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-accent md:hidden"
                aria-label="{{ __('catalog.filter_toggle') }}"
            >
                <span class="inline-flex items-center gap-2">
                    <x-ui.icon name="search" class="h-4 w-4" />
                    {{ __('catalog.filter_title') }}
                </span>
                <x-ui.icon name="chevron-down" class="h-4 w-4" />
            </summary>

            <form method="GET" action="{{ route('catalog.index') }}" class="border-t border-muted/15 p-4 md:border-t-0">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <x-ui.input
                        id="filter-q"
                        name="q"
                        :label="__('catalog.search_label')"
                        :placeholder="__('catalog.search_placeholder')"
                        :value="$filter->keyword"
                        maxlength="100"
                    />

                    <x-ui.select
                        id="filter-type"
                        name="type"
                        :label="__('catalog.type_label')"
                        :options="$typeOptions"
                        :selected="$filter->type"
                        :placeholder="__('catalog.type_all')"
                    />

                    <x-ui.input
                        id="filter-brand"
                        name="brand"
                        :label="__('catalog.brand_label')"
                        :placeholder="__('catalog.brand_placeholder')"
                        :value="$filter->brand"
                        maxlength="100"
                    />

                    <x-ui.input
                        id="filter-area"
                        name="area"
                        :label="__('catalog.area_label')"
                        :placeholder="__('catalog.area_placeholder')"
                        :value="$filter->area"
                        maxlength="100"
                    />

                    <x-ui.input
                        id="filter-price-min"
                        name="price_min"
                        type="number"
                        inputmode="numeric"
                        min="0"
                        :label="__('catalog.price_min_label')"
                        :value="$filter->priceMin"
                    />

                    <x-ui.input
                        id="filter-price-max"
                        name="price_max"
                        type="number"
                        inputmode="numeric"
                        min="0"
                        :label="__('catalog.price_max_label')"
                        :value="$filter->priceMax"
                    />

                    <x-ui.input
                        id="filter-year-min"
                        name="year_min"
                        type="number"
                        inputmode="numeric"
                        min="1900"
                        :label="__('catalog.year_min_label')"
                        :value="$filter->yearMin"
                    />

                    <x-ui.input
                        id="filter-year-max"
                        name="year_max"
                        type="number"
                        inputmode="numeric"
                        min="1900"
                        :label="__('catalog.year_max_label')"
                        :value="$filter->yearMax"
                    />

                    <x-ui.select
                        id="filter-inspected"
                        name="inspected"
                        :label="__('catalog.inspected_label')"
                        :options="$inspectedOptions"
                        :selected="$filter->inspected"
                    />

                    <x-ui.select
                        id="filter-sort"
                        name="sort"
                        :label="__('catalog.sort_label')"
                        :options="$sortOptions"
                        :selected="$filter->sort"
                    />
                </div>

                <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                    <x-ui.button type="submit" variant="primary" icon="search">
                        {{ __('catalog.apply') }}
                    </x-ui.button>
                    <x-ui.button :href="route('catalog.index')" variant="ghost" icon="x-mark">
                        {{ __('catalog.reset') }}
                    </x-ui.button>
                </div>
            </form>
        </details>

        <p class="text-sm text-muted" aria-live="polite" role="status">
            {{ trans_choice('catalog.results', $listings->total(), ['count' => number_format($listings->total(), 0, ',', '.')]) }}
        </p>

        @if ($listings->isEmpty())
            <x-ui.empty-state
                icon="search"
                :title="__('catalog.empty_title')"
                :description="__('catalog.empty_description')"
                :actionLabel="__('catalog.reset')"
                :actionUrl="route('catalog.index')"
                actionIcon="x-mark"
            />
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($cards as $card)
                    <x-ui.listing-card
                        :title="$card->title"
                        :price="$card->price"
                        :year="$card->year"
                        :mileage="$card->mileage"
                        :area="$card->area"
                        :imageUrl="$card->imageUrl"
                        :imageAlt="$card->imageAlt"
                        :inspected="$card->inspected"
                        :inspectedDate="$card->inspectedDate"
                        :href="route('catalog.show', ['listing' => $card->id])"
                    />
                @endforeach
            </div>

            <x-ui.pagination :paginator="$listings" />
        @endif
    </div>
</x-layouts.app>
