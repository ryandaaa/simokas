<x-layouts.app :title="__('home.title')" :navItems="$navItems">
    <div class="ds-sans -mx-4 sm:-mx-6 lg:-mx-8 -mt-8">
        {{-- Hero Section --}}
        <section aria-labelledby="home-hero-heading" class="relative overflow-hidden bg-gradient-to-b from-ds-canvas via-ds-blue-soft/40 to-ds-canvas pt-12 pb-16 lg:py-20">
            <div class="absolute top-10 left-10 w-32 h-32 bg-ds-blue/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="absolute bottom-10 right-20 w-48 h-48 bg-ds-yellow/15 rounded-full blur-3xl pointer-events-none"></div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
                <div class="max-w-2xl mx-auto text-center space-y-6">
                    <h1 id="home-hero-heading" class="text-4xl sm:text-5xl font-extrabold text-ds-ink leading-[1.15] tracking-tight">
                        {{ __('home.hero_title') }}
                    </h1>
                    <p class="text-base sm:text-lg text-ds-muted max-w-xl mx-auto font-normal leading-relaxed">
                        {{ __('home.hero_subtitle') }}
                    </p>

                    {{-- Hero Search Bar --}}
                    <div class="pt-2 max-w-lg mx-auto">
                        <form method="GET" action="{{ route('catalog.index') }}" class="flex flex-col sm:flex-row items-center gap-2 p-2 bg-white rounded-2xl shadow-xl shadow-ds-ink/10 border border-ds-line/60">
                            <div class="flex items-center gap-3 px-3 w-full sm:w-auto flex-1">
                                <i aria-hidden="true" class="fa-solid fa-magnifying-glass text-ds-faint"></i>
                                <input type="text" name="q" maxlength="100" placeholder="{{ __('catalog.search_placeholder') }}" class="w-full py-2 bg-transparent text-sm font-medium text-ds-ink focus:outline-none placeholder:text-ds-faint">
                            </div>
                            <button type="submit" class="w-full sm:w-auto px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md flex items-center justify-center gap-2">
                                <span>{{ __('home.hero_browse_all') }}</span>
                                <i aria-hidden="true" class="fa-solid fa-arrow-right text-xs"></i>
                            </button>
                        </form>
                    </div>

                    <div class="flex flex-wrap items-center justify-center gap-2 text-xs">
                        <a href="{{ route('catalog.index', ['type' => 'car']) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 font-semibold text-ds-muted hover:text-ds-blue transition-colors">
                            <i aria-hidden="true" class="fa-solid fa-car text-ds-blue"></i>
                            {{ __('home.hero_search_car') }}
                        </a>
                        <span class="text-ds-line">|</span>
                        <a href="{{ route('catalog.index', ['type' => 'motorcycle']) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 font-semibold text-ds-muted hover:text-ds-blue transition-colors">
                            <i aria-hidden="true" class="fa-solid fa-motorcycle text-ds-blue"></i>
                            {{ __('home.hero_search_motorcycle') }}
                        </a>
                    </div>

                    {{-- Trust Badges --}}
                    <div class="pt-2 flex flex-wrap items-center justify-center gap-8 text-ds-muted text-xs font-semibold">
                        <div class="flex items-center gap-2">
                            <i aria-hidden="true" class="fa-solid fa-shield-halved text-ds-blue text-sm"></i>
                            <span>{{ __('home.trust_verified') }}</span>
                        </div>
                                <div class="flex items-center gap-2">
                                    <i aria-hidden="true" class="fa-solid fa-bolt text-ds-blue text-sm"></i>
                            <span>{{ __('home.trust_from_price') }}</span>
                        </div>
                                <div class="flex items-center gap-2">
                                    <i aria-hidden="true" class="fa-solid fa-location-dot text-ds-blue text-sm"></i>
                            <span>{{ __('home.trust_area') }}</span>
                        </div>
                    </div>

                    @guest
                        <p class="text-xs text-ds-faint">
                            {{ __('home.hero_guest_hint') }}
                        </p>
                        <div class="flex flex-row justify-center gap-2">
                            <a href="{{ route('login') }}" class="px-4 py-1.5 text-sm font-semibold text-ds-ink hover:text-ds-blue transition-colors">
                                {{ __('home.hero_login') }}
                            </a>
                            <a href="{{ route('identity.register') }}" class="px-4 py-1.5 text-sm font-semibold text-ds-ink hover:text-ds-blue transition-colors">
                                {{ __('home.hero_register') }}
                            </a>
                        </div>
                    @endguest
                </div>
            </div>
        </section>

        {{-- Category Icon Navigation Bar --}}
        <section aria-label="Kategori Kendaraan" class="py-8 bg-white border-y border-ds-line/60 shadow-xs">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-start md:justify-center gap-4 sm:gap-6 lg:gap-8 overflow-x-auto pb-2 pt-1">
                    <a href="{{ route('catalog.index') }}" class="flex flex-col items-center gap-2.5 min-w-[85px] group focus:outline-none">
                        <span class="w-14 h-14 rounded-2xl bg-ds-soft group-hover:bg-ds-blue group-hover:text-white text-ds-ink flex items-center justify-center text-xl transition-all shadow-xs group-hover:-translate-y-1">
                            <i aria-hidden="true" class="fa-solid fa-border-all"></i>
                        </span>
                        <span class="text-xs font-bold text-ds-ink group-hover:text-ds-blue transition-colors">{{ __('home.cat_all') }}</span>
                    </a>
                    <a href="{{ route('catalog.index', ['type' => 'car']) }}" class="flex flex-col items-center gap-2.5 min-w-[85px] group focus:outline-none">
                        <span class="w-14 h-14 rounded-2xl bg-ds-soft group-hover:bg-ds-blue group-hover:text-white text-ds-ink flex items-center justify-center text-xl transition-all shadow-xs group-hover:-translate-y-1">
                            <i aria-hidden="true" class="fa-solid fa-car"></i>
                        </span>
                        <span class="text-xs font-bold text-ds-ink group-hover:text-ds-blue transition-colors">{{ __('home.cat_car') }}</span>
                    </a>
                    <a href="{{ route('catalog.index', ['type' => 'motorcycle']) }}" class="flex flex-col items-center gap-2.5 min-w-[85px] group focus:outline-none">
                        <span class="w-14 h-14 rounded-2xl bg-ds-soft group-hover:bg-ds-blue group-hover:text-white text-ds-ink flex items-center justify-center text-xl transition-all shadow-xs group-hover:-translate-y-1">
                            <i aria-hidden="true" class="fa-solid fa-motorcycle"></i>
                        </span>
                        <span class="text-xs font-bold text-ds-ink group-hover:text-ds-blue transition-colors">{{ __('home.cat_motorcycle') }}</span>
                    </a>
                    <a href="{{ route('catalog.index', ['inspected' => 'inspected']) }}" class="flex flex-col items-center gap-2.5 min-w-[85px] group focus:outline-none">
                        <span class="w-14 h-14 rounded-2xl bg-ds-soft group-hover:bg-ds-blue group-hover:text-white text-ds-ink flex items-center justify-center text-xl transition-all shadow-xs group-hover:-translate-y-1">
                            <i aria-hidden="true" class="fa-solid fa-shield-halved"></i>
                        </span>
                        <span class="text-xs font-bold text-ds-ink group-hover:text-ds-blue transition-colors">{{ __('home.cat_inspected') }}</span>
                    </a>
                </div>
            </div>
        </section>

        {{-- Featured: Sidebar + Grid --}}
        <section aria-labelledby="home-featured-heading" class="py-12 bg-ds-canvas">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-10 space-y-3">
                    <span class="text-ds-blue text-xs font-extrabold uppercase tracking-wider">{{ __('home.featured_eyebrow') }}</span>
                    <h2 id="home-featured-heading" class="text-3xl font-extrabold text-ds-ink tracking-tight">{{ __('home.featured_title') }}</h2>
                    <p class="text-sm text-ds-muted">{{ __('home.featured_subtitle') }}</p>
                </div>

                <div class="grid lg:grid-cols-12 gap-8">
                    {{-- Sidebar --}}
                    <aside class="lg:col-span-3">
                        <div class="bg-white rounded-2xl p-5 border border-ds-line/80 shadow-xs space-y-4">
                            <h3 class="text-xs font-extrabold text-ds-faint uppercase tracking-wider">{{ __('home.browse_title') }}</h3>
                            <ul class="space-y-1.5 text-sm font-semibold text-ds-muted">
                                <li>
                                    <a href="{{ route('catalog.index', ['type' => 'car', 'price_max' => 100000000]) }}" class="flex items-center justify-between px-3 py-2 rounded-xl hover:bg-ds-canvas hover:text-ds-ink transition-colors">
                                        <span>{{ __('home.browse_car_budget') }}</span>
                                        <i aria-hidden="true" class="fa-solid fa-chevron-right text-xs"></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('catalog.index', ['type' => 'motorcycle', 'price_max' => 20000000]) }}" class="flex items-center justify-between px-3 py-2 rounded-xl hover:bg-ds-canvas hover:text-ds-ink transition-colors">
                                        <span>{{ __('home.browse_motor_budget') }}</span>
                                        <i aria-hidden="true" class="fa-solid fa-chevron-right text-xs"></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('catalog.index', ['inspected' => 'inspected']) }}" class="flex items-center justify-between px-3 py-2 rounded-xl hover:bg-ds-canvas hover:text-ds-ink transition-colors">
                                        <span>{{ __('home.browse_inspected') }}</span>
                                        <span class="bg-ds-green-soft text-ds-green-ink text-[10px] font-bold px-2 py-0.5 rounded-full">OK</span>
                                    </a>
                                </li>
                            </ul>

                            <hr class="border-ds-line/60">

                            <div class="bg-gradient-to-br from-ds-ink to-ds-indigo text-white rounded-2xl p-4 text-center space-y-3">
                                <div class="w-10 h-10 mx-auto rounded-full bg-ds-blue/20 text-ds-blue flex items-center justify-center text-lg">
                                    <i aria-hidden="true" class="fa-solid fa-screwdriver-wrench"></i>
                                </div>
                                <h4 class="text-xs font-bold">{{ __('home.inspector_title') }}</h4>
                                <p class="text-[11px] text-ds-soft">{{ __('home.inspector_description') }}</p>
                                <a href="{{ route('identity.register') }}" class="block w-full py-2 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-extrabold text-xs rounded-xl transition-all">
                                    {{ __('home.inspector_action') }}
                                </a>
                            </div>
                        </div>
                    </aside>

                    {{-- Cards Grid --}}
                    <div class="lg:col-span-9">
                        @if (count($cards) === 0)
                            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-8 text-center max-w-2xl mx-auto">
                                <div class="w-14 h-14 mx-auto rounded-2xl bg-ds-soft text-ds-muted flex items-center justify-center text-xl mb-4">
                                    <i aria-hidden="true" class="fa-solid fa-magnifying-glass"></i>
                                </div>
                                <h3 class="text-lg font-extrabold text-ds-ink">{{ __('home.empty_title') }}</h3>
                                <p class="text-xs text-ds-muted mt-2 leading-relaxed">{{ __('home.empty_description') }}</p>
                                <div class="flex justify-center mt-6">
                                    <a href="{{ route('catalog.index') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md">
                                        <span>{{ __('home.empty_action') }}</span>
                                        <i aria-hidden="true" class="fa-solid fa-arrow-right text-xs"></i>
                                    </a>
                                </div>
                            </div>
                        @else
                            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
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
                            <div class="mt-10 text-center">
                                <a href="{{ route('catalog.index') }}" class="px-8 py-3 bg-white border border-ds-line hover:border-ds-blue text-ds-ink hover:text-ds-blue font-bold text-xs rounded-xl transition-all shadow-xs hover:shadow-md inline-flex items-center gap-2">
                                    <span>{{ __('home.featured_more') }}</span>
                                    <i aria-hidden="true" class="fa-solid fa-arrows-rotate text-xs"></i>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        {{-- How It Works --}}
        <section aria-labelledby="home-steps-heading" class="py-16 bg-white border-t border-ds-line/60">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-12 space-y-3">
                    <span class="text-ds-blue text-xs font-extrabold uppercase tracking-wider">{{ __('home.steps_eyebrow') }}</span>
                    <h2 id="home-steps-heading" class="text-3xl font-extrabold text-ds-ink tracking-tight">{{ __('home.steps_title') }}</h2>
                    <p class="text-sm text-ds-muted">{{ __('home.steps_subtitle') }}</p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach ([
                        ['icon' => 'fa-magnifying-glass', 'number' => '01', 'title' => __('home.step_1_title'), 'description' => __('home.step_1_description')],
                        ['icon' => 'fa-clipboard-check', 'number' => '02', 'title' => __('home.step_2_title'), 'description' => __('home.step_2_description')],
                        ['icon' => 'fa-file-lines', 'number' => '03', 'title' => __('home.step_3_title'), 'description' => __('home.step_3_description')],
                        ['icon' => 'fa-circle-check', 'number' => '04', 'title' => __('home.step_4_title'), 'description' => __('home.step_4_description')],
                    ] as $step)
                        <div class="bg-ds-canvas rounded-2xl p-6 border border-ds-line/60 relative group hover:bg-white hover:shadow-xl transition-all duration-300">
                            <div class="w-14 h-14 rounded-2xl bg-ds-blue text-white flex items-center justify-center text-xl mb-5 group-hover:scale-110 transition-transform">
                                <i aria-hidden="true" class="fa-solid {{ $step['icon'] }}"></i>
                            </div>
                            <span class="text-4xl font-extrabold text-ds-line absolute top-6 right-6">{{ $step['number'] }}</span>
                            <h3 class="text-lg font-bold text-ds-ink mb-2">{{ $step['title'] }}</h3>
                            <p class="text-xs text-ds-muted leading-relaxed">{{ $step['description'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Inspector Banner --}}
        <section aria-labelledby="home-inspector-heading" class="py-16 bg-ds-canvas">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-gradient-to-r from-ds-blue via-ds-indigo to-ds-ink rounded-3xl p-8 lg:p-12 text-white relative overflow-hidden shadow-2xl">
                    <div class="grid lg:grid-cols-12 gap-8 items-center relative z-10">
                        <div class="lg:col-span-7 space-y-5">
                            <span class="px-3.5 py-1.5 rounded-full bg-white/10 text-ds-yellow text-xs font-bold uppercase tracking-wider inline-block">
                                {{ __('home.banner_eyebrow') }}
                            </span>
                            <h2 id="home-inspector-heading" class="text-3xl sm:text-4xl font-extrabold leading-tight">
                                {{ __('home.inspector_title') }}
                            </h2>
                            <p class="text-ds-soft text-sm max-w-lg font-normal leading-relaxed">
                                {{ __('home.inspector_description') }}
                            </p>
                            <div class="pt-2">
                                <a href="{{ route('identity.register') }}" class="inline-flex items-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md">
                                    <span>{{ __('home.inspector_action') }}</span>
                                    <i aria-hidden="true" class="fa-solid fa-arrow-right text-xs"></i>
                                </a>
                            </div>
                        </div>
                        <div class="lg:col-span-5 grid grid-cols-3 gap-4 text-center bg-white/10 p-6 rounded-2xl border border-white/15">
                            <div>
                                <p class="text-xl font-extrabold text-white">Rp 45 rb</p>
                                <p class="text-[11px] text-ds-soft mt-1">{{ __('home.banner_stat_fee') }}</p>
                            </div>
                            <div>
                                <p class="text-xl font-extrabold text-white">10%</p>
                                <p class="text-[11px] text-ds-soft mt-1">{{ __('home.banner_stat_platform') }}</p>
                            </div>
                            <div>
                                <p class="text-xl font-extrabold text-white">90%</p>
                                <p class="text-[11px] text-ds-soft mt-1">{{ __('home.banner_stat_share') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- FAQ --}}
        <section aria-labelledby="home-faq-heading" class="py-16 bg-white border-t border-ds-line/60">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-12 space-y-3">
                    <span class="text-ds-blue text-xs font-extrabold uppercase tracking-wider">{{ __('home.sidebar_help') }}</span>
                    <h2 id="home-faq-heading" class="text-3xl font-extrabold text-ds-ink tracking-tight">{{ __('home.help.faq_title') }}</h2>
                    <p class="text-sm text-ds-muted">{{ __('home.faq_subtitle') }}</p>
                </div>

                <div class="max-w-3xl mx-auto space-y-4" x-data="{ open: 1 }">
                    @foreach ([1, 2, 3] as $i)
                        <div class="border border-ds-line/80 rounded-2xl overflow-hidden bg-white transition-all shadow-xs">
                            <button @click="open = (open === {{ $i }} ? 0 : {{ $i }})" class="w-full px-6 py-4 text-left flex items-center justify-between font-bold text-sm text-ds-ink hover:text-ds-blue transition-colors focus:outline-none">
                                <span>{{ __('home.help.faq_q'.$i) }}</span>
                                <i aria-hidden="true" class="fa-solid text-xs text-ds-faint transition-transform" :class="open === {{ $i }} ? 'fa-minus rotate-180' : 'fa-plus'"></i>
                            </button>
                            <div x-show="open === {{ $i }}" x-cloak class="px-6 pb-4 text-xs text-ds-muted leading-relaxed border-t border-ds-line/60 pt-3">
                                {{ __('home.help.faq_a'.$i) }}
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8 text-center">
                    <a href="{{ route('catalog.help') }}" class="px-8 py-3 bg-white border border-ds-line hover:border-ds-blue text-ds-ink hover:text-ds-blue font-bold text-xs rounded-xl transition-all shadow-xs hover:shadow-md inline-flex items-center gap-2">
                        <span>{{ __('home.faq_more') }}</span>
                        <i aria-hidden="true" class="fa-solid fa-arrow-right text-xs"></i>
                    </a>
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
