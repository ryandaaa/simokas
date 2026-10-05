<x-layouts.app :title="__('home.title')" :navItems="$navItems">
    <div class="space-y-8">
        <section aria-labelledby="home-hero-heading" class="rounded-xl bg-surface p-6 text-center sm:p-10">
            <h1 id="home-hero-heading" class="text-2xl font-bold text-text sm:text-3xl">
                {{ __('home.hero_title') }}
            </h1>
            <p class="mx-auto mt-2 max-w-xl text-sm text-muted sm:text-base">
                {{ __('home.hero_subtitle') }}
            </p>
            <div class="mt-6 flex flex-col justify-center gap-2 sm:flex-row">
                <x-ui.button :href="route('catalog.index', ['type' => 'car'])" variant="primary" icon="search">
                    {{ __('home.hero_search_car') }}
                </x-ui.button>
                <x-ui.button :href="route('catalog.index', ['type' => 'motorcycle'])" variant="secondary" icon="search">
                    {{ __('home.hero_search_motorcycle') }}
                </x-ui.button>
            </div>
            @guest
                <p class="mt-4 text-sm text-muted">
                    {{ __('home.hero_guest_hint') }}
                </p>
                <div class="mt-2 flex flex-col justify-center gap-2 sm:flex-row">
                    <x-ui.button :href="route('login')" variant="ghost">
                        {{ __('home.hero_login') }}
                    </x-ui.button>
                    <x-ui.button :href="route('identity.register')" variant="ghost">
                        {{ __('home.hero_register') }}
                    </x-ui.button>
                </div>
            @endguest
        </section>

        <section aria-labelledby="home-steps-heading">
            <h2 id="home-steps-heading" class="text-lg font-bold text-text sm:text-xl">
                {{ __('home.steps_title') }}
            </h2>
            <p class="mt-1 text-sm text-muted">
                {{ __('home.steps_subtitle') }}
            </p>
            <ol class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['icon' => 'search', 'title' => __('home.step_1_title'), 'description' => __('home.step_1_description')],
                    ['icon' => 'shield-check', 'title' => __('home.step_2_title'), 'description' => __('home.step_2_description')],
                    ['icon' => 'document-text', 'title' => __('home.step_3_title'), 'description' => __('home.step_3_description')],
                    ['icon' => 'tag', 'title' => __('home.step_4_title'), 'description' => __('home.step_4_description')],
                ] as $index => $step)
                    <li>
                        <x-ui.card>
                            <span class="inline-flex h-11 w-11 items-center justify-center rounded-lg bg-primary/10 text-primary" aria-hidden="true">
                                <x-ui.icon :name="$step['icon']" class="h-6 w-6" />
                            </span>
                            <h3 class="mt-3 text-base font-semibold text-text">
                                {{ ($index + 1) . '. ' . $step['title'] }}
                            </h3>
                            <p class="mt-1 text-sm text-muted">
                                {{ $step['description'] }}
                            </p>
                        </x-ui.card>
                    </li>
                @endforeach
            </ol>
        </section>

        <section aria-labelledby="home-featured-heading">
            <div class="flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h2 id="home-featured-heading" class="text-lg font-bold text-text sm:text-xl">
                        {{ __('home.featured_title') }}
                    </h2>
                    <p class="mt-1 text-sm text-muted">
                        {{ __('home.featured_subtitle') }}
                    </p>
                </div>
                <x-ui.button :href="route('catalog.index')" variant="ghost" iconRight="chevron-right">
                    {{ __('home.featured_more') }}
                </x-ui.button>
            </div>

            @if (count($cards) === 0)
                <x-ui.empty-state
                    icon="search"
                    :title="__('home.empty_title')"
                    :description="__('home.empty_description')"
                    :actionLabel="__('home.empty_action')"
                    :actionUrl="route('catalog.index')"
                    actionIcon="search"
                />
            @else
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
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
            @endif
        </section>

        <section aria-labelledby="home-inspector-heading">
            <x-ui.card>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-accent/15 text-warning" aria-hidden="true">
                        <x-ui.icon name="shield-check" class="h-6 w-6" />
                    </span>
                    <div>
                        <h2 id="home-inspector-heading" class="text-lg font-bold text-text">
                            {{ __('home.inspector_title') }}
                        </h2>
                        <p class="mt-1 text-sm text-muted">
                            {{ __('home.inspector_description') }}
                        </p>
                        <div class="mt-4">
                            <x-ui.button :href="route('identity.register')" variant="primary" icon="user">
                                {{ __('home.inspector_action') }}
                            </x-ui.button>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </section>
    </div>
</x-layouts.app>
