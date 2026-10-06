<x-layouts.app :title="__('home.title')" :navItems="$navItems">
    <div class="space-y-16 pb-12">
        {{-- Hero Section --}}
        <section aria-labelledby="home-hero-heading" class="text-center py-8 sm:py-16">
            <h1 id="home-hero-heading" class="text-3xl sm:text-4xl md:text-5xl font-bold text-obs tracking-tight uppercase max-w-3xl mx-auto">
                {{ __('home.hero_title') }}
            </h1>
            <p class="mx-auto mt-4 max-w-xl text-sm sm:text-base text-slate leading-relaxed">
                {{ __('home.hero_subtitle') }}
            </p>
            <div class="mt-8 flex flex-col justify-center items-center gap-3 sm:flex-row">
                <x-ui.button :href="route('catalog.index', ['type' => 'car'])" variant="primary" icon="search" class="w-full sm:w-auto min-w-[180px]">
                    {{ __('home.hero_search_car') }}
                </x-ui.button>
                <x-ui.button :href="route('catalog.index', ['type' => 'motorcycle'])" variant="secondary" icon="search" class="w-full sm:w-auto min-w-[180px]">
                    {{ __('home.hero_search_motorcycle') }}
                </x-ui.button>
            </div>
            @guest
                <p class="mt-6 text-xs text-pebble">
                    {{ __('home.hero_guest_hint') }}
                </p>
                <div class="mt-2 flex flex-row justify-center gap-2">
                    <x-ui.button :href="route('login')" variant="ghost" class="!min-h-[36px] !px-4 !py-1 text-sm">
                        {{ __('home.hero_login') }}
                    </x-ui.button>
                    <x-ui.button :href="route('identity.register')" variant="ghost" class="!min-h-[36px] !px-4 !py-1 text-sm">
                        {{ __('home.hero_register') }}
                    </x-ui.button>
                </div>
            @endguest
        </section>

        {{-- Kartu Kategori Mobil & Motor ala Wise --}}
        <section aria-label="Kategori Kendaraan">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <a href="{{ route('catalog.index', ['type' => 'car']) }}"
                   class="relative overflow-hidden rounded-[10px] aspect-[2/1] bg-forest text-paper p-6 sm:p-8 flex flex-col justify-between group hover:opacity-95 transition-all">
                    <div>
                        <span class="text-title font-bold text-paper block drop-shadow-xs">Mobil Bekas</span>
                        <span class="text-xs text-lime font-medium mt-1 inline-block">Terinspeksi Independen</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-paper/85">Inspeksi Rp 50.000</span>
                        <span class="w-8 h-8 rounded-full bg-paper/10 flex items-center justify-center text-paper group-hover:translate-x-1 transition-transform">
                            <x-ui.icon name="chevron-right" class="w-4 h-4" />
                        </span>
                    </div>
                </a>

                <a href="{{ route('catalog.index', ['type' => 'motorcycle']) }}"
                   class="relative overflow-hidden rounded-[10px] aspect-[2/1] bg-forest text-paper p-6 sm:p-8 flex flex-col justify-between group hover:opacity-95 transition-all">
                    <div>
                        <span class="text-title font-bold text-paper block drop-shadow-xs">Motor Bekas</span>
                        <span class="text-xs text-lime font-medium mt-1 inline-block">Terinspeksi Independen</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-paper/85">Inspeksi Rp 15.000</span>
                        <span class="w-8 h-8 rounded-full bg-paper/10 flex items-center justify-center text-paper group-hover:translate-x-1 transition-transform">
                            <x-ui.icon name="chevron-right" class="w-4 h-4" />
                        </span>
                    </div>
                </a>
            </div>
        </section>

        {{-- Cara Kerja (4 Langkah) ala Wise --}}
        <section id="cara-kerja" aria-labelledby="home-steps-heading" class="pt-4">
            <div class="text-center max-w-xl mx-auto">
                <h2 id="home-steps-heading" class="text-2xl sm:text-3xl font-bold text-obs tracking-tight">
                    {{ __('home.steps_title') }}
                </h2>
                <p class="mt-2 text-sm text-slate">
                    {{ __('home.steps_subtitle') }}
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 mt-12">
                @foreach ([
                    ['number' => '01', 'title' => __('home.step_1_title'), 'description' => __('home.step_1_description')],
                    ['number' => '02', 'title' => __('home.step_2_title'), 'description' => __('home.step_2_description')],
                    ['number' => '03', 'title' => __('home.step_3_title'), 'description' => __('home.step_3_description')],
                    ['number' => '04', 'title' => __('home.step_4_title'), 'description' => __('home.step_4_description')],
                ] as $step)
                    <div class="p-6 rounded-[10px] bg-surface border border-fog flex flex-col">
                        <p class="text-promo font-bold text-forest">{{ $step['number'] }}</p>
                        <h3 class="text-title font-bold text-obs mt-2">
                            {{ $step['title'] }}
                        </h3>
                        <p class="text-sm text-slate mt-2 leading-relaxed">
                            {{ $step['description'] }}
                        </p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Listing Unggulan / Kendaraan Terbaru --}}
        <section aria-labelledby="home-featured-heading" class="pt-4">
            <div class="flex flex-wrap items-end justify-between gap-4 mb-6">
                <div>
                    <h2 id="home-featured-heading" class="text-2xl sm:text-3xl font-bold text-obs tracking-tight">
                        {{ __('home.featured_title') }}
                    </h2>
                    <p class="mt-1 text-sm text-slate">
                        {{ __('home.featured_subtitle') }}
                    </p>
                </div>
                <x-ui.button :href="route('catalog.index')" variant="ghost" iconRight="chevron-right" class="text-sm">
                    {{ __('home.featured_more') }}
                </x-ui.button>
            </div>

            @if (count($cards) === 0)
                <x-ui.empty-state
                    icon="kaca-pembesar"
                    :title="__('home.empty_title')"
                    :description="__('home.empty_description')"
                    :actionLabel="__('home.empty_action')"
                    :actionUrl="route('catalog.index')"
                    actionIcon="kaca-pembesar"
                />
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
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
                <div class="text-center mt-10">
                    <x-ui.button :href="route('catalog.index')" variant="secondary" class="w-full sm:w-[280px]">
                        {{ __('home.featured_more') }}
                    </x-ui.button>
                </div>
            @endif
        </section>

        {{-- Banner Inspektor Mekanik ala Wise --}}
        <section aria-labelledby="home-inspector-heading" class="pt-4">
            <div class="rounded-[10px] bg-forest text-paper p-8 sm:p-12 grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                <div>
                    <h2 id="home-inspector-heading" class="text-2xl sm:text-3xl font-bold text-lime tracking-tight">
                        {{ __('home.inspector_title') }}
                    </h2>
                    <p class="mt-3 text-sm text-paper/80 max-w-md leading-relaxed">
                        {{ __('home.inspector_description') }}
                    </p>
                    <div class="mt-6">
                        <x-ui.button :href="route('identity.register')" variant="primary" icon="orang">
                            {{ __('home.inspector_action') }}
                        </x-ui.button>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4 text-center border-t lg:border-t-0 lg:border-l border-paper/15 pt-6 lg:pt-0 lg:pl-8">
                    <div>
                        <p class="text-promo font-bold text-paper">Rp 45 rb</p>
                        <p class="text-xs text-paper/60 mt-1">fee bersih mobil</p>
                    </div>
                    <div>
                        <p class="text-promo font-bold text-paper">3%</p>
                        <p class="text-xs text-paper/60 mt-1">komisi platform</p>
                    </div>
                    <div>
                        <p class="text-promo font-bold text-paper">90%</p>
                        <p class="text-xs text-paper/60 mt-1">bagian inspektor</p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
